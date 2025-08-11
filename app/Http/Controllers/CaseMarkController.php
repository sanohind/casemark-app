<?php
// app/Http/Controllers/CaseMarkController.php
namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\ContentList;
use App\Models\ScanHistory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ContentListImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CaseMarkController extends Controller
{
    public function index()
    {
        return view('casemark.dashboard');
    }

    // Content List Management
    public function contentList($caseNo = null)
    {
        if ($caseNo) {
            $case = CaseModel::where('case_no', $caseNo)->firstOrFail();
            $contentLists = $case->contentLists()->get();
            // Fetch only scanned boxes for this case
            $scanHistory = $case->scanHistory()->where('status', 'scanned')->orderBy('scanned_at', 'desc')->get();

            // Calculate progress: total scanned quantity / total quantity
            $totalScannedQty = $scanHistory->sum('scanned_qty');
            $totalQty = $contentLists->sum('quantity');
            $progress = $totalQty > 0 ? $totalScannedQty . '/' . $totalQty : '0/0';

            return view('casemark.content-list', compact('case', 'contentLists', 'scanHistory', 'progress'));
        }

        return view('casemark.content-list');
    }

    // History Management  
    public function history(Request $request)
    {
        $query = CaseModel::with(['contentLists', 'scanHistory'])
            ->where('status', 'packed');
        
        // Apply case no filter
        if ($request->filled('case_no')) {
            $query->where('case_no', $request->case_no);
        }
        
        // Apply prod month filter
        if ($request->filled('prod_month')) {
            $query->where('prod_month', $request->prod_month);
        }
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('case_no', 'like', "%{$search}%")
                  ->orWhere('prod_month', 'like', "%{$search}%")
                  ->orWhereHas('contentLists', function($subQ) use ($search) {
                      $subQ->where('part_no', 'like', "%{$search}%")
                           ->orWhere('part_name', 'like', "%{$search}%");
                  });
            });
        }
        
        $cases = $query->orderBy('packing_date', 'desc')->paginate(10);
        
        // Append current filters to pagination links
        $cases->appends($request->only(['case_no', 'prod_month', 'search']));
        
        // Get all packed cases for filter options (not paginated)
        $allPackedCases = CaseModel::where('status', 'packed')
            ->select('case_no', 'prod_month')
            ->orderBy('packing_date', 'desc')
            ->get();
        
        // Get statistics from all packed cases (not paginated)
        $allPackedCasesForStats = CaseModel::with(['contentLists', 'scanHistory'])
            ->where('status', 'packed');
        
        // Apply same filters to statistics query
        if ($request->filled('case_no')) {
            $allPackedCasesForStats->where('case_no', $request->case_no);
        }
        
        if ($request->filled('prod_month')) {
            $allPackedCasesForStats->where('prod_month', $request->prod_month);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $allPackedCasesForStats->where(function($q) use ($search) {
                $q->where('case_no', 'like', "%{$search}%")
                  ->orWhere('prod_month', 'like', "%{$search}%")
                  ->orWhereHas('contentLists', function($subQ) use ($search) {
                      $subQ->where('part_no', 'like', "%{$search}%")
                           ->orWhere('part_name', 'like', "%{$search}%");
                  });
            });
        }
        
        $allPackedCasesForStats = $allPackedCasesForStats->get();
        
        // Calculate statistics
        $packedCasesCount = $allPackedCasesForStats->count();
        $totalBoxesCount = $allPackedCasesForStats->sum(function($case) { 
            return $case->contentLists->count(); 
        });
        $totalQuantityCount = $allPackedCasesForStats->sum(function($case) { 
            return $case->contentLists->sum('quantity'); 
        });
        $productionMonthsCount = $allPackedCasesForStats->unique('prod_month')->count();
        
        // Hitung progress untuk setiap case
        foreach ($cases as $case) {
            $totalQty = $case->contentLists->sum('quantity');
            $scannedQty = $case->scanHistory()->where('status', 'packed')->sum('scanned_qty');
            $case->progress = $totalQty > 0 ? $scannedQty . '/' . $totalQty : '0/0';
        }

        return view('casemark.history', compact('cases', 'allPackedCases', 'packedCasesCount', 'totalBoxesCount', 'totalQuantityCount', 'productionMonthsCount'));
    }

    public function historyDetail($caseNo)
    {
        $case = CaseModel::where('case_no', $caseNo)->firstOrFail();

        // Ambil semua content lists untuk case ini
        $contentLists = $case->contentLists()->orderBy('box_no')->get();
        
        // Ambil semua scan history untuk case ini (baik scanned maupun unscanned)
        $scanHistory = ScanHistory::with('case')
            ->where('case_no', $case->case_no)
            ->orderBy('scanned_at', 'desc')
            ->get();

        // Hitung progress
        $totalScannedQty = $scanHistory->sum('scanned_qty');
        $totalQty = $contentLists->sum('quantity');
        $progress = $totalQty > 0 ? $totalScannedQty . '/' . $totalQty : '0/0';

        // Hitung progress per part untuk Scan Progress table
        $scanProgress = $contentLists->groupBy('part_no')->map(function ($items) use ($scanHistory) {
            $totalQty = $items->sum('quantity');
            $scannedQty = $scanHistory->where('part_no', $items->first()->part_no)->sum('scanned_qty');
            return [
                'part_no' => $items->first()->part_no,
                'part_name' => $items->first()->part_name,
                'quantity' => $items->first()->quantity,
                'progress' => $scannedQty . '/' . $totalQty
            ];
        })->values();

        return view('casemark.history-detail', compact('case', 'contentLists', 'scanHistory', 'progress', 'scanProgress'));
    }

    // Upload Excel
    public function upload()
    {
        return view('casemark.upload');
    }



    public function uploadExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv',
            'case_no' => 'required|string',
            'destination' => 'required|string',
            'prod_month' => 'required|string',
            'case_size' => 'required|string',
            'gross_weight' => 'required|numeric',
            'net_weight' => 'required|numeric'
        ]);

        try {
            // Check if case already exists
            $existingCase = CaseModel::where('case_no', $request->case_no)->first();
            
            if ($existingCase) {
                // Get existing content lists count for information
                $existingContentCount = $existingCase->contentLists()->count();
                
                return back()->with('warning', "Case No. {$request->case_no} sudah pernah diupload sebelumnya dengan {$existingContentCount} item. Data akan diupdate dengan file baru.");
            }

            // Create new case
            $case = CaseModel::create([
                'case_no' => $request->case_no,
                'destination' => (string)$request->destination,
                'order_no' => (string)$request->order_no,
                'prod_month' => (string)$request->prod_month,
                'case_size' => (string)$request->case_size,
                'gross_weight' => (float)$request->gross_weight,
                'net_weight' => (float)$request->net_weight,
                'status' => 'unpacked'
            ]);

            // Import Excel data dengan logging yang lebih detail
            Log::info('Starting Excel import for case:', [
                'case_id' => $case->id,
                'case_no' => $case->case_no,
                'file_name' => $request->file('excel_file')->getClientOriginalName()
            ]);

            Excel::import(new ContentListImport($case->id), $request->file('excel_file'));

            // Log hasil import
            $importedCount = $case->contentLists()->count();
            Log::info('Excel import completed:', [
                'case_id' => $case->id,
                'imported_records' => $importedCount
            ]);

            return back()->with('success', "Excel berhasil diupload! {$importedCount} item berhasil diimport untuk Case No. {$request->case_no}.");
        } catch (\Exception $e) {
            Log::error('Excel import failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // List Case Mark
    public function listCaseMark(Request $request)
    {
        $query = CaseModel::with(['contentLists', 'scanHistory']);
        
        // Apply status filter
        if ($request->filled('status')) {
            $status = $request->status;
            
            if ($status === 'packed') {
                $query->where('status', 'packed');
            } elseif ($status === 'unpacked') {
                // Cases that are not packed and have no scan history
                $query->where('status', '!=', 'packed')
                      ->whereDoesntHave('scanHistory');
            } elseif ($status === 'in-progress') {
                // Cases that are not packed but have scan history
                $query->where('status', '!=', 'packed')
                      ->whereHas('scanHistory');
            }
        }
        
        // Apply prod month filter
        if ($request->filled('prod_month')) {
            $query->where('prod_month', $request->prod_month);
        }
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('case_no', 'like', "%{$search}%")
                  ->orWhere('prod_month', 'like', "%{$search}%")
                  ->orWhereHas('contentLists', function($subQ) use ($search) {
                      $subQ->where('part_no', 'like', "%{$search}%")
                           ->orWhere('part_name', 'like', "%{$search}%");
                  });
            });
        }
        
        $cases = $query->orderBy('updated_at', 'desc')->paginate(10);
        
        // Append current filters to pagination links
        $cases->appends($request->only(['status', 'prod_month', 'search']));
        
        // Get statistics from all cases (not paginated)
        $allCases = CaseModel::with(['contentLists', 'scanHistory']);
        
        // Apply same filters to statistics query
        if ($request->filled('status')) {
            $status = $request->status;
            
            if ($status === 'packed') {
                $allCases->where('status', 'packed');
            } elseif ($status === 'unpacked') {
                $allCases->where('status', '!=', 'packed')
                      ->whereDoesntHave('scanHistory');
            } elseif ($status === 'in-progress') {
                $allCases->where('status', '!=', 'packed')
                      ->whereHas('scanHistory');
            }
        }
        
        if ($request->filled('prod_month')) {
            $allCases->where('prod_month', $request->prod_month);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $allCases->where(function($q) use ($search) {
                $q->where('case_no', 'like', "%{$search}%")
                  ->orWhere('prod_month', 'like', "%{$search}%")
                  ->orWhereHas('contentLists', function($subQ) use ($search) {
                      $subQ->where('part_no', 'like', "%{$search}%")
                           ->orWhere('part_name', 'like', "%{$search}%");
                  });
            });
        }
        
        $allCases = $allCases->get();
        
        // Calculate statistics
        $unpackedCount = 0;
        $inProgressCount = 0;
        $packedCount = 0;
        
        foreach($allCases as $case) {
            if($case->status == 'packed') {
                $packedCount++;
            } else {
                // Check if case has scan history (in progress)
                $hasScanHistory = $case->scanHistory()->exists();
                if($hasScanHistory) {
                    $inProgressCount++;
                } else {
                    $unpackedCount++;
                }
            }
        }
        
        return view('casemark.list-case-mark', compact('cases', 'unpackedCount', 'inProgressCount', 'packedCount'));
    }

