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
        'status'
    ];

    public function contentLists()
    {
        return $this->hasMany(ContentList::class, 'case_id');
    }

    public function scanHistory()
    {
        return $this->hasMany(ScanHistory::class, 'case_id');
    }

    public function getProgressAttribute()
    {
        $totalBoxes = $this->contentLists()->count();
        $scannedBoxes = $this->scanHistory()->where('status', 'scanned')->distinct('box_no')->count();
        
        if ($totalBoxes == 0) return '0/0';
        
        return "{$scannedBoxes}/{$totalBoxes}";
    }
}