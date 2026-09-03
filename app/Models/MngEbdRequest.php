<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasEncryptedId;

class MngEbdRequest extends Model
{
    use HasFactory, SoftDeletes, HasEncryptedId;

    protected $table = 'mng_ebd_requests';

    protected $fillable = [
        'request_no',
        'wo_id',
        'customer_id',
        'model_id',
        'ebd_header_id',
        'revised_ebd_id',
        'request_date',
        'request_type',
        'description',
        'attachment_path',
        'status',
        'rejection_note',
        'requested_by',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'processed_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function projectModel()
    {
        return $this->belongsTo(ProjectModel::class, 'model_id', 'id');
    }

    public function baseEbd()
    {
        return $this->belongsTo(MngEbdHeader::class, 'ebd_header_id', 'id');
    }

    public function revisedEbd()
    {
        return $this->belongsTo(MngEbdHeader::class, 'revised_ebd_id', 'id');
    }

    // -------------------------------------------------------------------------
    // Number Generator
    // -------------------------------------------------------------------------

    public static function generateNextRequestNo(): string
    {
        $year = (int)date('Y');
        $month = (int)date('n');
        
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romanMonths[$month] ?? 'I';

        $lastSeq = 0;
        $currentYearRequests = self::withTrashed()
            ->whereYear('request_date', $year)
            ->pluck('request_no');

        foreach ($currentYearRequests as $num) {
            if (preg_match('/^(\d+)\/REQ-EBD\/SAI/i', $num, $matches)) {
                $seqVal = (int)$matches[1];
                if ($seqVal > $lastSeq) {
                    $lastSeq = $seqVal;
                }
            }
        }

        $nextSeq = $lastSeq + 1;
        return sprintf("%03d/REQ-EBD/SAI/%s/%02d", $nextSeq, $romanMonth, $year % 100);
    }
}
