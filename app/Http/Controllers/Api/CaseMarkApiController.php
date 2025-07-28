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

    public function previewExcel(Request $request)
    {
        try {
            $request->validate(['excel_file' => 'required|mimes:xlsx,xls,csv']);

            $file = $request->file('excel_file');
            $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $file)[0];

            // Mulai dari baris 19 (index 18) sesuai dengan format Excel yang diberikan
            $startRow = 18; // Baris 19 dalam Excel (0-based index)
            
            if (count($data) <= $startRow) {
                throw new \Exception('File Excel tidak memiliki cukup baris data');
            }

            // Fungsi helper untuk ekstrak nilai berdasarkan pola
            $extractValue = function ($row, $patterns, $valueColumnIndex = null, $cleanNumeric = false) {
                foreach ($row as $i => $cell) {
                    $cellValue = trim((string)$cell);
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $cellValue)) {
                            // Jika valueColumnIndex tidak ditentukan, ambil dari kolom berikutnya
                            $targetIndex = $valueColumnIndex !== null ? $valueColumnIndex : ($i + 1);
                            $value = trim((string)($row[$targetIndex] ?? ''));
                            
                            // Jika cleanNumeric true, bersihkan nilai dari unit seperti "KGS", "KG", dll
                            if ($cleanNumeric) {
                                return preg_replace('/[^0-9.]/', '', $value);
                            }
                            return $value;
                        }
                    }
                }
                return '';
            };

            // Fungsi untuk mencari nilai berdasarkan posisi kolom yang spesifik
            $extractValueByPosition = function ($row, $columnIndex, $cleanNumeric = false) {
                if (isset($row[$columnIndex])) {
                    $value = trim((string)$row[$columnIndex]);
                    if ($cleanNumeric) {
                        return preg_replace('/[^0-9.]/', '', $value);
                    }
                    return $value;
                }
                return '';
            };

            // Ambil data dari baris 19-25 (sesuai dengan format Excel)
            $row19 = array_map('trim', $data[$startRow] ?? []); // Baris 19
            $row20 = array_map('trim', $data[$startRow + 1] ?? []); // Baris 20  
            $row21 = array_map('trim', $data[$startRow + 2] ?? []); // Baris 21
            $row22 = array_map('trim', $data[$startRow + 3] ?? []); // Baris 22
            $row23 = array_map('trim', $data[$startRow + 4] ?? []); // Baris 23
            $row24 = array_map('trim', $data[$startRow + 5] ?? []); // Baris 24
            $row25 = array_map('trim', $data[$startRow + 6] ?? []); // Baris 25

            // Debug: Tampilkan isi baris untuk troubleshooting
            \Log::info('Excel Data Debug', [
                'row19' => $row19,
                'row20' => $row20,
                'row21' => $row21,
                'row22' => $row22,
                'row23' => $row23,
                'row24' => $row24,
                'row25' => $row25
            ]);

            // Ekstrak data berdasarkan format Excel yang diberikan
            // Destination - tidak perlu clean numeric
            $destination = $extractValue($row19, ['/DESTINATION/i'], 3, false);
            if (empty($destination)) {
                $destination = trim((string)($row19[3] ?? '')); // Kolom D
            }

            // Case No - tidak perlu clean numeric
            $caseNo = $extractValue($row19, ['/CASE NO\./i'], 11, false);
            if (empty($caseNo)) {
                $caseNo = trim((string)($row19[11] ?? '')); // Kolom L
            }

            // Case Size - tidak perlu clean numeric
            $caseSize = $extractValue($row20, ['/C\/SIZE/i'], 11, false);
            if (empty($caseSize)) {
                $caseSize = trim((string)($row20[11] ?? '')); // Kolom L
            }

            // Order No - tidak perlu clean numeric
            $orderNo = $extractValue($row22, ['/ORDER NO\./i'], 3, false);
            if (empty($orderNo)) {
                $orderNo = trim((string)($row22[3] ?? '')); // Kolom D
            }

            // Prod Month - tidak perlu clean numeric
            $prodMonth = $extractValue($row23, ['/PROD\. MONTH/i'], 3, false);
            if (empty($prodMonth)) {
                $prodMonth = trim((string)($row23[3] ?? '')); // Kolom D
            }

            // Gross Weight - perlu clean numeric
            $grossWeight = $extractValue($row21, ['/G\/W/i'], 11, true);
            if (empty($grossWeight)) {
                $grossWeight = $extractValueByPosition($row21, 11, true); // Kolom L
            }

            // Net Weight - perlu clean numeric
            $netWeight = $extractValue($row22, ['/N\/W/i'], 11, true);
            if (empty($netWeight)) {
                $netWeight = $extractValueByPosition($row22, 11, true); // Kolom L
            }

            // Fallback: Jika masih kosong, coba dengan pola yang lebih fleksibel
            if (empty($caseNo)) {
                // Cari pola "CASE NO." di baris 19
                foreach ($row19 as $i => $cell) {
                    if (stripos($cell, 'CASE NO') !== false) {
                        $caseNo = trim((string)($row19[$i + 1] ?? ''));
                        break;
                    }
                }
            }

            if (empty($caseSize)) {
                // Cari pola "C/SIZE" di baris 20
                foreach ($row20 as $i => $cell) {
                    if (stripos($cell, 'C/SIZE') !== false) {
                        $caseSize = trim((string)($row20[$i + 1] ?? ''));
                        break;
                    }
                }
            }

            if (empty($orderNo)) {
                // Cari pola "ORDER NO." di baris 22
                foreach ($row22 as $i => $cell) {
                    if (stripos($cell, 'ORDER NO') !== false) {
                        $orderNo = trim((string)($row22[$i + 1] ?? ''));
                        break;
                    }
                }
            }

            if (empty($grossWeight)) {
                // Cari pola "G/W" di baris 21
                foreach ($row21 as $i => $cell) {
                    if (stripos($cell, 'G/W') !== false) {
                        $grossWeight = preg_replace('/[^0-9.]/', '', trim((string)($row21[$i + 1] ?? '')));
                        break;
                    }
                }
            }

            if (empty($netWeight)) {
                // Cari pola "N/W" di baris 22
                foreach ($row22 as $i => $cell) {
                    if (stripos($cell, 'N/W') !== false) {
                        $netWeight = preg_replace('/[^0-9.]/', '', trim((string)($row22[$i + 1] ?? '')));
                        break;
                    }
                }
            }

            // Final fallback: Coba dengan posisi kolom yang berbeda
            if (empty($caseNo)) {
                // Coba kolom yang berbeda untuk Case No (prioritas kolom L)
                for ($i = 11; $i <= 13; $i++) {
                    if (!empty($row19[$i])) {
                        $caseNo = trim((string)$row19[$i]);
                        break;
                    }
                }
            }

            if (empty($caseSize)) {
                // Coba kolom yang berbeda untuk Case Size (prioritas kolom L)
                for ($i = 11; $i <= 13; $i++) {
                    if (!empty($row20[$i])) {
                        $caseSize = trim((string)$row20[$i]);
                        break;
                    }
                }
            }

            if (empty($orderNo)) {
                // Coba kolom yang berbeda untuk Order No
                for ($i = 2; $i <= 6; $i++) {
                    if (!empty($row22[$i])) {
                        $orderNo = trim((string)$row22[$i]);
                        break;
                    }
                }
            }

            if (empty($grossWeight)) {
                // Coba kolom yang berbeda untuk Gross Weight (prioritas kolom L)
                for ($i = 11; $i <= 13; $i++) {
                    if (!empty($row21[$i])) {
                        $grossWeight = preg_replace('/[^0-9.]/', '', trim((string)$row21[$i]));
                        break;
                    }
                }
            }

            if (empty($netWeight)) {
                // Coba kolom yang berbeda untuk Net Weight (prioritas kolom L)
                for ($i = 11; $i <= 13; $i++) {
                    if (!empty($row22[$i])) {
                        $netWeight = preg_replace('/[^0-9.]/', '', trim((string)$row22[$i]));
                        break;
                    }
                }
            }

            // Parse content list data (baris 25 ke bawah)
            $contentListData = [];
            $contentStartRow = 25; // Baris 25 adalah data pertama setelah header
            
            // Debug: Log data dari baris 25-35 untuk melihat data yang sebenarnya
            \Log::info('Content List Preview Debug - Rows 25-35:', [
                'row25' => array_map('trim', $data[25] ?? []),
                'row26' => array_map('trim', $data[26] ?? []),
                'row27' => array_map('trim', $data[27] ?? []),
                'row28' => array_map('trim', $data[28] ?? []),
                'row29' => array_map('trim', $data[29] ?? []),
                'row30' => array_map('trim', $data[30] ?? []),
                'row31' => array_map('trim', $data[31] ?? []),
                'row32' => array_map('trim', $data[32] ?? []),
                'row33' => array_map('trim', $data[33] ?? []),
                'row34' => array_map('trim', $data[34] ?? []),
                'row35' => array_map('trim', $data[35] ?? [])
            ]);
            
            for ($i = $contentStartRow; $i < count($data); $i++) {
                $row = array_map('trim', $data[$i] ?? []);
                
                // Skip baris kosong
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Parse data content list berdasarkan format yang sebenarnya
                $no = trim((string)($row[0] ?? '')); // Kolom 0 - NO.
                $boxNo = trim((string)($row[1] ?? '')); // Kolom 1 - BOX NO.
                $partNo = trim((string)($row[3] ?? '')); // Kolom 3 - PART NO. (bukan 2)
                $partName = trim((string)($row[4] ?? '')); // Kolom 4 - PART NAME (bukan 3)
                $quantity = trim((string)($row[8] ?? '0')); // Kolom 8 - QTY (bukan 4)
                $remark = trim((string)($row[5] ?? '')); // Kolom 5 - REMARK
                
                // Jika part_no kosong, gunakan part_name sebagai part_no
                if (empty($partNo) && !empty($partName)) {
                    $partNo = $partName;
                    $partName = ''; // Kosongkan part_name karena sudah dipindah ke part_no
                }
                
                // Validasi data yang diperlukan - hanya box_no yang wajib
                if (!empty($boxNo)) {
                    $contentListData[] = [
                        'no' => $no,
                        'box_no' => $boxNo,
                        'part_no' => $partNo,
                        'part_name' => $partName,
                        'quantity' => $quantity,
                        'remark' => $remark
                    ];
                }
            }

            // Debug information (akan dihapus di production)
            $debugInfo = [
                'row19' => $row19,
                'row20' => $row20,
                'row21' => $row21,
                'row22' => $row22,
                'row23' => $row23,
                'extracted_values' => [
                    'destination' => $destination,
                    'case_no' => $caseNo,
                    'case_size' => $caseSize,
                    'order_no' => $orderNo,
                    'prod_month' => $prodMonth,
                    'gross_weight' => $grossWeight,
                    'net_weight' => $netWeight
                ],
                'column_mapping' => [
                    'destination' => 'Kolom D (index 3)',
                    'case_no' => 'Kolom L (index 11)',
                    'case_size' => 'Kolom L (index 11)',
                    'order_no' => 'Kolom D (index 3)',
                    'prod_month' => 'Kolom D (index 3)',
                    'gross_weight' => 'Kolom L (index 11)',
                    'net_weight' => 'Kolom L (index 11)'
                ],
                'content_list_preview' => array_slice($contentListData, 0, 10) // Preview 10 item pertama
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'destination' => $destination,
                    'case_no' => $caseNo,
                    'case_size' => $caseSize,
                    'order_no' => $orderNo,
                    'prod_month' => $prodMonth,
                    'gross_weight' => $grossWeight,
                    'net_weight' => $netWeight,
                    'content_list_preview' => $contentListData // Preview semua data
                ],
                'debug' => $debugInfo // Hapus ini di production
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 400);
        }
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