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
        'remark',
        'status'
    ];

    protected $appends = [
        'status'
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function isScanned()
    {
        // Cari berdasarkan box_no saja terlebih dahulu
        $scanned = ScanHistory::where('case_no', $this->case->case_no)
                             ->where('box_no', $this->box_no)
                             ->exists();
        
        // Jika tidak ditemukan, coba cari berdasarkan kombinasi box_no dan part_no
        if (!$scanned) {
            $scanned = ScanHistory::where('case_no', $this->case->case_no)
                                 ->where('box_no', $this->box_no)
                                 ->where('part_no', $this->part_no)
                                 ->exists();
        }
        
        return $scanned;
    }

    public function getStatusAttribute()
    {
        // Jika kolom status sudah ada di database, ambil dari database
        if (isset($this->attributes['status'])) {
            return $this->attributes['status'];
        }
        
        // Fallback ke logic lama jika status belum diset
        return $this->isScanned() ? 'Scanned' : 'Not Scanned';
    }

    // Method untuk update status berdasarkan scan
    public function updateStatus()
    {
        $status = $this->isScanned() ? 'Scanned' : 'Not Scanned';
        $this->update(['status' => $status]);
        return $status;
    }
}