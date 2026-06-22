<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRule extends Model
{
    use HasFactory;

    protected $table = 'mng_approval_rules';
    protected $primaryKey = 'rule_id';

    protected $fillable = [
        'document_type',
        'approval_level',
        'department_id',
        'approver_user_id',
        'position_label',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'approval_level' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $with = ['department'];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function approverUser()
    {
        return $this->belongsTo(User::class, 'approver_user_id', 'id');
    }

    /**
     * Check if a given user is authorized to approve under this rule.
     */
    public function canBeApprovedBy(User $user): bool
    {
        if (!$this->is_active) return false;

        // If specific user assigned, must match
        if ($this->approver_user_id) {
            return $this->approver_user_id == $user->id;
        }

        // Otherwise, any user in the assigned department
        return $this->department_id == $user->id_dept;
    }

    /**
     * Scope: active rules for a document type, ordered by level.
     */
    public function scopeActiveFor($query, string $type = 'SPK')
    {
        return $query->where('document_type', $type)
                     ->where('is_active', true)
                     ->orderBy('approval_level', 'asc')
                     ->orderBy('sort_order', 'asc');
    }
}
