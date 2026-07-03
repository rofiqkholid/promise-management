<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalConfig extends Model
{
    use HasFactory;
    protected $table = 'mng_approval_config';
    protected $fillable = [
        'document_type',
        'approval_level',
        'department_id',
        'approver_user_ids',
        'position_label',
        'action_label',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'approval_level' => 'integer',
        'sort_order' => 'integer',
        'approver_user_ids' => 'array',
    ];

    protected $appends = ['rule_id'];

    protected $with = ['department'];

    public function getRuleIdAttribute()
    {
        return $this->id;
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function getApproverUsersAttribute()
    {
        if (empty($this->approver_user_ids)) {
            return collect();
        }
        return User::whereIn('id', $this->approver_user_ids)->get();
    }

    /**
     * Check if a given user is authorized to approve under this rule.
     */
    public function canBeApprovedBy(User $user): bool
    {
        if (!$this->is_active) return false;

        // If specific users assigned, must match one of them
        if (is_array($this->approver_user_ids) && count($this->approver_user_ids) > 0) {
            return in_array($user->id, $this->approver_user_ids);
        }

        // Otherwise, any user in the assigned department
        return $this->department_id == $user->id_dept;
    }

    /**
     * Scope: active rules for a document type, ordered by level.
     */
    public function scopeActiveFor($query, string $type = 'WO')
    {
        return $query->where('document_type', $type)
                     ->where('is_active', true)
                     ->orderBy('approval_level', 'asc')
                     ->orderBy('sort_order', 'asc');
    }
}
