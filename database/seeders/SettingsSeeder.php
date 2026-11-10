<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            $this->row('site.title', 'site', 'string', ['ar' => '', 'en' => ''], true, 1, $now),
            $this->row('site.logo', 'site', 'image', null, false, 2, $now),
            $this->row('site.favicon', 'site', 'image', null, false, 3, $now),
            $this->row('site.default_locale', 'site', 'string', 'ar', false, 4, $now),
            $this->row('site.currency', 'site', 'string', 'SAR', false, 5, $now),
            $this->row('site.currency_symbol', 'site', 'string', 'رس', false, 6, $now),
            $this->row('site.enable_payments', 'site', 'boolean', true, false, 7, $now),
            $this->row('seo.meta_title', 'seo', 'string', ['ar' => '', 'en' => ''], true, 10, $now),
            $this->row('seo.meta_description', 'seo', 'text', ['ar' => '', 'en' => ''], true, 11, $now),
            $this->row('seo.og_image', 'seo', 'image', null, false, 12, $now),
            $this->row('payments.enable_tamara', 'payments', 'boolean', false, false, 20, $now),
            $this->row('payments.enable_tabby', 'payments', 'boolean', false, false, 21, $now),
            $this->row('payments.enable_bank_transfer', 'payments', 'boolean', true, false, 22, $now),
            $this->row('payments.tamara.mode', 'payments', 'string', 'test', false, 23, $now),
            $this->row('payments.tamara.api_key', 'payments', 'string', null, false, 24, $now),
            $this->row('payments.tamara.init_api_key', 'payments', 'string', null, false, 25, $now),
            $this->row('payments.tabby.base_url', 'payments', 'string', null, false, 27, $now),
            $this->row('payments.tabby.public_key', 'payments', 'string', null, false, 28, $now),
            $this->row('payments.tabby.secret_key', 'payments', 'string', null, false, 29, $now),
            $this->row('payments.tabby.webhook_secret', 'payments', 'string', null, false, 30, $now),
            $this->row('bank.name', 'bank', 'string', 'الراجحي', false, 40, $now),
            $this->row('bank.account_number', 'bank', 'string', '126000010006089801223', false, 41, $now),
            $this->row('bank.iban', 'bank', 'string', 'SA9280000126608019801223', false, 42, $now),
            $this->row('contact.email', 'contact', 'string', null, false, 50, $now),
            $this->row('contact.phone', 'contact', 'string', null, false, 51, $now),
        ];

        DB::table('settings')->upsert(
            $rows,
            ['key'],
            ['group', 'type', 'value', 'is_translatable', 'sort_order', 'updated_at']
        );
    }

    private function row(string $key, string $group, string $type, $value, bool $translatable, int $sortOrder, $now): array
    {
        return [
            'key' => $key,
            'group' => $group,
            'type' => $type,
            'value' => $value === null ? null : json_encode($translatable ? $this->normalizeTranslatable($value) : $value),
            'is_translatable' => $translatable,
            'sort_order' => $sortOrder,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function normalizeTranslatable($value): array
    {
        if (is_array($value)) {
            return [
                'ar' => $value['ar'] ?? '',
                'en' => $value['en'] ?? '',
            ];
        }

        return ['ar' => (string) $value, 'en' => (string) $value];
    }
}
