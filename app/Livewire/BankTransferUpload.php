<?php

namespace App\Livewire;

use App\Models\Order;
use App\Services\BankTransferService;
use App\Services\Settings\SettingsService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class BankTransferUpload extends Component
{
    use WithFileUploads;

    public string $order_no;

    public $receipt;

    protected function rules(): array
    {
        return [
            'receipt' => ['required', 'file', 'max:2048'],
        ];
    }

    public function mount(string $order_no): void
    {
        $this->order_no = $order_no;
    }

    public function submit(BankTransferService $bankTransferService): void
    {
        $this->validate();

        $order = Order::query()
            ->where('user_id', auth()->id())
            ->where('order_no', $this->order_no)
            ->firstOrFail();

        $path = $this->receipt->store('bank-receipts', 's3');
        $bankTransferService->submitReceipt($order, auth()->id(), $path);

        $this->reset('receipt');
        session()->flash('status', __('web.bank_transfer.uploaded'));
        $this->dispatch('receipt-submitted');
        session()->flash('order_success', true);
        $this->redirectRoute('dashboard.orders.show', ['order_no' => $order->order_no]);
    }

    public function render(SettingsService $settingsService): View
    {
        return view('livewire.bank-transfer-upload', [
            'bank' => [
                'bank_name' => $settingsService->getValue('bank.name'),
                'account_number' => $settingsService->getValue('bank.account_number'),
                'iban' => $settingsService->getValue('bank.iban'),
            ],
        ])->layout('layouts.app');
    }
}
