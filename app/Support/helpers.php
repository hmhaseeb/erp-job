<?php

use App\Models\ActivityLog;
use App\Models\CompanySetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

if (! function_exists('erp_settings')) {
    /**
     * Retrieve the cached company settings row or a default object.
     */
    function erp_settings(): object
    {
        if (app()->bound('erp.settings')) {
            return app('erp.settings');
        }

        $defaults = (object) [
            'company_name' => 'Dreams ERP Solutions',
            'trade_name' => 'Dreams ERP',
            'tagline' => 'Enterprise Resource Planning',
            'registration_no' => 'REG-2026-8841',
            'tax_number' => 'VAT-1004592003',
            'license_no' => 'LIC-90841',
            'business_type' => 'Private Limited',
            'industry' => 'Technology & Services',
            'website' => 'https://dreamserp.local',
            'email' => 'info@dreamserp.com',
            'support_email' => 'support@dreamserp.com',
            'phone' => '+1 (555) 234-5678',
            'mobile' => '+1 (555) 987-6543',
            'address_line1' => '123 Business Ave, Suite 400',
            'address_line2' => 'Enterprise District',
            'city' => 'New York',
            'state' => 'NY',
            'country' => 'United States',
            'postal_code' => '10001',
            'logo_light' => null,
            'logo_dark' => null,
            'logo_small' => null,
            'favicon' => null,
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'currency_decimals' => 2,
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'tax_enabled' => true,
            'tax_label' => 'VAT',
            'default_tax_rate' => 5.00,
            'tax_inclusive' => false,
            'date_format' => 'd M Y',
            'time_format' => 'h:i A',
            'timezone' => 'UTC',
            'language' => 'en',
            'is_rtl' => false,
            'default_theme' => 'light',
            'fiscal_year_label' => '2026-2027',
            'fiscal_year_start_month' => 1,
            'fiscal_year_end_month' => 12,
            'fiscal_lock_date' => null,
            'sales_order_prefix' => 'SO-',
            'sales_invoice_prefix' => 'INV-',
            'sales_return_prefix' => 'SR-',
            'purchase_order_prefix' => 'PO-',
            'purchase_invoice_prefix' => 'PINV-',
            'purchase_return_prefix' => 'PR-',
            'job_card_prefix' => 'JC-',
            'employee_prefix' => 'EMP-',
            'receipt_prefix' => 'RCP-',
            'payment_prefix' => 'PAY-',
            'invoice_terms' => 'Thank you for your business. Payment is due within 15 days of invoice date.',
            'invoice_footer_note' => 'Computer-generated document. No signature required.',
            'show_logo_on_docs' => true,
            'show_tax_on_docs' => true,
            'mail_host' => 'smtp.mailtrap.io',
            'mail_port' => 587,
            'mail_username' => '',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@dreamserp.com',
            'mail_from_name' => 'Dreams ERP',
            'notify_low_stock' => true,
            'notify_invoice_created' => true,
            'notify_payroll_processed' => true,
            'notify_job_card_updates' => true,
            'low_stock_threshold' => 10,
            'per_page' => 10,
            'maintenance_mode' => false,
            'session_timeout' => 120,
            'footer_text' => '2026 © Dreams ERP. All Rights Reserved',
        ];

        try {
            if (Schema::hasTable('company_settings')) {
                $row = CompanySetting::query()->first();
                if ($row) {
                    app()->instance('erp.settings', $row);
                    return $row;
                }
            }
        } catch (\Throwable $e) {
            // Ignore during early boot / before migrations
        }

        return $defaults;
    }
}

if (! function_exists('erp_settings_clear_cache')) {
    function erp_settings_clear_cache(): void
    {
        app()->forgetInstance('erp.settings');
    }
}

if (! function_exists('erp_setting')) {
    function erp_setting(string $key, mixed $default = null): mixed
    {
        $settings = erp_settings();
        return $settings->{$key} ?? $default;
    }
}

if (! function_exists('erp_logo')) {
    function erp_logo(string $variant = 'light'): string
    {
        $settings = erp_settings();
        $map = [
            'light' => ['field' => 'logo_light', 'fallback' => 'assets/img/logo.svg'],
            'dark' => ['field' => 'logo_dark', 'fallback' => 'assets/img/logo-white.svg'],
            'small' => ['field' => 'logo_small', 'fallback' => 'assets/img/logo-small.svg'],
            'favicon' => ['field' => 'favicon', 'fallback' => 'assets/img/favicon.png'],
        ];

        $cfg = $map[$variant] ?? $map['light'];
        $path = $settings->{$cfg['field']} ?? null;

        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        return asset($cfg['fallback']);
    }
}

if (! function_exists('format_currency')) {
    function format_currency(float|int|string|null $amount, bool $withSymbol = true): string
    {
        $value = (float) ($amount ?? 0);
        $settings = erp_settings();
        $decimals = (int) ($settings->currency_decimals ?? 2);
        $decSep = $settings->decimal_separator ?? '.';
        $thSep = $settings->thousand_separator ?? ',';
        $symbol = $settings->currency_symbol ?? '$';
        $position = $settings->currency_position ?? 'before';

        $sign = $value < 0 ? '-' : '';
        $formatted = number_format(abs($value), $decimals, $decSep, $thSep);

        if (! $withSymbol) {
            return $sign.$formatted;
        }

        return $position === 'after'
            ? $sign.$formatted.' '.$symbol
            : $sign.$symbol.$formatted;
    }
}

if (! function_exists('format_date')) {
    function format_date(mixed $date, bool $withTime = false): string
    {
        if (empty($date)) {
            return '—';
        }

        $settings = erp_settings();
        $dateFormat = $settings->date_format ?? 'd M Y';
        $timeFormat = $settings->time_format ?? 'h:i A';

        try {
            $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
            return $carbon->format($withTime ? "$dateFormat $timeFormat" : $dateFormat);
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }
}

if (! function_exists('erp_next_number')) {
    function erp_next_number(?string $prefixKey, string $table, string $column, string $defaultPrefix, int $pad = 4): string
    {
        $prefix = $prefixKey ? (string) erp_setting($prefixKey, $defaultPrefix) : $defaultPrefix;
        $maxId = 0;

        try {
            if (Schema::hasTable($table)) {
                $maxId = (int) DB::table($table)->max('id');
            }
        } catch (\Throwable $e) {
            $maxId = 0;
        }

        $next = $maxId + 1;
        while (
            Schema::hasTable($table) &&
            DB::table($table)->where($column, $prefix.str_pad((string) $next, $pad, '0', STR_PAD_LEFT))->exists()
        ) {
            $next++;
        }

        return $prefix.str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('log_activity')) {
    function log_activity(string $module, string $action, string $description, ?Model $subject = null, ?int $userId = null): void
    {
        try {
            if (! Schema::hasTable('activity_logs')) {
                return;
            }

            ActivityLog::create([
                'user_id' => $userId ?? Auth::id(),
                'module' => $module,
                'action' => $action,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'ip_address' => request()?->ip(),
                'user_agent' => substr((string) request()?->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            // Never break business transactions if logging fails
        }
    }
}
