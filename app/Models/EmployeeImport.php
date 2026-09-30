<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One bulk employee upload (see EmployeeImportController). */
class EmployeeImport extends Model
{
    protected $fillable = [
        'user_id', 'approver_id', 'original_name', 'stored_path', 'status', 'total_rows', 'valid_rows',
        'error_rows', 'created_count', 'skipped_count', 'created_ids', 'completed_at',
    ];

    protected $casts = [
        'created_ids' => 'array',
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
