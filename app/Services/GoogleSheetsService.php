<?php

namespace App\Services;

use App\Enums\CouponType;
use App\Models\Bundle;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\ServiceRequest;
use App\Services\Settings\SettingsService;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function appendRow(string $sheetId, array $row): bool
    {
        return $this->writeRow($sheetId, $row, anchorToColumnA: false);
    }

    /**
     * Writes the row on the next row after the last filled cell in column A, starting
     * at column A. Unlike append(), this skips Google's table detection, which can
     * shift the row to a far-right column (e.g. AJ) when the sheet has formula columns.
     */
    public function appendRowFromColumnA(string $sheetId, array $row): bool
    {
        return $this->writeRow($sheetId, $row, anchorToColumnA: true);
    }

    private function writeRow(string $sheetId, array $row, bool $anchorToColumnA): bool
    {
        $credentialsPath = $this->settings->googleSheetsCredentialsPath();

        $logger = Log::channel('google_sheets');

        if ($credentialsPath === '' || ! file_exists($credentialsPath)) {
            $logger->error('GoogleSheetsService: service account JSON file not found or not configured.', [
                'path' => $credentialsPath,
            ]);

            return false;
        }

        $clientEmail = json_decode(file_get_contents($credentialsPath), true)['client_email'] ?? 'unknown';
        $logger->info('GoogleSheetsService: using service account.', [
            'client_email' => $clientEmail,
            'credentials_path' => $credentialsPath,
            'sheet_id' => $sheetId,
        ]);

        try {
            $client = new Client;
            $client->setAuthConfig($credentialsPath);
            $client->setScopes([Sheets::SPREADSHEETS]);

            $service = new Sheets($client);
            $valueRange = new ValueRange(['values' => [$row]]);
            if ($anchorToColumnA) {
                $filledRows = $service->spreadsheets_values->get($sheetId, 'A:A')->getValues() ?? [];
                $service->spreadsheets_values->update(
                    $sheetId,
                    'A'.(count($filledRows) + 1),
                    $valueRange,
                    ['valueInputOption' => 'USER_ENTERED'],
                );
            } else {
                $service->spreadsheets_values->append(
                    $sheetId,
                    'A:Z',
                    $valueRange,
                    ['valueInputOption' => 'USER_ENTERED'],
                );
            }

            $logger->info('GoogleSheetsService: row appended successfully.', [
                'sheet_id' => $sheetId,
                'row' => $row,
            ]);

            return true;
        } catch (\Throwable $e) {
            $logger->error('GoogleSheetsService: exception while appending row.', [
                'sheet_id' => $sheetId,
                'row' => $row,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * One row per order item. Columns A-K and U-X are filled; E and L-T are
     * intentionally left blank — they are manual/operational columns staff manage
     * in the sheet itself (assignment status, WhatsApp sent, marketing team, etc.),
     * and Y onward are the sheet's own formula columns which must never be written to.
     *
     * @return array<int, array<int, string>>
     */
    public function buildRowsForOrder(Order $order): array
    {
        $order->loadMissing(['items.purchasable', 'user', 'coupon', 'referredByEmployee', 'payments', 'bankTransferReceipt']);

        $purchaseDate = $order->created_at?->format('Y-m-d H:i') ?? '';
        $customerPhone = $order->customer_phone ?? '';
        $employeeName = $order->referredByEmployee?->employee_code ?? $order->marketing_source ?? '';
        $customerName = $order->customer_name ?? '';
        $orderNo = $order->order_no ?? '';
        $paymentMethod = $order->payments->last()?->provider ?? ($order->bankTransferReceipt ? 'bank_transfer' : '');
        $couponCode = $order->coupon?->code ?? '';
        $email = $order->user?->email ?? '';

        [$firstName, $lastName] = $this->splitName($customerName);

        if ($order->items->isEmpty()) {
            return [];
        }

        $itemCount = $order->items->count();
        $fixedDiscountPerItem = ($order->coupon?->type === CouponType::Fixed && $itemCount > 0)
            ? $order->discount_amount / $itemCount
            : 0;

        return $order->items->map(function ($item) use (
            $purchaseDate,
            $customerPhone,
            $employeeName,
            $customerName,
            $orderNo,
            $paymentMethod,
            $couponCode,
            $email,
            $firstName,
            $lastName,
            $order,
            $fixedDiscountPerItem
        ): array {
            [$courseType, $courseDate] = $this->resolveItemCourseTypeAndDate($item);
            $lineTotal = $item->price_amount * $item->quantity;
            $itemDiscount = match ($order->coupon?->type) {
                CouponType::Percent => $lineTotal * ($order->coupon->value / 100),
                CouponType::Fixed => $fixedDiscountPerItem,
                default => 0,
            };
            $itemTotal = (string) (int) round(max(0, $lineTotal - $itemDiscount));

            $blank = ['', '', '', '', '', '', '', '', '']; // L..T (9 columns) — never written to

            return [
                $purchaseDate,      // A
                $customerPhone,     // B
                $courseType,        // C
                $courseType,        // D
                '',                 // E — intentionally left blank
                $courseDate,        // F
                $customerName,      // G
                $orderNo,           // H
                $paymentMethod,     // I
                $itemTotal,         // J
                $employeeName,      // K
                ...$blank,          // L..T
                $firstName,         // U
                $lastName,          // V
                $email,             // W
                $couponCode,        // X
            ];
        })->all();
    }

    /**
     * @return array{0: string, 1: string} [courseType, courseDate]
     */
    private function resolveItemCourseTypeAndDate($item): array
    {
        $purchasable = $item->purchasable;

        if ($purchasable instanceof CourseGroup) {
            $courseTitle = $purchasable->course?->getTranslation('title', 'ar') ?? '';
            $groupTitle = $purchasable->getTranslation('title', 'ar') ?? '';
            $title = trim(collect([$courseTitle, $groupTitle])->filter(fn ($part) => $part !== '')->implode(' - '));
            $date = $purchasable->start_at?->format('Y-m-d') ?? '';

            return [$title, $date];
        }

        if ($purchasable instanceof Bundle) {
            return [$purchasable->getTranslation('title', 'ar') ?? '', ''];
        }

        return [$item->title_snapshot ?? '', ''];
    }

    /**
     * @return array{0: string, 1: string} [firstName, lastName]
     */
    public function splitName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $fullName, 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    public function buildRowForType(ServiceRequest $request, ?string $landingSlug = null): array
    {
        return match ($request->type?->slug) {
            'tlbat-altoasl' => $this->buildCompanyContactRow($request),
            'tlbat-altoasl-maa-alshrkat' => $this->buildCompanyContactRow($request),
            'tlbat' => $this->buildRequestRow($request, $landingSlug),
            'shakawi' => $this->buildComplaintRow($request),
            default => $this->buildGenericRow($request),
        };
    }

    /**
     * Generic fallback — used for any type with no dedicated builder.
     * Columns: ID | التاريخ | الوقت | المصدر | النوع | الاسم | الجوال | البريد | الرساله | الدورة
     */
    private function buildGenericRow(ServiceRequest $request): array
    {
        return [
            $request->created_at?->format('Y-m-d') ?? '',
            $request->created_at?->locale('ar')->translatedFormat('g:i a') ?? '',
            $request->source?->value ?? '',
            $request->type?->getTranslatedName('ar') ?? '',
            $request->name ?? '',
            $request->phone,
            $request->email ?? '',
            $request->message ?? '',
            $request->course?->getTranslation('title', 'ar') ?? '',
        ];
    }

    /**
     * Type 1 — طلبات التواصل مع الشركات
     * Columns: اسمك الكريم | اسم الشركة | رقم الجوال | البريد الالكتروني | المدينة | الرساله | الدورة المهتم بها | التاريخ
     */
    private function buildCompanyContactRow(ServiceRequest $request): array
    {
        return [
            $request->name ?? '',
            $request->company_name ?? '',
            $request->phone,
            $request->email ?? '',
            $request->city ?? '',
            $request->message ?? '',
            $request->course?->getTranslation('title', 'ar') ?? '',
            $request->created_at?->format('Y-m-d') ?? '',
        ];
    }

    /**
     * Type 2 — طلبات
     * Columns: A=التاريخ | B=الوقت | C=رقم العميل | D= | E= | F=نوع الدورة | G-K= | L=كود التسويق
     */
    private function buildRequestRow(ServiceRequest $request, ?string $landingSlug = null): array
    {
        $course = $request->course;
        $courseTitle = $course
            ? ($course->getTranslation('title', 'ar').' '.(int) ($course->final_price ?? $course->price ?? 0).' ريال')
            : '';

        // Only the new landing pages send their slug; every other form leaves it null.
        // Versioned landing pages add their version number after it ("pmpon v2").
        if (filled($landingSlug)) {
            $version = $request->page?->isVersioned() ? $request->page->version : null;
            $courseTitle = trim($courseTitle.' '.$landingSlug.($version ? ' v'.$version : ''));
        }

        // D, E, and G-K are intentionally left blank; course title goes in column F,
        // marketing source code goes in column L
        return [
            $request->created_at?->format('Y-m-d') ?? '',
            $request->created_at?->locale('ar')->translatedFormat('g:i a') ?? '',
            $request->phone,
            '',  // D — empty
            '',  // E — empty
            $courseTitle,
            '',  // G — empty
            '',  // H — empty
            '',  // I — empty
            '',  // J — empty
            '',  // K — empty
            $request->marketing_source ?? '',  // L
        ];
    }

    /**
     * Type 3 — شكاوى
     * Columns: التاريخ | التوقيت | الجوال | العميل | المشكلة
     */
    private function buildComplaintRow(ServiceRequest $request): array
    {
        return [
            $request->created_at?->format('Y-m-d') ?? '',
            $request->created_at?->locale('ar')->translatedFormat('g:i a') ?? '',
            $request->phone,
            $request->name ?? '',
            $request->message ?? '',
        ];
    }
}
