<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One bulk employee upload: new employees (EmployeeImportController) or changes (EmployeeBulkUpdateController). */
class EmployeeImport extends Model
{
    protected $fillable = [
        'user_id', 'mode', 'approver_id', 'original_name', 'stored_path', 'status', 'total_rows', 'valid_rows',
        'error_rows', 'created_count', 'skipped_count', 'created_ids', 'changes', 'completed_at',
    ];

    protected $casts = [
        'created_ids' => 'array',
        'changes' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
