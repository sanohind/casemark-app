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
                    'message' => 'Box does not match content list! Box: ' . $boxData['box_no'] . ', Part: ' . $boxData['part_no']
                ]);
            }

            // Use transaction with lock to prevent race condition
            $scanRecord = null;
            $errorMessage = null;

            DB::transaction(function () use ($case, $boxData, $contentList, &$scanRecord, &$errorMessage) {
                $existingScan = ScanHistory::where('case_no', $case->case_no)
                    ->where('box_no', $boxData['box_no'])
                    ->where('part_no', $boxData['part_no'])
                    ->lockForUpdate()
                    ->first();

                if ($existingScan) {
                    $errorMessage = 'Box ' . $boxData['box_no'] . ' has already been scanned on ' . $existingScan->scanned_at->format('d/m/Y H:i');
                    return;
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
            });

            if ($errorMessage) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }

            if (!$scanRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to record scan. Please try again.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Box scanned successfully!',
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
                'message' => 'An error occurred: ' . $e->getMessage()
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
                    'message' => 'No boxes have been scanned for this case'
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
                'message' => 'Case ' . $case->case_no . ' successfully packed!',
                'data' => [
                    'case_no' => $case->case_no,
                    'packed_items' => $scannedCount,
                    'packing_date' => $jakartaTime->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }



    public function previewExcel(Request $request)
    {
        try {
            $request->validate(['excel_file' => 'required|mimes:xlsx,xls,csv']);

            $file = $request->file('excel_file');
            $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $file)[0];

            // Start from row 19 (index 18) according to the provided Excel format
            $startRow = 18; // Row 19 in Excel (0-based index)

            if (count($data) <= $startRow) {
                throw new \Exception('File Excel does not have enough data rows');
            }

            // Helper function to extract values based on patterns
            $extractValue = function ($row, $patterns, $valueColumnIndex = null, $cleanNumeric = false) {
                foreach ($row as $i => $cell) {
                    $cellValue = trim((string)$cell);
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $cellValue)) {
                            // If valueColumnIndex is not specified, take from the next column
                            $targetIndex = $valueColumnIndex !== null ? $valueColumnIndex : ($i + 1);
                            $value = trim((string)($row[$targetIndex] ?? ''));

                            // If cleanNumeric is true, clean the value from units like "KGS", "KG", etc.
                            if ($cleanNumeric) {
                                return preg_replace('/[^0-9.]/', '', $value);
                            }
                            return $value;
                        }
                    }
                }
                return '';
            };

            // Function to find values based on specific column positions
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

            // Get data from rows 19-25 (according to Excel format)
            $row19 = array_map('trim', $data[$startRow] ?? []); // Row 19
            $row20 = array_map('trim', $data[$startRow + 1] ?? []); // Row 20  
            $row21 = array_map('trim', $data[$startRow + 2] ?? []); // Row 21
            $row22 = array_map('trim', $data[$startRow + 3] ?? []); // Row 22
            $row23 = array_map('trim', $data[$startRow + 4] ?? []); // Row 23
            $row24 = array_map('trim', $data[$startRow + 5] ?? []); // Row 24
            $row25 = array_map('trim', $data[$startRow + 6] ?? []); // Row 25

            // Debug: Show row contents for troubleshooting
            Log::info('Excel Data Debug', [
                'row19' => $row19,
                'row20' => $row20,
                'row21' => $row21,
                'row22' => $row22,
                'row23' => $row23,
                'row24' => $row24,
                'row25' => $row25
            ]);

            // Extract data according to the provided Excel format
            // Destination - no need to clean numeric
            $destination = $extractValue($row19, ['/DESTINATION/i'], 3, false);
            if (empty($destination)) {
                $destination = trim((string)($row19[3] ?? '')); // Column D
            }

            // Case No - no need to clean numeric
            $caseNo = $extractValue($row19, ['/CASE NO\./i'], 11, false);
            if (empty($caseNo)) {
                $caseNo = trim((string)($row19[11] ?? '')); // Column L
            }

            // Case Size - no need to clean numeric
            $caseSize = $extractValue($row20, ['/C\/SIZE/i'], 11, false);
            if (empty($caseSize)) {
                $caseSize = trim((string)($row20[11] ?? '')); // Column L
            }

            // Order No - no need to clean numeric
            $orderNo = $extractValue($row22, ['/ORDER NO\./i'], 3, false);
            if (empty($orderNo)) {
                $orderNo = trim((string)($row22[3] ?? '')); // Column D
            }

            // Prod Month - no need to clean numeric
            $prodMonth = $extractValue($row23, ['/PROD\. MONTH/i'], 3, false);
            if (empty($prodMonth)) {
                $prodMonth = trim((string)($row23[3] ?? '')); // Column D
            }

            // Gross Weight - need to clean numeric
            $grossWeight = $extractValue($row21, ['/G\/W/i'], 11, true);
            if (empty($grossWeight)) {
                $grossWeight = $extractValueByPosition($row21, 11, true); // Column L
            }

            // Net Weight - need to clean numeric
            $netWeight = $extractValue($row22, ['/N\/W/i'], 11, true);
            if (empty($netWeight)) {
                $netWeight = $extractValueByPosition($row22, 11, true); // Column L
            }

            // Fallback: If still empty, try with more flexible patterns
            if (empty($caseNo)) {
                // Look for "CASE NO." pattern in row 19
                foreach ($row19 as $i => $cell) {
                    if (stripos($cell, 'CASE NO') !== false) {
                        $caseNo = trim((string)($row19[$i + 1] ?? ''));
                        break;
                    }
                }
            }

            if (empty($caseSize)) {
                // Look for "C/SIZE" pattern in row 20
                foreach ($row20 as $i => $cell) {
                    if (stripos($cell, 'C/SIZE') !== false) {
                        $caseSize = trim((string)($row20[$i + 1] ?? ''));
                        break;
                    }
                }
            }

            if (empty($orderNo)) {
                // Look for "ORDER NO." pattern in row 22
                foreach ($row22 as $i => $cell) {
                    if (stripos($cell, 'ORDER NO') !== false) {
                        $orderNo = trim((string)($row22[$i + 1] ?? ''));
                        break;
                    }
                }
            }

            if (empty($grossWeight)) {
                // Look for "G/W" pattern in row 21
                foreach ($row21 as $i => $cell) {
                    if (stripos($cell, 'G/W') !== false) {
                        $grossWeight = preg_replace('/[^0-9.]/', '', trim((string)($row21[$i + 1] ?? '')));
                        break;
                    }
                }
            }

            if (empty($netWeight)) {
                // Look for "N/W" pattern in row 22
                foreach ($row22 as $i => $cell) {
                    if (stripos($cell, 'N/W') !== false) {
                        $netWeight = preg_replace('/[^0-9.]/', '', trim((string)($row22[$i + 1] ?? '')));
                        break;
                    }
                }
            }

            // Final fallback: Try with different column positions
            if (empty($caseNo)) {
                // Try different columns for Case No (priority column L)
                for ($i = 11; $i <= 13; $i++) {
                    if (!empty($row19[$i])) {
                        $caseNo = trim((string)$row19[$i]);
                        break;
                    }
                }
            }

            if (empty($caseSize)) {
                // Try different columns for Case Size (priority column L)
                for ($i = 11; $i <= 13; $i++) {
                    if (!empty($row20[$i])) {
                        $caseSize = trim((string)$row20[$i]);
                        break;
                    }
                }
            }

            if (empty($orderNo)) {
                // Try different columns for Order No
                for ($i = 2; $i <= 6; $i++) {
                    if (!empty($row22[$i])) {
                        $orderNo = trim((string)$row22[$i]);
                        break;
                    }
                }
            }

            if (empty($grossWeight)) {
                // Try different columns for Gross Weight (priority column L)
                for ($i = 11; $i <= 13; $i++) {
                    if (!empty($row21[$i])) {
                        $grossWeight = preg_replace('/[^0-9.]/', '', trim((string)$row21[$i]));
                        break;
                    }
                }
            }

            if (empty($netWeight)) {
                // Try different columns for Net Weight (priority column L)
                for ($i = 11; $i <= 13; $i++) {
                    if (!empty($row22[$i])) {
                        $netWeight = preg_replace('/[^0-9.]/', '', trim((string)$row22[$i]));
                        break;
                    }
                }
            }

            // Parse content list data (rows 25 and below)
            $contentListData = [];
            $contentStartRow = 25; // Row 25 is the first data after header

            for ($i = $contentStartRow; $i < count($data); $i++) {
                $row = array_map('trim', $data[$i] ?? []);

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Parse content list data according to actual format
                $no = trim((string)($row[0] ?? '')); // Column 0 - NO.
                $boxNo = trim((string)($row[1] ?? '')); // Column 1 - BOX NO.
                $partNo = trim((string)($row[3] ?? '')); // Column 3 - PART NO. (not 2)
                $partName = trim((string)($row[4] ?? '')); // Column 4 - PART NAME (not 3)
                $quantity = trim((string)($row[8] ?? '0')); // Column 8 - QTY (not 4)
                $remark = trim((string)($row[5] ?? '')); // Column 5 - REMARK

                // If part_no is empty, use part_name as part_no
                if (empty($partNo) && !empty($partName)) {
                    $partNo = $partName;
                    $partName = ''; // Clear part_name because it's moved to part_no
                }

                // Validate required data - only box_no is mandatory
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

            // Check if case already exists
            $existingCase = null;
            $caseExists = false;
            $existingContentCount = 0;
            
            if (!empty($caseNo)) {
                $existingCase = CaseModel::where('case_no', $caseNo)->first();
                if ($existingCase) {
                    $caseExists = true;
                    $existingContentCount = $existingCase->contentLists()->count();
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
                    'content_list_preview' => $contentListData, // Preview all data
                    'case_exists' => $caseExists,
                    'existing_content_count' => $existingContentCount,
                    'warning_message' => $caseExists ? "Case No. {$caseNo} sudah pernah diupload sebelumnya dengan {$existingContentCount} item." : null
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
            $barcode = preg_replace('/[\x00-\x1F\x7F]/', '', $barcode);
    
            // TAMBAHKAN VALIDASI INI - Validate if this is actually a container barcode
            if (!$this->isContainerBarcode($barcode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid container barcode format. Please scan a container barcode, not a box barcode.'
                ], 400);
            }

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
                    'message' => 'Case not found: ' . $caseNo . '. Please check the barcode and try again.', 
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
            $barcode = preg_replace('/[\x00-\x1F\x7F]/', '', $barcode);
    
            // TAMBAHKAN VALIDASI INI - Validate if this is actually a box barcode
            if (!$this->isBoxBarcode($barcode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid box barcode format. Please scan a box barcode, not a container barcode.'
                ], 400);
            }

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

            // Calculate total quantity
            $totalQty = $quantity * (int) $totalSequence;

            // FIX: Convert sequence to integer first, then pad with 2 digits
            $sequenceInt = (int) $sequence; // Convert "002" to 2
            $boxNo = 'BOX_' . str_pad($sequenceInt, 2, '0', STR_PAD_LEFT); // Pad with 2 digits: "BOX_02"

            $scanHistory = null;
            $errorMessage = null;

            // Use transaction with lock to prevent race condition
            DB::transaction(function () use ($case, $sequence, $boxNo, $partNo, $quantity, $totalQty, &$scanHistory, &$errorMessage) {
                // FIX: Check for existing scan using both sequence AND part_no to prevent duplicate detection
                // when different parts have the same sequence number
                $existingScan = ScanHistory::where('case_no', $case->case_no)
                    ->where('seq', $sequence)
                    ->where('part_no', $partNo)
                    ->lockForUpdate()
                    ->first();

                if ($existingScan) {
                    $errorMessage = 'Box with sequence ' . $sequence . ' and part ' . $partNo . ' has already been scanned';
                    return;
                }

                // Create scan history record
                $scanHistory = ScanHistory::create([
                    'case_no' => $case->case_no,
                    'box_no' => $boxNo,
                    'part_no' => $partNo,
                    'scanned_qty' => $quantity,
                    'total_qty' => $totalQty,
                    'seq' => $sequence,
                    'status' => 'scanned'
                ]);
            });

            if ($errorMessage) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'Box scanned successfully',
                'data' => [
                    'scan_history' => $scanHistory,
                    'sequence' => $sequence,
                    'quantity' => $quantity,
                    'total_qty' => $totalQty,
                    'box_no' => $boxNo // For debugging
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
     * Scan final barcode to submit case
     */
    public function scanFinalBarcode(Request $request)
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

            // Check if barcode contains '#' (final barcode format) or not (container barcode format)
            $isFinalBarcodeFormat = strpos($barcode, '#') !== false;
            
            if ($isFinalBarcodeFormat) {
                // Final barcode format: I2A-SAN-00432-SA#CR-06PG_40#20250615#1B
                $parts = explode('#', $barcode);
                
                // Validasi minimal 3 parts (case_no#part_info#date#status)
                if (count($parts) < 3) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid final barcode format. Expected format: CASE_NO#PART_INFO#DATE#STATUS'
                    ], 400);
                }
                
                // Extract case number from first part
                $possibleCaseNumbers = [];
                if (preg_match('/^([A-Z0-9-]{11,13})/', $parts[0], $matches)) {
                    $possibleCaseNumbers[] = $matches[1];
                }
                $possibleCaseNumbers[] = substr($parts[0], 0, 12);
                $possibleCaseNumbers[] = substr($parts[0], 0, 11);
                $possibleCaseNumbers[] = substr($parts[0], 0, 13);
                $possibleCaseNumbers = array_unique(array_filter($possibleCaseNumbers));
                
                $case = null;
                $finalCaseNo = null;
                foreach ($possibleCaseNumbers as $possibleCaseNo) {
                    $case = CaseModel::where('case_no', $possibleCaseNo)->first();
                    if ($case) {
                        $finalCaseNo = $possibleCaseNo;
                        break;
                    }
                }
                
                if (!$case) {
                    $finalCaseNo = $possibleCaseNumbers[0] ?? substr($parts[0], 0, 12);
                    return response()->json([
                        'success' => false,
                        'message' => 'Case not found: ' . $finalCaseNo
                    ], 404);
                }
                
                // Optional: Validate date format if present (part index 2)
                if (isset($parts[2]) && !empty($parts[2])) {
                    $datePart = $parts[2];
                    if (!preg_match('/^\d{8}$/', $datePart)) { // Format: YYYYMMDD
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid date format in final barcode. Expected format: YYYYMMDD'
                        ], 400);
                    }
                }
            } else {
                // Container barcode format (same as scanContainer method)
                $possibleCaseNumbers = [];
                if (preg_match('/^([A-Z0-9-]{11,13})/', $barcode, $matches)) {
                    $possibleCaseNumbers[] = $matches[1];
                }
                $possibleCaseNumbers[] = substr($barcode, 0, 12);
                $possibleCaseNumbers[] = substr($barcode, 0, 11);
                $possibleCaseNumbers[] = substr($barcode, 0, 13);
                $possibleCaseNumbers = array_unique(array_filter($possibleCaseNumbers));
                
                $case = null;
                $finalCaseNo = null;
                foreach ($possibleCaseNumbers as $possibleCaseNo) {
                    $case = CaseModel::where('case_no', $possibleCaseNo)->first();
                    if ($case) {
                        $finalCaseNo = $possibleCaseNo;
                        break;
                    }
                }
                
                if (!$case) {
                    $finalCaseNo = $possibleCaseNumbers[0] ?? substr($barcode, 0, 12);
                    return response()->json([
                        'success' => false,
                        'message' => 'Case not found: ' . $finalCaseNo
                    ], 404);
                }
            }

            // Debug logging
            Log::info('Final barcode scan - Original barcode: ' . $barcode);
            Log::info('Final barcode scan - Barcode length: ' . strlen($barcode));
            Log::info('Final barcode scan - Is final format: ' . ($isFinalBarcodeFormat ? 'YES' : 'NO'));
            Log::info('Final barcode scan - Selected case number: ' . $finalCaseNo);

            // Verify case ID matches
            if ($case->id != $caseId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Final barcode case number does not match current case'
                ], 400);
            }

            // Check if case is already packed
            if ($case->status === 'packed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Case ' . $finalCaseNo . ' has already been packed'
                ], 400);
            }

            // Check if all boxes are scanned
            $contentLists = ContentList::where('case_id', $case->id)->get();
            $scanHistory = ScanHistory::where('case_no', $case->case_no)->get();

            $totalBoxes = $contentLists->count();
            $scannedBoxes = $scanHistory->count();

            if ($scannedBoxes < $totalBoxes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot submit case. Not all boxes have been scanned. (' . $scannedBoxes . '/' . $totalBoxes . ')'
                ], 400);
            }

            // Update case status with Jakarta timezone
            $jakartaTime = Carbon::now('Asia/Jakarta');

            $case->update([
                'status' => 'packed',
                'packing_date' => $jakartaTime
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Case ' . $case->case_no . ' successfully submitted via final barcode scan!',
                'data' => [
                    'case_no' => $case->case_no,
                    'packed_items' => $scannedBoxes,
                    'packing_date' => $jakartaTime->format('d/m/Y H:i:s'),
                    'submit_method' => 'final_barcode'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Final barcode scan error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing final barcode scan: ' . $e->getMessage()
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

            // Update case status with Jakarta timezone
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

            // Prepare scan progress data - FIX: Group by part_no to show progress for each part separately
            $scanProgress = $contentLists->groupBy('part_no')->map(function ($items) use ($scanHistory) {
                $totalQty = $items->sum('quantity');
                $scannedQty = $scanHistory->where('part_no', $items->first()->part_no)->sum('scanned_qty');
                return [
                    'part_no' => $items->first()->part_no,
                    'part_name' => $items->first()->part_name,
                    'quantity' => $items->first()->quantity, // Quantity per box
                    'progress' => $scannedQty . '/' . $totalQty
                ];
            })->values();

            // Prepare details data - FIX: Use correct logic to determine status
            $details = $contentLists->map(function ($content) use ($scanHistory) {
                // FIX: Search by combination of box_no AND part_no to prevent false detection
                // when different parts have the same box_no (sequence)
                $scannedBox = $scanHistory->where('box_no', $content->box_no)
                    ->where('part_no', $content->part_no)
                    ->first();

                $isScanned = $scannedBox ? true : false;

                return [
                    'box_no' => $content->box_no,
                    'part_no' => $content->part_no,
                    'part_name' => $content->part_name,
                    'quantity' => $content->quantity,
                    'status' => $isScanned, // Use boolean
                    'is_scanned' => $isScanned // Add this property for consistency with blade
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'progress' => $progress,
                    'scanProgress' => $scanProgress,
                    'details' => $details,
                    'scannedBoxes' => $scanHistory->count(), // Count based on number of scan_history records
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

    /**
 * Validate if barcode is container format
 */
private function isContainerBarcode($barcode)
{
    // Container format: I2A-SAN-00432-SA#CR-06PG_40#20250615#1B
    // Expected parts: 4 (case_no#part_info#date#status)
    $parts = explode('#', $barcode);
    
    // Container barcode should have exactly 4 parts
    if (count($parts) !== 4) {
        return false;
    }
    
    // Additional validation: second part should not be numeric (like part number)
    // Container's second part is like "CR-06PG_40", box's second part is like "23901-BZ140-00-87"
    $secondPart = $parts[1];
    
    // If second part starts with numbers only (like "23901"), it's likely a box barcode
    if (preg_match('/^\d+/', $secondPart)) {
        return false;
    }
    
    return true;
}

/**
 * Validate if barcode is box format
 */
private function isBoxBarcode($barcode)
{
    // Box format: I2A-SAN-00432-SA#23901-BZ140-00-87#00020#001-060#0#20250615#0#1B
    // Expected parts: 8 (case_no#part_no#quantity#sequence#unknown#date#unknown#status)
    $parts = explode('#', $barcode);
    
    // Box barcode should have exactly 8 parts
    if (count($parts) !== 8) {
        return false;
    }
    
    // Additional validation: second part should start with numbers (part number)
    $secondPart = $parts[1];
    
    // If second part starts with numbers (like "23901"), it's likely a box barcode
    if (!preg_match('/^\d+/', $secondPart)) {
        return false;
    }
    
    // Third part should be numeric quantity
    $thirdPart = $parts[2];
    if (!is_numeric($thirdPart)) {
        return false;
    }
    
    return true;
}
}
