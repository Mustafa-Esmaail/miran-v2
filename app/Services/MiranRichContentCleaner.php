<?php

namespace App\Services;

use App\Enums\DeliveryType;
use Illuminate\Support\Str;

class MiranRichContentCleaner
{
    private const HEADING_KEYWORDS = '/(الأهداف|المحاور|المحتوى|المميزات|الفئة|التعريف|نبذة|وصف|المخرجات|المتطلبات|الشهادة)/u';

    private const HEADING_MAP = [
        'التعريف بالدورة' => 'about',
        'نبذة عن الدورة' => 'about',
        'وصف الدورة' => 'about',
        'الأهداف التفصيلية للدورة' => 'objectives',
        'الأهداف' => 'objectives',
        'أهداف الدورة' => 'objectives',
        'الفئة المستهدفة' => 'audience',
        'لمن هذه الدورة' => 'audience',
        'الجمهور المستهدف' => 'audience',
        'مميزات الدورة' => 'advantages',
        'مزايا الدورة' => 'advantages',
        'لماذا هذه الدورة' => 'advantages',
        'محتوى الدورة' => 'outline',
        'المحاور' => 'outline',
        'المنهج' => 'outline',
        'الخطة التدريبية' => 'outline',
        'المخرجات' => 'outcomes',
        'في نهاية الدورة سيكون المشاركون قادرون على' => 'outcomes',
        'الاعتمادات' => 'certification',
        'الشهادة' => 'certification',
        'شهادة معتمدة' => 'certification',
        'المتطلبات' => 'requirements',
        'المتطلبات السابقة' => 'requirements',
        'متطلبات الالتحاق' => 'requirements',
    ];

    private const SECTION_ORDER = [
        'about',
        'objectives',
        'audience',
        'advantages',
        'outline',
        'outcomes',
        'requirements',
        'certification',
        'other',
    ];

    public function htmlToCleanText(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $clean = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', ' ', $html);
        $clean = preg_replace('/<br\s*\/?>/i', "\n", $clean);
        $clean = preg_replace('/<\/(p|div|section|li|h1|h2|h3|h4|tr)>/i', "\n", $clean);
        $clean = preg_replace('/<li[^>]*>/i', '- ', $clean);
        $clean = strip_tags($clean);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = preg_replace("/[ \t]+/", ' ', $clean);
        $clean = preg_replace("/\n{3,}/", "\n\n", $clean);
        $clean = $this->removeCssGarbage($clean);

        return trim($clean);
    }

    public function removeCssGarbage(string $text): string
    {
        $lines = preg_split("/\r\n|\r|\n/", $text);
        $filtered = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                $filtered[] = '';

                continue;
            }

            if (preg_match('/^\s*(\.|#)[\w\-]+\s*[{:]/u', $trimmed)) {
                continue;
            }

            if (preg_match('/^\s*@media/i', $trimmed)) {
                continue;
            }

            if (str_contains($trimmed, '{') || str_contains($trimmed, '}')) {
                continue;
            }

            if (preg_match('/(margin|padding|color|font|background|display|position|flex|grid)\s*:/i', $trimmed)) {
                continue;
            }

            if (preg_match('/(vc_row|vc_column|wpb_row|wpb_column|row-inner|col-inner)/i', $trimmed)) {
                continue;
            }

