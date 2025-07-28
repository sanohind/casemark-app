<?php

namespace App\Imports;

use App\Models\ContentList;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ContentListImport implements ToModel, WithStartRow
{
    private $caseId;
    private $startRow = 26; // Mulai dari baris 26 (setelah header tabel)

    public function __construct($caseId)
    {
        $this->caseId = $caseId;
    }

    public function startRow(): int
    {
        return $this->startRow;
    }

    public function model(array $row)
    {
        // Skip baris kosong atau baris yang tidak memiliki data
        if (empty(array_filter($row))) {
            return null;
        }

        // Parse data berdasarkan format Excel yang sebenarnya
        // Format berdasarkan log: NO. | BOX NO. | PART NO. | PART NAME | QTY | REMARK
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
        if (empty($boxNo)) {
            Log::info('Skipping row due to missing box_no:', [
                'row' => $row,
                'box_no' => $boxNo
            ]);
            return null;
        }

        // Convert quantity to integer and ensure it's not negative
        $quantity = max(0, (int)$quantity);

        // Log the processed row for debugging
        Log::info('Processing Content List Row:', [
            'original_row' => $row,
            'processed_data' => [
                'no' => $no,
                'box_no' => $boxNo,
                'part_no' => $partNo,
                'part_name' => $partName,
                'quantity' => $quantity,
                'remark' => $remark
            ],
            'row_length' => count($row),
            'all_columns' => array_values($row)
        ]);

        return new ContentList([
            'case_id' => $this->caseId,
            'box_no' => $boxNo,
            'part_no' => $partNo,
            'part_name' => $partName,
            'quantity' => $quantity,
            'remark' => $remark
        ]);
    }
}