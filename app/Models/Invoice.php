<?php

namespace App\Models;

use App\Enums\InvoiceRecipientType;
use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasDeletedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasDeletedBy;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'created_by',
        'invoice_number',
        'invoice_path',
        'status',
        'amount',
        'issued_at',
        'recipient_type',
        'recipient_name',
        'company_legal_name',
        'company_tax_certificate_path',
        'company_cr_certificate_path',
        'company_city',
        'company_district',
        'company_street',
        'company_building_number',
        'company_postal_code',
        'company_additional_number',
        'company_phone',
        'company_website',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'recipient_type' => InvoiceRecipientType::class,
            'amount' => 'int',
            'issued_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
