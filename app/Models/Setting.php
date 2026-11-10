<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'group',
        'type',
        'value',
        'is_translatable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_translatable' => 'bool',
            'sort_order' => 'int',
        ];
    }
}
