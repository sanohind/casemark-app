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
            
            return view('casemark.content-list', compact('case', 'contentLists'));
        }
        
        return view('casemark.content-list');
    }

    // History Management  
    public function history()
    {
        $histories = ScanHistory::with('case')
                               ->orderBy('scanned_at', 'desc')
                               ->paginate(10);
        
        return view('casemark.history', compact('histories'));
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
            // Create or update case
            $case = CaseModel::updateOrCreate(
                ['case_no' => $request->case_no],
                [
                    'destination' => $request->destination,
                    'order_no' => $request->order_no ?? '0',
                    'prod_month' => $request->prod_month,
                    'case_size' => $request->case_size,
                    'gross_weight' => $request->gross_weight,
                    'net_weight' => $request->net_weight,
                    'status' => 'active'
                ]
            );

            // Clear existing content lists
            $case->contentLists()->delete();

            // Import Excel data
            Excel::import(new ContentListImport($case->id), $request->file('excel_file'));

            return redirect()->route('casemark.content-list', $case->case_no)
                           ->with('success', 'Excel berhasil diupload!');
                           
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // List Case Mark
    public function listCaseMark()
    {
        $cases = CaseModel::with(['contentLists', 'scanHistory'])
                         ->orderBy('updated_at', 'desc')
                         ->paginate(10);
        
        return view('casemark.list-case-mark', compact('cases'));
    }

    // Scan Operations
    public function scanContainer()
    {
        return view('casemark.scan-container');
    }

    public function scanBox(Request $request)
    {
        $caseNo = $request->case_no;
        
        if (!$caseNo) {
            return redirect()->route('casemark.scan-container')
                           ->with('error', 'Silakan scan container terlebih dahulu');
        }

        $case = CaseModel::where('case_no', $caseNo)->first();
        
        if (!$case) {
            return redirect()->route('casemark.scan-container')
                           ->with('error', 'Container tidak ditemukan');
        }

        return view('casemark.scan-box', compact('case'));
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

            // Check if already scanned
            $existingScan = ScanHistory::where('case_id', $case->id)
                                     ->where('box_no', $boxData['box_no'])
                                     ->where('part_no', $boxData['part_no'])
                                     ->first();

            if ($existingScan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Box sudah pernah discan!'
                ]);
            }

            // Record scan
            ScanHistory::create([
                'case_id' => $case->id,
                'box_no' => $boxData['box_no'],
                'part_no' => $boxData['part_no'],
                'scanned_qty' => $contentList->quantity,
                'total_qty' => $contentList->quantity,
                'status' => 'scanned',
                'scanned_by' => auth()->user()->name ?? 'Unknown'
            ]);

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
            
            // Update all scanned items to packed
            ScanHistory::where('case_id', $case->id)
                      ->where('status', 'scanned')
                      ->update([
                          'status' => 'packed',
                          'packing_date' => Carbon::now()
                      ]);

            // Update case status
            $case->update(['status' => 'packed']);

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