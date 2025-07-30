<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanHistory extends Model
{
    use HasFactory;

    protected $table = 'scan_history';
    
    // Disable timestamps since we don't have created_at and updated_at columns
    public $timestamps = false;
    
    protected $fillable = [
        'case_id',
        'box_no',
        'part_no',
        'scanned_qty',
        'total_qty',
        'seq',
        'status',
        'scanned_by'
    ];

    protected $dates = [
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
