<?php

namespace App\Imports;

use App\Models\ContentList;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContentListImport implements ToModel, WithHeadingRow
{
    private $caseId;

    public function __construct($caseId)
    {
        $this->caseId = $caseId;
    }

    public function model(array $row)
    {
        return new ContentList([
            'case_id' => $this->caseId,
            'box_no' => $row['box_no'] ?? '',
            'part_no' => $row['part_no'] ?? '',
            'part_name' => $row['part_name'] ?? '',
            'quantity' => (int)($row['quantity'] ?? 0),
            'remark' => $row['remark'] ?? ''
        ]);
    }
}