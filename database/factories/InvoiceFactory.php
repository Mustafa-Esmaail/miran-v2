<?php

namespace Database\Factories;

use App\Enums\InvoiceRecipientType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'invoice_number' => 'INV-'.$this->faker->unique()->numerify('######'),
            'invoice_path' => 'invoices/'.$this->faker->uuid().'.pdf',
            'status' => InvoiceStatus::Draft->value,
            'amount' => $this->faker->numberBetween(1000, 500000),
            'issued_at' => null,
            'recipient_type' => InvoiceRecipientType::Person->value,
            'recipient_name' => $this->faker->name(),
        ];
    }
}
