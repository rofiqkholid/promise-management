<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\HasEncryptedId;

class MngEbdHeader extends Model
{
    use HasFactory, SoftDeletes, HasEncryptedId;

    protected $table = 'mng_ebd_headers';

    protected $fillable = [
        'wo_id',
        'revised_from_id',
        'customer_id',
        'model_id',
        'date',
        'revision',
        'is_latest',
        'status',
        'revision_note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_latest' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id', 'id');
    }

    public function revisedFrom()
    {
        return $this->belongsTo(MngEbdHeader::class, 'revised_from_id', 'id');
    }

    public function revisions()
    {
        return $this->hasMany(MngEbdHeader::class, 'revised_from_id', 'id');
    }

    public function inquiry()
    {
        return $this->hasOneThrough(
            ProjectInquiry::class,
            WorkOrder::class,
            'id',
            'id',
            'wo_id',
            'inquiry_id'
        );
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function projectModel()
    {
        return $this->belongsTo(ProjectModel::class, 'model_id', 'id');
    }

    public function quotations()
    {
        return $this->hasMany(ToolingQuotation::class, 'ebd_header_id');
    }

    /**
     * All EBD items (BOM components) belonging to this header.
     */
    public function items()
    {
        return $this->hasMany(MngEbdItem::class, 'ebd_header_id', 'id');
    }

    /**
     * Only root-level items (Level 1, no parent).
     */
    public function rootItems()
    {
        return $this->hasMany(MngEbdItem::class, 'ebd_header_id', 'id')
                    ->whereNull('parent_id')
                    ->with('children');
    }

    /**
     * Get the root ancestor header of this EBD revision lineage.
     */
    public function getRootHeader(): self
    {
        $curr = $this;
        while ($curr->revised_from_id && $curr->revisedFrom) {
            $curr = $curr->revisedFrom;
        }
        return $curr;
    }

    /**
     * Get all revisions in the lineage ordered by revision.
     */
    public function getAllRevisions()
    {
        $root = $this->getRootHeader();
        $allIds = collect([$root->id]);
        
        $queue = [$root->id];
        while (!empty($queue)) {
            $next = self::whereIn('revised_from_id', $queue)->pluck('id')->toArray();
            $queue = [];
            foreach ($next as $nId) {
                if (!$allIds->contains($nId)) {
                    $allIds->push($nId);
                    $queue[] = $nId;
                }
            }
        }

        return self::whereIn('id', $allIds)
            ->with(['workOrder', 'customer', 'projectModel'])
            ->orderBy('id', 'asc')
            ->get();
    }
}
