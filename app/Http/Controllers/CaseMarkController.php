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
            
            // FIXED: Use consistent scan history query (remove status filter)
            $scanHistory = $case->scanHistory()->orderBy('scanned_at', 'desc')->get();

            // FIXED: Calculate progress using position-based matching (same as API)
            $contentByPart = $contentLists->groupBy('part_no');
            
            // Calculate total scanned using position-based logic
            $totalScannedQty = 0;
            foreach ($contentByPart as $partNo => $contentItems) {
                $scansForThisPart = $scanHistory->where('part_no', $partNo)->sortBy('created_at');
                $scannedCount = $scansForThisPart->count();
                $quantityPerBox = $contentItems->first()->quantity;
                $totalScannedQty += $scannedCount * $quantityPerBox;
            }
            
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
        
        // FIXED: Calculate progress for each case using position-based matching
        foreach ($cases as $case) {
            $contentByPart = $case->contentLists->groupBy('part_no');
            $scanHistory = $case->scanHistory;
            
            $totalScannedQty = 0;
            foreach ($contentByPart as $partNo => $contentItems) {
                $scansForThisPart = $scanHistory->where('part_no', $partNo)->sortBy('created_at');
                $scannedCount = $scansForThisPart->count();
                $quantityPerBox = $contentItems->first()->quantity;
                $totalScannedQty += $scannedCount * $quantityPerBox;
            }
            
            $totalQty = $case->contentLists->sum('quantity');
            $case->progress = $totalQty > 0 ? $totalScannedQty . '/' . $totalQty : '0/0';
        }

        return view('casemark.history', compact('cases', 'allPackedCases', 'packedCasesCount', 'totalBoxesCount', 'totalQuantityCount', 'productionMonthsCount'));
    }

    public function historyDetail($caseNo)
    {
        $case = CaseModel::where('case_no', $caseNo)->firstOrFail();

        // Ambil semua content lists untuk case ini
        $contentLists = $case->contentLists()->orderBy('box_no')->get();
        
        // Ambil semua scan history untuk case ini
        $scanHistory = ScanHistory::with('case')
            ->where('case_no', $case->case_no)
            ->get();

        // FIXED: Create indexed scan history using position-based matching (same as API)
        $contentByPart = $contentLists->groupBy('part_no');
        $indexedScanHistory = [];
        
        foreach ($contentLists as $content) {
            // Get all scans for this part number, ordered by scan time
            $scansForThisPart = $scanHistory->where('part_no', $content->part_no)
                ->sortBy('created_at')
                ->values();
            
            // Get the position of this content item within its part group
            $contentItemsForThisPart = $contentByPart[$content->part_no]->sortBy('box_no');
            $positionInPart = $contentItemsForThisPart->search(function($item) use ($content) {
                return $item->id === $content->id;
            });
            
            // Check if there's a scan record at this position
            $scannedBox = $scansForThisPart->get($positionInPart);
            
            if ($scannedBox) {
                $key = $content->box_no . '|' . $content->part_no;
                $indexedScanHistory[$key] = $scannedBox;
            }
        }

        // Calculate progress using position-based matching
        $totalScannedQty = 0;
        foreach ($contentByPart as $partNo => $contentItems) {
            $scansForThisPart = $scanHistory->where('part_no', $partNo)->sortBy('created_at');
            $scannedCount = $scansForThisPart->count();
            $quantityPerBox = $contentItems->first()->quantity;
            $totalScannedQty += $scannedCount * $quantityPerBox;
        }
        
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

        return view('casemark.history-detail', compact('case', 'contentLists', 'scanHistory', 'indexedScanHistory', 'progress', 'scanProgress'));
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
            ->get();

        // FIXED: Create indexed scan history using position-based matching (same as API)
        $contentByPart = $contentLists->groupBy('part_no');
        $indexedScanHistory = [];
        
        foreach ($contentLists as $content) {
            // Get all scans for this part number, ordered by scan time
            $scansForThisPart = $scanHistory->where('part_no', $content->part_no)
                ->sortBy('created_at')
                ->values();
            
            // Get the position of this content item within its part group
            $contentItemsForThisPart = $contentByPart[$content->part_no]->sortBy('box_no');
            $positionInPart = $contentItemsForThisPart->search(function($item) use ($content) {
                return $item->id === $content->id;
            });
            
            // Check if there's a scan record at this position
            $scannedBox = $scansForThisPart->get($positionInPart);
            
            if ($scannedBox) {
                $key = $content->box_no . '|' . $content->part_no;
                $indexedScanHistory[$key] = $scannedBox;
            }
        }

        // Calculate progress using position-based matching
        $totalScannedQty = 0;
        foreach ($contentByPart as $partNo => $contentItems) {
            $scansForThisPart = $scanHistory->where('part_no', $partNo)->sortBy('created_at');
            $scannedCount = $scansForThisPart->count();
            $quantityPerBox = $contentItems->first()->quantity;
            $totalScannedQty += $scannedCount * $quantityPerBox;
        }
        
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

        return view('casemark.list-case-mark-detail', compact('case', 'contentLists', 'scanHistory', 'indexedScanHistory', 'progress', 'scanProgress'));
    }

    public function processScan(Request $request)
    {
        $request->validate([
            'case_no' => 'required|string',
            'box_qr' => 'required|string'
        ]);

        try {
            $case = CaseModel::where('case_no', $request->case_no)->firstOrFail();

            // FIXED: Parse QR code with new format support
            $boxData = $this->parseBoxQR($request->box_qr);

            // FIXED: Validate case number if present in QR
            if (!empty($boxData['case_no'])) {
                if (strtoupper($boxData['case_no']) !== strtoupper($case->case_no)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Box case number does not match container case number. Container: {$case->case_no}, Box: {$boxData['case_no']}"
                    ]);
                }
            }

            // FIXED: Handle different QR formats
            if (empty($boxData['part_no'])) {
                // If part_no is not in QR, we need to determine it from sequence
                return response()->json([
                    'success' => false,
                    'message' => 'Part number not found in QR code. Please use complete QR format.'
                ]);
            }

            // Find matching content list using part_no and sequence logic
            $contentList = ContentList::where('case_id', $case->id)
                ->where('part_no', $boxData['part_no'])
                ->first();

            if (!$contentList) {
                return response()->json([
                    'success' => false,
                    'message' => 'Part number tidak ditemukan dalam content list!'
                ]);
            }

            $errorMessage = null;
            $scanRecord = null;

            // Use transaction with lock to prevent race condition
            DB::transaction(function () use ($case, $boxData, $contentList, &$errorMessage, &$scanRecord) {
                // FIXED: Check for duplicate scan using sequence and part_no
                $existingScan = ScanHistory::where('case_no', $case->case_no)
                    ->where('seq', $boxData['seq'])
                    ->where('part_no', $boxData['part_no'])
                    ->lockForUpdate()
                    ->first();

                if ($existingScan) {
                    $errorMessage = 'Box dengan sequence ' . $boxData['seq'] . ' untuk part ' . $boxData['part_no'] . ' sudah pernah discan!';
                    return;
                }

                // Record scan with sequence
                $scanRecord = ScanHistory::create([
                    'case_no' => $case->case_no,
                    'box_no' => $boxData['box_no'],
                    'part_no' => $boxData['part_no'],
                    'scanned_qty' => $boxData['quantity'],
                    'total_qty' => $boxData['total_qty'],
                    'seq' => $boxData['seq'],
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
                    'quantity' => $boxData['quantity']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    // FIXED: Update parseBoxQR method to handle new format
    private function parseBoxQR($qrCode)
    {
        // FIXED: Handle new barcode format
        // New format: I2A-SAN-00432-SA#23901-BZ140-00-87#00020#001-060#0#20250615#0#1B
        // Old format: BOX_01|32909-BZ100-00-87
        
        if (strpos($qrCode, '#') !== false) {
            // New format
            $parts = explode('#', $qrCode);
            
            if (count($parts) >= 4) {
                // Extract case number
                $possibleCaseNumbers = [];
                if (preg_match('/^([A-Z0-9-]{11,13})/', $parts[0], $matches)) {
                    $possibleCaseNumbers[] = $matches[1];
                }
                $possibleCaseNumbers[] = substr($parts[0], 0, 12);
                $possibleCaseNumbers[] = substr($parts[0], 0, 11);
                $possibleCaseNumbers[] = substr($parts[0], 0, 13);
                $caseNo = array_filter($possibleCaseNumbers)[0] ?? '';
                
                $partNo = $parts[1];
                $quantity = (int) $parts[2];
                $sequence = substr($parts[3], 0, 3);
                $totalSequence = substr($parts[3], -3);
                $totalQty = $quantity * (int) $totalSequence;
                
                // Generate box_no from sequence
                $sequenceInt = (int) $sequence;
                $boxNo = 'BOX_' . str_pad($sequenceInt, 2, '0', STR_PAD_LEFT);
                
                return [
                    'case_no' => $caseNo,
                    'box_no' => $boxNo,
                    'part_no' => $partNo,
                    'quantity' => $quantity,
                    'total_qty' => $totalQty,
                    'seq' => $sequence
                ];
            }
        } else {
            // Old format
            $separators = ['|', ':', '-', '_'];
            $parts = [];

            foreach ($separators as $separator) {
                if (strpos($qrCode, $separator) !== false) {
                    $parts = explode($separator, $qrCode, 2);
                    break;
                }
            }

            if (count($parts) >= 2) {
                return [
                    'case_no' => '',
                    'box_no' => trim($parts[0]),
                    'part_no' => trim($parts[1]),
                    'quantity' => 0,
                    'total_qty' => 0,
                    'seq' => ''
                ];
            }

            // If no separator found, assume it's just a box number
            return [
                'case_no' => '',
                'box_no' => trim($qrCode),
                'part_no' => '',
                'quantity' => 0,
                'total_qty' => 0,
                'seq' => ''
            ];
        }
        
        return [
            'case_no' => '',
            'box_no' => '',
            'part_no' => '',
            'quantity' => 0,
            'total_qty' => 0,
            'seq' => ''
        ];
    }

    public function markAsPacked(Request $request)
    {
        $request->validate([
            'case_no' => 'required|string'
        ]);

        try {
            $case = CaseModel::where('case_no', $request->case_no)->firstOrFail();

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