// List Case Mark Detail 
public function listCaseMarkDetail($caseNo)
{
    $case = CaseModel::where('case_no', $caseNo)->firstOrFail();

    // Ambil semua content lists untuk case ini
    $contentLists = $case->contentLists()->orderBy('box_no')->get();
    
    // Ambil semua scan history untuk case ini
    $scanHistory = ScanHistory::with('case')
        ->where('case_no', $case->case_no)
        ->orderBy('scanned_at', 'desc')
        ->get();

    // Hitung progress
    $totalScannedQty = $scanHistory->sum('scanned_qty');
    $totalQty = $contentLists->sum('quantity');
    $progress = $totalQty > 0 ? $totalScannedQty . '/' . $totalQty : '0/0';

    // Hitung progress per part untuk Scan Progress table
    $scanProgress = $contentLists->groupBy('part_no')->map(function ($items) use ($scanHistory) {
        $totalQty = $items->sum('quantity');
        $scannedQty = $scanHistory->where('part_no', $items->first()->part_no)->sum('scanned_qty');
        return [
            'part_no' => $items->first()->part_no,
            'part_name' => $items->first()->part_name,
            'quantity' => $items->first()->quantity,
            'progress' => $scannedQty . '/' . $totalQty
        ];
    })->values();

    return view('casemark.list-case-mark-detail', compact('case', 'contentLists', 'scanHistory', 'progress', 'scanProgress'));
}

    public function processScan(Request $request)
    {
        $request->validate([
            'case_no' => 'required|string',
            'box_qr' => 'required|string'
        ]);

        try {
            $case = CaseModel::where('case_no', $request->case_no)->firstOrFail();

            // Parse QR code to extract box info
            $boxData = $this->parseBoxQR($request->box_qr);

            // Validate case number with case insensitive comparison
            if (strtoupper($boxData['case_no']) !== strtoupper($case->case_no)) {
                return response()->json([
                    'success' => false,
                    'message' => "Box case number does not match container case number. Container: {$case->case_no}, Box: {$boxData['case_no']}"
                ]);
            }

            // Find matching content list
            $contentList = ContentList::where('case_id', $case->id)
                ->where('box_no', $boxData['box_no'])
                ->where('part_no', $boxData['part_no'])
                ->first();

            if (!$contentList) {
                return response()->json([
                    'success' => false,
                    'message' => 'Box tidak sesuai dengan content list!'
                ]);
            }

            $errorMessage = null;

            // Use transaction with lock to prevent race condition
            DB::transaction(function () use ($case, $boxData, $contentList, &$errorMessage) {
                $existingScan = ScanHistory::where('case_no', $case->case_no)
                    ->where('box_no', $boxData['box_no'])
                    ->where('part_no', $boxData['part_no'])
                    ->lockForUpdate()
                    ->first();

                if ($existingScan) {
                    $errorMessage = 'Box sudah pernah discan!';
                    return;
                }

                // Record scan
                ScanHistory::create([
                    'case_no' => $case->case_no,
                    'box_no' => $boxData['box_no'],
                    'part_no' => $boxData['part_no'],
                    'scanned_qty' => $contentList->quantity,
                    'total_qty' => $contentList->quantity,
                    'status' => 'scanned'
                ]);
            });

            if ($errorMessage) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Box berhasil discan!',
                'data' => [
                    'box_no' => $boxData['box_no'],
                    'part_no' => $boxData['part_no'],
                    'part_name' => $contentList->part_name,
                    'quantity' => $contentList->quantity
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }



    private function parseBoxQR($qrCode)
    {
        // Assuming QR format: BOX_01|32909-BZ100-00-87
        $parts = explode('|', $qrCode);

        return [
            'box_no' => $parts[0] ?? '',
            'part_no' => $parts[1] ?? ''
        ];
    }

    public function markAsPacked(Request $request)
    {
        $request->validate([
            'case_no' => 'required|string'
        ]);

        try {
            $case = CaseModel::where('case_no', $request->case_no)->firstOrFail();

            // Update all scanned items to unscanned
            ScanHistory::where('case_no', $case->case_no)
                ->where('status', 'scanned')
                ->update([
                    'status' => 'unscanned'
                ]);

            // Update case status and packing_date
            $case->update([
                'status' => 'packed',
                'packing_date' => Carbon::now('Asia/Jakarta')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Case berhasil dipacked!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }
}
