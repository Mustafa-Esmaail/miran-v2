<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class MenuItem extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'menu_id',
        'label',
        'type',
        'page_id',
        'url',
        'icon',
        'parent_id',
        'open_in_new_tab',
        'sort_order',
        'is_active',
    ];

    public array $translatable = ['label'];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'bool',
            'is_active' => 'bool',
            'sort_order' => 'int',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }
}
