<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentList extends Model
{
    use HasFactory;

    protected $table = 'content_lists';
    
    protected $fillable = [
        'case_id',
        'box_no',
        'part_no',
        'part_name',
        'quantity',
        'remark'
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function isScanned()
    {
        return ScanHistory::where('case_id', $this->case_id)
                         ->where('box_no', $this->box_no)
                         ->where('part_no', $this->part_no)
                         ->exists();
    }

    public function getStatusAttribute()
    {
        return $this->isScanned() ? 'Scanned' : 'Pending';
    }
}
