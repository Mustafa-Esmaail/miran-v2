<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionField extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'key',
        'type',
        'value',
        'group',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'sort_order' => 'int',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
