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

    public function previewExcel(Request $request)
    {
        try {
            $request->validate(['excel_file' => 'required|mimes:xlsx,xls,csv']);

            $file = $request->file('excel_file');
            $data = Excel::toArray([], $file)[0];

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
                $caseNo = trim((string)($row19[11] ?? '')); // Kolom K
            }

            // Case Size - tidak perlu clean numeric
            $caseSize = $extractValue($row20, ['/C\/SIZE/i'], 11, false);
            if (empty($caseSize)) {
                $caseSize = trim((string)($row20[11] ?? '')); // Kolom K
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
                $grossWeight = $extractValueByPosition($row21, 11, true); // Kolom K
            }

            // Net Weight - perlu clean numeric
            $netWeight = $extractValue($row22, ['/N\/W/i'], 11, true);
            if (empty($netWeight)) {
                $netWeight = $extractValueByPosition($row22, 11, true); // Kolom K
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

    private function extractValueFromRow($row, $headerText)
    {
        if (empty($row)) return '';

        $headerIndex = null;
        $headerText = strtoupper($headerText);

        // Cari kolom yang mengandung teks header
        foreach ($row as $index => $cell) {
            $cellValue = strtoupper(trim($cell ?? ''));
            if (str_contains($cellValue, $headerText)) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) return '';

        // Nilai biasanya ada di kolom berikutnya
        $valueIndex = $headerIndex + 1;
        return $row[$valueIndex] ?? '';
    }

    private function findValue($data, $possibleHeaders)
    {
        foreach ($data as $row) {
            foreach ($row as $cell) {
                $cellValue = strtoupper(trim($cell));
                foreach ($possibleHeaders as $header) {
                    if (strpos($cellValue, strtoupper($header)) !== false) {
                        // Ambil nilai di kolom berikutnya
                        $nextCell = next($row);
                        return $nextCell ? trim($nextCell) : '';
                    }
                }
            }
        }
        return '';
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
                    'destination' => (string)$request->destination,
                    'order_no' => (string)$request->order_no,
                    'prod_month' => (string)$request->prod_month,
                    'case_size' => (string)$request->case_size,
                    'gross_weight' => (float)$request->gross_weight,
                    'net_weight' => (float)$request->net_weight,
                    'status' => 'active'
                ]
            );

            // Clear existing content lists
            $case->contentLists()->delete();

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

            return redirect()->route('casemark.content-list', $case->case_no)
                ->with('success', "Excel berhasil diupload! {$importedCount} item berhasil diimport.");
        } catch (\Exception $e) {
            Log::error('Excel import failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
                'scanned_by' => Auth::check() ? Auth::user()->name : 'Unknown'
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

    private function extractValue($row, $possibleHeaders, $valueColumnIndex)
    {
        if (!$row) return '';

        // First, try to find the header column
        $headerColumnIndex = null;
        foreach ($row as $index => $value) {
            $value = trim((string)$value);
            foreach ($possibleHeaders as $header) {
                if (stripos($value, $header) !== false) {
                    $headerColumnIndex = $index;
                    break 2;
                }
            }
        }

        if ($headerColumnIndex !== null) {
            // If we found the header, get the value from the specified column
            $value = trim((string)($row[$valueColumnIndex] ?? ''));
            // Remove common prefixes/suffixes like ":", "KGS", etc.
            return trim(str_replace([':', 'KGS', 'KG'], '', $value));
        }

        // If no header found, try to get value directly from the specified column
        return trim(str_replace([':', 'KGS', 'KG'], '', (string)($row[$valueColumnIndex] ?? '')));
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
