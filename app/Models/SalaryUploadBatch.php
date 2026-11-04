<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One salary upload file (App\Support\SalaryUpload): checked, then applied or left. */
class SalaryUploadBatch extends Model
{
    protected $table = 'salary_uploads';

    protected $fillable = [
        'user_id', 'original_name', 'stored_path', 'status', 'months', 'columns', 'applied_columns',
        'total_rows', 'ready_rows', 'error_rows', 'warning_rows', 'updated_count', 'skipped_count',
        'net_before', 'net_after', 'changes', 'applied_by', 'applied_at', 'undone_by', 'undone_at', 'undo_kept',
    ];

    protected $casts = [
        'columns' => 'array',
        'applied_columns' => 'array',
        'changes' => 'array',
        'undo_kept' => 'array',
        'applied_at' => 'datetime',
        'undone_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function undoneBy()
    {
        return $this->belongsTo(User::class, 'undone_by');
    }
}
