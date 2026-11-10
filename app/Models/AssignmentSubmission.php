<?php

namespace App\Models;

use App\Enums\AssignmentSubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'enrollment_id',
        'file_path',
        'submitted_at',
        'grade',
        'feedback',
        'graded_by',
        'graded_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'file_path' => 'array',
            'submitted_at' => 'datetime',
            'grade' => 'int',
            'graded_at' => 'datetime',
            'status' => AssignmentSubmissionStatus::class,
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
