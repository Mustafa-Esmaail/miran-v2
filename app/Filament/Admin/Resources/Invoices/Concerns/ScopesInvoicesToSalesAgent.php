<?php

namespace App\Filament\Admin\Resources\Invoices\Concerns;

use App\Enums\EmployeeRole;
use App\Enums\OrderStatus;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

trait ScopesInvoicesToSalesAgent
{
    protected static function isSalesAgent(): bool
    {
        $employee = Employee::query()->where('user_id', auth()->id())->first();

        return $employee?->role === EmployeeRole::SalesPerson;
    }

    /**
     * Restricts the order picker to paid, employee-referral orders when the
     * acting user is a sales agent creating an invoice.
     */
    protected static function scopeOrdersForInvoiceCreation(Builder $query): Builder
    {
        if (! self::isSalesAgent()) {
            return $query;
        }

        return $query->where('status', OrderStatus::Paid->value)->whereNotNull('referred_by_employee_id');
    }

    /**
     * Restricts the invoice list to the sales agent's own invoices. Accountants
     * and other elevated roles see every invoice, unscoped.
     */
    protected static function scopeInvoicesToOwnRecords(Builder $query): Builder
    {
        if (! self::isSalesAgent()) {
            return $query;
        }

        return $query->where('created_by', auth()->id());
    }
}
