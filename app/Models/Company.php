<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'industry',
        'size',
        'website',
        'tax_id',
        'address',
        'account_manager_id',
        'notes',
    ];

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'account_manager_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
