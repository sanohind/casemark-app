<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanHistory extends Model
{
    use HasFactory;

    protected $table = 'scan_history';
    
    protected $fillable = [
        'case_id',
        'box_no',
        'part_no',
        'scanned_qty',
        'total_qty',
        'status',
        'packing_date',
        'scanned_by'
    ];

    protected $dates = [
        'packing_date',
        'scanned_at'
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function getProgressAttribute()
    {
        return "{$this->scanned_qty}/{$this->total_qty}";
    }
}
