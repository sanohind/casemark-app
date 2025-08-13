<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseModel extends Model
{
    use HasFactory;

    protected $table = 'cases';
    
    protected $fillable = [
        'case_no',
        'destination',
        'order_no',
        'prod_month',
        'case_size',
        'gross_weight',
        'net_weight',
        'status',
        'packing_date'
    ];

     protected $casts = [
        'packing_date' => 'datetime',
        'gross_weight' => 'decimal:2',
        'net_weight' => 'decimal:2',
    ];


    public function contentLists()
    {
        return $this->hasMany(ContentList::class, 'case_id');
    }

    public function scanHistory()
    {
        return $this->hasMany(ScanHistory::class, 'case_no', 'case_no');
    }

    public function getProgressAttribute()
    {
        $totalBoxes = $this->contentLists()->count();
        
        if ($this->status == 'packed') {
            // For packed cases, all boxes are considered scanned.
            $scannedBoxes = $totalBoxes;
        } else {
            // For other statuses, count boxes with 'scanned' status.
            $scannedBoxes = $this->scanHistory()->where('status', 'scanned')->count();
        }
        
        if ($totalBoxes == 0) {
            return '0/0';
        }
        
        return "{$scannedBoxes}/{$totalBoxes}";
    }
}