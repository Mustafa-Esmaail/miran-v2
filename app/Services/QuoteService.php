<?php

namespace App\Services;

use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteService
{
    public function downloadPdf(Quote $quote): StreamedResponse
    {
        $quote->loadMissing(['deal.company', 'deal.employee']);

        $pdf = Pdf::loadView('pdf.quote', ['quote' => $quote])
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $quote->quote_no.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function markAsSent(Quote $quote): void
    {
        $quote->update([
            'status' => QuoteStatus::Sent->value,
            'sent_at' => now(),
        ]);
    }

    public function markAsAccepted(Quote $quote): void
    {
        $quote->update([
            'status' => QuoteStatus::Accepted->value,
            'accepted_at' => now(),
        ]);

        $quote->deal?->updateQuietly(['stage' => DealStage::ClosedWon->value]);
    }

    public function markAsRejected(Quote $quote): void
    {
        $quote->update(['status' => QuoteStatus::Rejected->value]);
    }

    public function markAsExpired(Quote $quote): void
    {
        $quote->update(['status' => QuoteStatus::Expired->value]);
    }
}