            $filtered[] = $trimmed;
        }

        $result = implode("\n", $filtered);
        $result = preg_replace("/\n{3,}/", "\n\n", $result);

        return trim($result);
    }

    public function parseSections(string $cleanText): array
    {
        $lines = preg_split("/\r\n|\r|\n/", $cleanText);
        $sections = [];
        $preamble = [];
        $currentIndex = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                if ($currentIndex !== null) {
                    $sections[$currentIndex]['lines'][] = '';
                } else {
                    $preamble[] = '';
                }

                continue;
            }

            $headingKey = $this->detectHeadingKey($line);
            if ($headingKey !== null) {
                $sections[] = [
                    'key' => $headingKey,
                    'heading' => $this->normalizeHeadingLabel($line),
                    'lines' => [],
                ];
                $currentIndex = count($sections) - 1;

                continue;
            }

            if ($currentIndex === null) {
                $preamble[] = $line;
            } else {
                $sections[$currentIndex]['lines'][] = $line;
            }
        }

        return [
            'preamble' => $preamble,
            'sections' => $sections,
        ];
    }

    public function extractStructuredMeta(string $cleanText): array
    {
        $durationHours = null;
        $daysCount = null;
        $timeFrom = null;
        $timeTo = null;
        $deliveryType = null;

        if (preg_match('/(\d{1,3})\s*(?:ساعة|ساعات)(?:\s*تدريبية)?/u', $cleanText, $matches)) {
            $durationHours = (int) $matches[1];
        }

        if (preg_match('/(\d{1,3})\s*(?:يوم|أيام|ايام)(?:\s*تدريبية)?/u', $cleanText, $matches)) {
            $daysCount = (int) $matches[1];
        }

        if (preg_match('/من\s*(\d{1,2})(?::(\d{2}))?\s*(صباحا|صباحًا|مساء|مساءً|AM|PM|am|pm)?\s*(?:إلى|الى|الي)\s*(\d{1,2})(?::(\d{2}))?\s*(صباحا|صباحًا|مساء|مساءً|AM|PM|am|pm)?/u', $cleanText, $matches)) {
            $fromMeridiem = $matches[3] ?? null;
            $toMeridiem = $matches[6] ?? $fromMeridiem;

            $timeFrom = $this->to24HourTime(
                (int) $matches[1],
                $matches[2] !== '' ? (int) $matches[2] : 0,
                $fromMeridiem
            );

            $timeTo = $this->to24HourTime(
                (int) $matches[4],
                $matches[5] !== '' ? (int) $matches[5] : 0,
                $toMeridiem
            );
        }

        if (preg_match('/(عن بعد|أونلاين|اونلاين|بث مباشر|افتراضي)/u', $cleanText)) {
            $deliveryType = str_contains($cleanText, 'بث مباشر')
                ? DeliveryType::RemoteLive->value
                : DeliveryType::Online->value;
        } elseif (preg_match('/(حضوري|حضورى|وجاهي|وجاهى)/u', $cleanText)) {
            $deliveryType = DeliveryType::Offline->value;
        } elseif (preg_match('/(مزيج|مختلط|مدمج|هجين)/u', $cleanText)) {
            $deliveryType = DeliveryType::Hybrid->value;
        }

        return [
            'duration_hours' => $durationHours,
            'duration_group' => $this->buildDurationGroup($durationHours),
            'days_count' => $daysCount,
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'delivery_type' => $deliveryType,
        ];
    }

    public function splitSummaryAndDescription(string $cleanText): array
    {
        $parsed = $this->parseSections($cleanText);
        $sections = $parsed['sections'];
        $preambleText = trim(implode("\n", $parsed['preamble']));

        $hasAbout = false;
        foreach ($sections as $section) {
            if ($section['key'] === 'about' && $this->sectionHasContent($section)) {
                $hasAbout = true;
                break;
            }
        }

        if (! $hasAbout) {
            [$summaryFallback, $descriptionFallback] = $this->fallbackSummaryAndDescription($cleanText);

            return [$summaryFallback, $descriptionFallback];
        }

        $ordered = $this->orderSections($sections);
        $description = $this->sectionsToText($ordered);

        return [$preambleText, $description];
    }

    public function toFilamentHtml(string|array $textOrSections): string
    {
        $text = is_array($textOrSections)
            ? $this->sectionsToText($this->orderSections($textOrSections['sections'] ?? []))
            : $textOrSections;

        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $lines = preg_split("/\r\n|\r|\n/", $text);
        $htmlParts = [];
        $listItems = [];

        foreach ($lines as $line) {
            $line = trim(strip_tags($line));

            if ($line === '') {
                $this->flushList($htmlParts, $listItems);

                continue;
            }

            $headingKey = $this->detectHeadingKey($line);
            if ($headingKey !== null) {
                $this->flushList($htmlParts, $listItems);
                $htmlParts[] = '<h2>'.e($this->normalizeHeadingLabel($line)).'</h2>';

                continue;
            }

            if (preg_match('/^[-•*]\s+/u', $line)) {
                $listItems[] = trim(preg_replace('/^[-•*]\s+/u', '', $line));

                continue;
            }

            $this->flushList($htmlParts, $listItems);
            $htmlParts[] = '<p>'.e($line).'</p>';
        }

        $this->flushList($htmlParts, $listItems);

        return implode('', $htmlParts);
    }

    public function buildDurationGroup(?int $hours): ?string
    {
        if ($hours === null) {
            return null;
        }

        return match (true) {
            $hours <= 5 => 'short',
            $hours <= 15 => 'medium',
            $hours <= 30 => 'long',
            default => 'extended',
        };
    }

    private function detectHeadingKey(string $line): ?string
    {
        $label = $this->normalizeHeadingLabel($line);

        if (array_key_exists($label, self::HEADING_MAP)) {
            return self::HEADING_MAP[$label];
        }

        if (preg_match(self::HEADING_KEYWORDS, $label)) {
            return $this->mapHeadingByKeywords($label);
        }

        $isShort = mb_strlen($label) <= 40;
        $endsWithColon = str_ends_with($line, ':') || str_ends_with($line, '：');

        if (($isShort || $endsWithColon) && preg_match(self::HEADING_KEYWORDS, $label)) {
            return $this->mapHeadingByKeywords($label);
        }

        return null;
    }

    private function normalizeHeadingLabel(string $line): string
    {
        $label = trim($line);
        $label = rtrim($label, " \t\n\r\0\x0B:：");

        return $label;
    }

    private function mapHeadingByKeywords(string $label): string
    {
        $map = [
            'الأهداف' => 'objectives',
            'الهدف' => 'objectives',
            'المحاور' => 'outline',
            'المحتوى' => 'outline',
            'المنهج' => 'outline',
            'المميزات' => 'advantages',
            'المزايا' => 'advantages',
            'الفئة' => 'audience',
            'التعريف' => 'about',
            'نبذة' => 'about',
            'وصف' => 'about',
            'المخرجات' => 'outcomes',
            'المتطلبات' => 'requirements',
            'الشهادة' => 'certification',
        ];

        foreach ($map as $keyword => $key) {
            if (Str::contains($label, $keyword)) {
                return $key;
            }
        }

        return 'other';
    }

    private function sectionHasContent(array $section): bool
    {
        foreach ($section['lines'] as $line) {
            if (trim($line) !== '') {
                return true;
            }
        }

        return false;
    }

    private function orderSections(array $sections): array
    {
        $ordered = [];

        foreach (self::SECTION_ORDER as $key) {
            foreach ($sections as $section) {
                if ($section['key'] === $key) {
                    $ordered[] = $section;
                }
            }
        }

        foreach ($sections as $section) {
            if (! in_array($section['key'], self::SECTION_ORDER, true)) {
                $ordered[] = $section;
            }
        }

        return $ordered;
    }

    private function sectionsToText(array $sections): string
    {
        $parts = [];

        foreach ($sections as $section) {
            $heading = trim($section['heading'] ?? '');
            if ($heading !== '') {
                $parts[] = $heading;
            }

            foreach ($section['lines'] as $line) {
                $line = trim($line);
                if ($line === '') {
                    $parts[] = '';
                } else {
                    $parts[] = $line;
                }
            }
        }

        return trim(implode("\n", $parts));
    }

    private function fallbackSummaryAndDescription(string $cleanText): array
    {
        $blocks = preg_split("/\n{2,}/", $cleanText);
        $blocks = array_values(array_filter(array_map('trim', $blocks)));

        if (count($blocks) >= 2) {
            $summary = implode("\n\n", array_slice($blocks, 0, min(3, count($blocks))));

            return [$summary, $cleanText];
        }

        $lines = preg_split("/\r\n|\r|\n/", $cleanText);
        $lines = array_values(array_filter(array_map('trim', $lines)));
        $summary = implode("\n", array_slice($lines, 0, min(3, count($lines))));

        return [$summary, $cleanText];
    }

    private function flushList(array &$htmlParts, array &$listItems): void
    {
        if ($listItems === []) {
            return;
        }

        $itemsHtml = array_map(fn (string $item) => '<li>'.e($item).'</li>', $listItems);
        $htmlParts[] = '<ul>'.implode('', $itemsHtml).'</ul>';
        $listItems = [];
    }

    private function to24HourTime(int $hour, int $minute, ?string $meridiem): string
    {
        $meridiem = $meridiem ? trim(mb_strtolower($meridiem)) : null;

        if ($meridiem) {
            $isPm = in_array($meridiem, ['pm', 'مساء', 'مساءً'], true);
            $isAm = in_array($meridiem, ['am', 'صباحا', 'صباحًا'], true);

            if ($isPm && $hour < 12) {
                $hour += 12;
            } elseif ($isAm && $hour === 12) {
                $hour = 0;
            }
        }

        return sprintf('%02d:%02d:00', $hour, $minute);
    }
}
