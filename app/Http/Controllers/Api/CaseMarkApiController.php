<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\ContentList;
use App\Models\ScanHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CaseMarkApiController extends Controller
{
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
            
            if (!$boxData['box_no'] || !$boxData['part_no']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code format. Expected format: BOX_01|32909-BZ100-00-87'
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
                    'message' => 'Box tidak sesuai dengan content list! Box: ' . $boxData['box_no'] . ', Part: ' . $boxData['part_no']
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
                    'message' => 'Box ' . $boxData['box_no'] . ' sudah pernah discan pada ' . $existingScan->scanned_at->format('d/m/Y H:i')
                ]);
            }

            // Record scan
            $scanRecord = ScanHistory::create([
                'case_id' => $case->id,
                'box_no' => $boxData['box_no'],
                'part_no' => $boxData['part_no'],
                'scanned_qty' => $contentList->quantity,
                'total_qty' => $contentList->quantity,
                'status' => 'scanned',
                'scanned_by' => $request->user()->name ?? 'System'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Box berhasil discan!',
                'data' => [
                    'box_no' => $boxData['box_no'],
                    'part_no' => $boxData['part_no'],
                    'part_name' => $contentList->part_name,
                    'quantity' => $contentList->quantity,
                    'scan_time' => $scanRecord->scanned_at->format('d/m/Y H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAsPacked(Request $request)
    {
        $request->validate([
            'case_no' => 'required|string'
        ]);

        try {
            $case = CaseModel::where('case_no', $request->case_no)->firstOrFail();
            
            // Check if there are scanned items
            $scannedCount = ScanHistory::where('case_id', $case->id)
                                     ->where('status', 'scanned')
                                     ->count();
            
            if ($scannedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada box yang discan untuk case ini'
                ]);
            }
            
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
                'message' => 'Case ' . $case->case_no . ' berhasil dipacked!',
                'data' => [
                    'case_no' => $case->case_no,
                    'packed_items' => $scannedCount,
                    'packing_date' => Carbon::now()->format('d/m/Y H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getContainerInfo($caseNo)
    {
        try {
            $case = CaseModel::with(['contentLists', 'scanHistory'])->where('case_no', $caseNo)->firstOrFail();
            
            $totalBoxes = $case->contentLists()->count();
            $scannedBoxes = $case->scanHistory()->distinct('box_no')->count();
            $totalQuantity = $case->contentLists()->sum('quantity');
            $scannedQuantity = $case->scanHistory()->sum('scanned_qty');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'case_no' => $case->case_no,
                    'destination' => $case->destination,
                    'order_no' => $case->order_no,
                    'prod_month' => $case->prod_month,
                    'case_size' => $case->case_size,
                    'gross_weight' => $case->gross_weight,
                    'net_weight' => $case->net_weight,
                    'status' => $case->status,
                    'progress' => "{$scannedBoxes}/{$totalBoxes}",
                    'total_boxes' => $totalBoxes,
                    'scanned_boxes' => $scannedBoxes,
                    'total_quantity' => $totalQuantity,
                    'scanned_quantity' => $scannedQuantity,
                    'completion_percentage' => $totalBoxes > 0 ? round(($scannedBoxes / $totalBoxes) * 100, 2) : 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Container tidak ditemukan'
            ], 404);
        }
    }

    public function getStats()
    {
        try {
            $totalCases = CaseModel::count();
            $activeCases = CaseModel::where('status', 'active')->count();
            $packedCases = CaseModel::where('status', 'packed')->count();
            $shippedCases = CaseModel::where('status', 'shipped')->count();
            
            $totalScans = ScanHistory::count();
            $todayScans = ScanHistory::whereDate('scanned_at', Carbon::today())->count();
            
            $recentActivity = ScanHistory::with('case')
                                       ->latest('scanned_at')
                                       ->take(10)
                                       ->get()
                                       ->map(function ($scan) {
                                           return [
                                               'case_no' => $scan->case->case_no,
                                               'box_no' => $scan->box_no,
                                               'part_no' => $scan->part_no,
                                               'scanned_at' => $scan->scanned_at->format('d/m/Y H:i'),
                                               'scanned_by' => $scan->scanned_by
                                           ];
                                       });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'cases' => [
                        'total' => $totalCases,
                        'active' => $activeCases,
                        'packed' => $packedCases,
                        'shipped' => $shippedCases
                    ],
                    'scans' => [
                        'total' => $totalScans,
                        'today' => $todayScans
                    ],
                    'recent_activity' => $recentActivity
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics'
            ], 500);
        }
    }

    public function getCaseStats($caseNo)
    {
        try {
            $case = CaseModel::where('case_no', $caseNo)->firstOrFail();
            $containerInfo = $this->getContainerInfo($caseNo);
            
            $scanHistory = ScanHistory::where('case_id', $case->id)
                                    ->with('case')
                                    ->orderBy('scanned_at', 'desc')
                                    ->get()
                                    ->map(function ($scan) {
                                        return [
                                            'box_no' => $scan->box_no,
                                            'part_no' => $scan->part_no,
                                            'quantity' => $scan->scanned_qty,
                                            'status' => $scan->status,
                                            'scanned_at' => $scan->scanned_at->format('d/m/Y H:i:s'),
                                            'scanned_by' => $scan->scanned_by,
                                            'packing_date' => $scan->packing_date ? $scan->packing_date->format('d/m/Y H:i:s') : null
                                        ];
                                    });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'container_info' => $containerInfo->getData()->data,
                    'scan_history' => $scanHistory
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Case tidak ditemukan'
            ], 404);
        }
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all'); // all, cases, parts, boxes
        
        $results = [];
        
        if ($type === 'all' || $type === 'cases') {
            $cases = CaseModel::where('case_no', 'LIKE', "%{$query}%")
                            ->orWhere('destination', 'LIKE', "%{$query}%")
                            ->orWhere('prod_month', 'LIKE', "%{$query}%")
                            ->take(10)
                            ->get(['case_no', 'destination', 'prod_month', 'status']);
            
            $results['cases'] = $cases;
        }
        
        if ($type === 'all' || $type === 'parts') {
            $parts = ContentList::where('part_no', 'LIKE', "%{$query}%")
                              ->orWhere('part_name', 'LIKE', "%{$query}%")
                              ->with('case:id,case_no')
                              ->take(10)
                              ->get(['part_no', 'part_name', 'case_id']);
            
            $results['parts'] = $parts;
        }
        
        if ($type === 'all' || $type === 'boxes') {
            $boxes = ScanHistory::where('box_no', 'LIKE', "%{$query}%")
                               ->with('case:id,case_no')
                               ->take(10)
                               ->get(['box_no', 'part_no', 'case_id', 'status', 'scanned_at']);
            
            $results['boxes'] = $boxes;
        }
        
        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    private function parseBoxQR($qrCode)
    {
        // Support multiple QR formats:
        // Format 1: BOX_01|32909-BZ100-00-87
        // Format 2: BOX_01:32909-BZ100-00-87
        // Format 3: Just box number (will need manual part selection)
        
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
                'box_no' => trim($parts[0]),
                'part_no' => trim($parts[1])
            ];
        }
        
        // If no separator found, assume it's just a box number
        return [
            'box_no' => trim($qrCode),
            'part_no' => '' // Will need to be determined from content list
        ];
    }
} 