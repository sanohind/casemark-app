<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\ContentList;
use App\Models\ScanHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $existingScan = ScanHistory::where('case_no', $case->case_no)
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
                'case_no' => $case->case_no,
                'box_no' => $boxData['box_no'],
                'part_no' => $boxData['part_no'],
                'scanned_qty' => $contentList->quantity,
                'total_qty' => $contentList->quantity,
                'status' => 'scanned'
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
            $scannedCount = ScanHistory::where('case_no', $case->case_no)
                ->where('status', 'scanned')
                ->count();

            if ($scannedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada box yang discan untuk case ini'
                ]);
            }

            // PERBAIKAN: Gunakan timezone Asia/Jakarta
            $jakartaTime = Carbon::now('Asia/Jakarta');

            // Update all scanned items to unscanned (since we're changing the enum)
            ScanHistory::where('case_no', $case->case_no)
                ->where('status', 'scanned')
                ->update([
                    'status' => 'unscanned'
                ]);

            // Update case status and packing_date
            $case->update([
                'status' => 'packed',
                'packing_date' => $jakartaTime
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Case ' . $case->case_no . ' berhasil dipacked!',
                'data' => [
                    'case_no' => $case->case_no,
                    'packed_items' => $scannedCount,
                    'packing_date' => $jakartaTime->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
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
                ]
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

    /**
     * Scan container barcode and return case information
     */
    public function scanContainer(Request $request)
    {
        try {
            $barcode = $request->input('barcode');

            if (!$barcode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Barcode is required'
                ], 400);
            }

            // Clean barcode from any control characters
            $barcode = trim($barcode);
            $barcode = preg_replace('/[\x00-\x1F\x7F]/', '', $barcode); // Remove control characters

            // Try multiple extraction methods
            $possibleCaseNumbers = [];

            // Method 1: Extract using regex pattern I2A-SAN-XXXXX
            if (preg_match('/^([A-Z0-9-]{11,13})/', $barcode, $matches)) {
                $possibleCaseNumbers[] = $matches[1];
            }

            // Method 2: Extract first 12 characters
            $possibleCaseNumbers[] = substr($barcode, 0, 12);

            // Method 3: Extract first 11 characters (in case scanner sends shorter)
            $possibleCaseNumbers[] = substr($barcode, 0, 11);

            // Method 4: Extract first 13 characters
            $possibleCaseNumbers[] = substr($barcode, 0, 13);

            // Remove duplicates and empty values
            $possibleCaseNumbers = array_unique(array_filter($possibleCaseNumbers));

            // Try to find case with any of the possible case numbers
            $case = null;
            $caseNo = null;

            foreach ($possibleCaseNumbers as $possibleCaseNo) {
                $case = CaseModel::where('case_no', $possibleCaseNo)->first();
                if ($case) {
                    $caseNo = $possibleCaseNo;
                    break;
                }
            }

            // If no case found, use the first possible case number for error message
            if (!$case) {
                $caseNo = $possibleCaseNumbers[0] ?? substr($barcode, 0, 12);
            }

            // Check if case is already packed
            if ($case && $case->status === 'packed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Case ' . $caseNo . ' has already been packed and cannot be scanned again'
                ], 400);
            }

            // Debug log
            Log::info('Container scan - Original barcode: ' . $barcode);
            Log::info('Container scan - Barcode length: ' . strlen($barcode));
            Log::info('Container scan - Barcode bytes: ' . bin2hex($barcode));
            Log::info('Container scan - Possible case numbers: ' . implode(', ', $possibleCaseNumbers));
            Log::info('Container scan - Selected case number: ' . $caseNo);
            Log::info('Container scan - Case found: ' . ($case ? 'YES' : 'NO'));

            if (!$case) {
                // Get all cases for debugging
                $allCases = CaseModel::all(['case_no']);
                $caseNumbers = $allCases->pluck('case_no')->toArray();

                Log::info('Container scan - All cases in database: ' . implode(', ', $caseNumbers));
                Log::info('Container scan - Searched case number: ' . $caseNo);
                Log::info('Container scan - Case number length: ' . strlen($caseNo));

                return response()->json([
                    'success' => false,
                    'message' => 'Case not found: ' . $caseNo . ' (Available: ' . implode(', ', $caseNumbers) . ')'
                ], 404);
            }

            // Get content lists for this case
            $contentLists = ContentList::where('case_id', $case->id)->get();

            // Get scan history for this case
            $scanHistory = ScanHistory::where('case_no', $case->case_no)->get();

            return response()->json([
                'success' => true,
                'message' => 'Container scanned successfully',
                'data' => [
                    'case' => $case,
                    'content_lists' => $contentLists,
                    'scan_history' => $scanHistory
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Container scan error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing container scan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scan box barcode and add to scan history
     */
    public function scanBox(Request $request)
    {
        try {
            $barcode = $request->input('barcode');
            $caseId = $request->input('case_id');

            if (!$barcode || !$caseId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Barcode and case_id are required'
                ], 400);
            }

            // Clean barcode from any control characters
            $barcode = trim($barcode);
            $barcode = preg_replace('/[\x00-\x1F\x7F]/', '', $barcode); // Remove control characters

            // Parse box barcode: I2A-SAN-00432-SA#23901-BZ140-00-87#00020#001-060#0#20250615#0#1B
            $parts = explode('#', $barcode);

            if (count($parts) < 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid box barcode format'
                ], 400);
            }

            // Extract case number using same method as container scan
            $possibleCaseNumbers = [];

            // Method 1: Extract using regex pattern I2A-SAN-XXXXX
            if (preg_match('/^([A-Z0-9-]{11,13})/', $parts[0], $matches)) {
                $possibleCaseNumbers[] = $matches[1];
            }

            // Method 2: Extract first 12 characters
            $possibleCaseNumbers[] = substr($parts[0], 0, 12);

            // Method 3: Extract first 11 characters
            $possibleCaseNumbers[] = substr($parts[0], 0, 11);

            // Method 4: Extract first 13 characters
            $possibleCaseNumbers[] = substr($parts[0], 0, 13);

            // Remove duplicates and empty values
            $possibleCaseNumbers = array_unique(array_filter($possibleCaseNumbers));

            // Try to find case with any of the possible case numbers
            $boxCaseNo = null;
            foreach ($possibleCaseNumbers as $possibleCaseNo) {
                $case = CaseModel::where('case_no', $possibleCaseNo)->first();
                if ($case) {
                    $boxCaseNo = $possibleCaseNo;
                    break;
                }
            }

            // If no case found, use the first possible case number for error message
            if (!$boxCaseNo) {
                $boxCaseNo = $possibleCaseNumbers[0] ?? substr($parts[0], 0, 12);
            }

            // Debug logging
            Log::info('Box scan - Original barcode: ' . $barcode);
            Log::info('Box scan - Parts: ' . implode(' | ', $parts));
            Log::info('Box scan - Possible case numbers: ' . implode(', ', $possibleCaseNumbers));
            Log::info('Box scan - Selected case number: ' . $boxCaseNo);

            $partNo = $parts[1]; // 23901-BZ140-00-87
            $quantity = (int) $parts[2]; // 00020
            $sequence = substr($parts[3], 0, 3); // 001 from 001-060
            $totalSequence = substr($parts[3], -3); // 060 from 001-060

            // Verify case number matches
            $case = CaseModel::find($caseId);
            Log::info('Box scan - Container case number: ' . ($case ? $case->case_no : 'NOT FOUND'));
            Log::info('Box scan - Box case number: ' . $boxCaseNo);
            Log::info('Box scan - Case numbers match: ' . ($case && $case->case_no === $boxCaseNo ? 'YES' : 'NO'));

            if (!$case || $case->case_no !== $boxCaseNo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Box case number does not match container case number. Container: ' . ($case ? $case->case_no : 'NOT FOUND') . ', Box: ' . $boxCaseNo
                ], 400);
            }

            // Check if box with this sequence already scanned
            $existingScan = ScanHistory::where('case_no', $case->case_no)
                ->where('seq', $sequence)
                ->first();

            if ($existingScan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Box with sequence ' . $sequence . ' has already been scanned'
                ], 409);
            }

            // Calculate total quantity
            $totalQty = $quantity * (int) $totalSequence;

            // PERBAIKAN: Konversi sequence ke integer dulu, lalu pad dengan 2 digit
            $sequenceInt = (int) $sequence; // Konversi "002" menjadi 2
            $boxNo = 'BOX_' . str_pad($sequenceInt, 2, '0', STR_PAD_LEFT); // Pad dengan 2 digit: "BOX_02"

            // Create scan history record
            $scanHistory = ScanHistory::create([
                'case_no' => $case->case_no,
                'box_no' => $boxNo, // Menggunakan variable yang sudah diperbaiki
                'part_no' => $partNo,
                'scanned_qty' => $quantity,
                'total_qty' => $totalQty,
                'seq' => $sequence,
                'status' => 'scanned'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Box scanned successfully',
                'data' => [
                    'scan_history' => $scanHistory,
                    'sequence' => $sequence,
                    'quantity' => $quantity,
                    'total_qty' => $totalQty,
                    'box_no' => $boxNo // Untuk debugging
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Box scan error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing box scan: ' . $e->getMessage()
            ], 500);
        }
    }




    /**
     * Submit case when all items are scanned
     */
    public function submitCase(Request $request)
    {
        try {
            $caseNo = $request->input('case_no');

            if (!$caseNo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Case number is required'
                ], 400);
            }

            $case = CaseModel::where('case_no', $caseNo)->first();

            if (!$case) {
                return response()->json([
                    'success' => false,
                    'message' => 'Case not found'
                ], 404);
            }

            // Update case status dengan timezone Jakarta
            $case->update([
                'status' => 'packed',
                'packing_date' => Carbon::now('Asia/Jakarta')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Case submitted successfully. You can continue scanning other containers.'
            ]);
        } catch (\Exception $e) {
            Log::error('Case submit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error submitting case: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getCaseProgress($caseId)
    {
        try {
            $case = CaseModel::find($caseId);

            if (!$case) {
                return response()->json([
                    'success' => false,
                    'message' => 'Case not found'
                ], 404);
            }

            $contentLists = ContentList::where('case_id', $case->id)->get();
            $scanHistory = ScanHistory::where('case_no', $case->case_no)->get();

            // Calculate progress
            $totalScanned = $scanHistory->sum('scanned_qty');
            $totalExpected = $contentLists->sum('quantity');
            $progress = $totalExpected > 0 ? $totalScanned . '/' . $totalExpected : '0/0';

            // Prepare scan progress data
            $scanProgress = [
                'part_no' => $contentLists->first()->part_no ?? 'N/A',
                'part_name' => $contentLists->first()->part_name ?? 'N/A',
                'quantity' => $contentLists->first()->quantity ?? 0, // Quantity per box
                'progress' => $progress
            ];

            // Prepare details data - PERBAIKAN: Gunakan logika yang benar untuk menentukan status
            $details = $contentLists->map(function ($content) use ($scanHistory) {
                // PERBAIKAN: Cari berdasarkan box_no saja karena dari scan box, 
                // box_no dibuat dari sequence dan part_no dari barcode mungkin berbeda format
                $scannedBox = $scanHistory->where('box_no', $content->box_no)->first();

                // Jika tidak ditemukan berdasarkan box_no, coba cari berdasarkan kombinasi box_no dan part_no
                if (!$scannedBox) {
                    $scannedBox = $scanHistory->where('box_no', $content->box_no)
                        ->where('part_no', $content->part_no)
                        ->first();
                }

                $isScanned = $scannedBox ? true : false;

                return [
                    'box_no' => $content->box_no,
                    'part_no' => $content->part_no,
                    'part_name' => $content->part_name,
                    'quantity' => $content->quantity,
                    'status' => $isScanned, // Gunakan boolean
                    'is_scanned' => $isScanned // Tambahkan property ini untuk konsistensi dengan blade
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'progress' => $progress,
                    'scanProgress' => $scanProgress,
                    'details' => $details,
                    'scannedBoxes' => $scanHistory->count(), // Hitung berdasarkan jumlah record scan_history
                    'totalBoxes' => $contentLists->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get case progress error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting case progress: ' . $e->getMessage()
            ], 500);
        }
    }
}
