<?php

namespace App\Models;

use App\Enums\CertificateRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_group_id',
        'id_number',
        'first_name_ar',
        'second_name_ar',
        'third_name_ar',
        'last_name_ar',
        'birth_date',
        'gender',
        'nationality',
        'qualification',
        'email',
        'phone',
        'first_name_en',
        'second_name_en',
        'third_name_en',
        'last_name_en',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'status'     => CertificateRequestStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courseGroup(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class);
    }
}
