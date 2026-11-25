<?php

namespace App\Livewire\Settings;

use App\Models\BankAccount;
use App\Models\CompanySetting;
use App\Models\PaymentMethod;
use App\Models\TaxRate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Settings & Company Configuration')]
class CompanySettingsManager extends Component
{
    use WithFileUploads;

    public string $activeTab = 'company';

    // Company, Registration, Contact, Address
    public string $company_name = '';
    public string $trade_name = '';
    public string $tagline = '';
    public string $registration_no = '';
    public string $tax_number = '';
    public string $license_no = '';
    public string $business_type = '';
    public string $industry = '';
    public string $website = '';
    public string $email = '';
    public string $support_email = '';
    public string $phone = '';
    public string $mobile = '';
    public string $address_line1 = '';
    public string $address_line2 = '';
    public string $city = '';
    public string $state = '';
    public string $country = '';
    public string $postal_code = '';

    // Branding & Logos
    public $logoLightUpload;
    public $logoDarkUpload;
    public $logoSmallUpload;
    public $faviconUpload;
    public string $default_theme = 'light';
    public string $footer_text = '';

    // Currency, Date/Time, Language, Financial Year
    public string $currency_code = 'USD';
    public string $currency_symbol = '$';
    public string $currency_position = 'before';
    public int $currency_decimals = 2;
    public string $thousand_separator = ',';
    public string $decimal_separator = '.';
    public string $date_format = 'd M Y';
    public string $time_format = 'h:i A';
    public string $timezone = 'UTC';
    public string $language = 'en';
    public bool $is_rtl = false;
    public string $fiscal_year_label = '2026-2027';
    public int $fiscal_year_start_month = 1;
    public int $fiscal_year_end_month = 12;
    public ?string $fiscal_lock_date = null;

    // Tax / VAT Settings
    public bool $tax_enabled = true;
    public string $tax_label = 'VAT';
    public float $default_tax_rate = 5.0;
    public bool $tax_inclusive = false;

    // Invoice & Document Settings
    public string $sales_order_prefix = 'SO-';
    public string $sales_invoice_prefix = 'INV-';
    public string $sales_return_prefix = 'SR-';
    public string $purchase_order_prefix = 'PO-';
    public string $purchase_invoice_prefix = 'PINV-';
    public string $purchase_return_prefix = 'PR-';
    public string $job_card_prefix = 'JC-';
    public string $employee_prefix = 'EMP-';
    public string $receipt_prefix = 'RCP-';
    public string $payment_prefix = 'PAY-';
    public string $invoice_terms = '';
    public string $invoice_footer_note = '';
    public bool $show_logo_on_docs = true;
    public bool $show_tax_on_docs = true;

    // Email, Notification & General App Settings
    public string $mail_host = '';
    public int $mail_port = 587;
    public string $mail_username = '';
    public string $mail_encryption = 'tls';
    public string $mail_from_address = '';
    public string $mail_from_name = '';
    public bool $notify_low_stock = true;
    public bool $notify_invoice_created = true;
    public bool $notify_payroll_processed = true;
    public bool $notify_job_card_updates = true;
    public int $low_stock_threshold = 10;
    public int $per_page = 10;
    public bool $maintenance_mode = false;
    public int $session_timeout = 120;

    // Bank Account Modal / Form
    public bool $showBankModal = false;
    public ?int $editingBankId = null;
    public string $bank_account_name = '';
    public string $bank_name = '';
    public string $bank_account_number = '';
    public string $bank_iban = '';
    public string $bank_swift = '';
    public string $bank_branch = '';
    public float $bank_opening_balance = 0;
    public bool $bank_is_default = false;

    // Payment Method Modal / Form
    public bool $showMethodModal = false;
    public ?int $editingMethodId = null;
    public string $pm_name = '';
    public string $pm_code = '';
    public string $pm_type = 'bank';
    public ?int $pm_bank_account_id = null;
    public bool $pm_is_active = true;

    // Tax Rate Modal / Form
    public bool $showTaxModal = false;
    public ?int $editingTaxId = null;
    public string $tax_name = '';
    public string $tax_code = '';
    public float $tax_rate_value = 5.0;
    public bool $tax_is_default = false;

    public function mount(): void
    {
        $s = CompanySetting::first() ?? new CompanySetting();
        foreach ([
            'company_name', 'trade_name', 'tagline', 'registration_no', 'tax_number', 'license_no',
            'business_type', 'industry', 'website', 'email', 'support_email', 'phone', 'mobile',
            'address_line1', 'address_line2', 'city', 'state', 'country', 'postal_code',
            'default_theme', 'footer_text', 'currency_code', 'currency_symbol', 'currency_position',
            'thousand_separator', 'decimal_separator', 'date_format', 'time_format', 'timezone',
            'language', 'fiscal_year_label', 'tax_label', 'sales_order_prefix', 'sales_invoice_prefix',
            'sales_return_prefix', 'purchase_order_prefix', 'purchase_invoice_prefix', 'purchase_return_prefix',
            'job_card_prefix', 'employee_prefix', 'receipt_prefix', 'payment_prefix', 'invoice_terms',
            'invoice_footer_note', 'mail_host', 'mail_username', 'mail_encryption', 'mail_from_address', 'mail_from_name',
        ] as $field) {
            $this->{$field} = (string) ($s->{$field} ?? $this->{$field});
        }

        $this->currency_decimals = (int) ($s->currency_decimals ?? 2);
        $this->fiscal_year_start_month = (int) ($s->fiscal_year_start_month ?? 1);
        $this->fiscal_year_end_month = (int) ($s->fiscal_year_end_month ?? 12);
        $this->fiscal_lock_date = $s->fiscal_lock_date?->format('Y-m-d');
        $this->tax_enabled = (bool) ($s->tax_enabled ?? true);
        $this->default_tax_rate = (float) ($s->default_tax_rate ?? 5.0);
        $this->tax_inclusive = (bool) ($s->tax_inclusive ?? false);
        $this->is_rtl = (bool) ($s->is_rtl ?? false);
        $this->show_logo_on_docs = (bool) ($s->show_logo_on_docs ?? true);
        $this->show_tax_on_docs = (bool) ($s->show_tax_on_docs ?? true);
        $this->mail_port = (int) ($s->mail_port ?? 587);
        $this->notify_low_stock = (bool) ($s->notify_low_stock ?? true);
        $this->notify_invoice_created = (bool) ($s->notify_invoice_created ?? true);
        $this->notify_payroll_processed = (bool) ($s->notify_payroll_processed ?? true);
        $this->notify_job_card_updates = (bool) ($s->notify_job_card_updates ?? true);
        $this->low_stock_threshold = (int) ($s->low_stock_threshold ?? 10);
        $this->per_page = (int) ($s->per_page ?? 10);
        $this->maintenance_mode = (bool) ($s->maintenance_mode ?? false);
        $this->session_timeout = (int) ($s->session_timeout ?? 120);
    }

    public function saveSettings(): void
    {
        abort_unless(auth()->user()->hasPermission('settings', 'edit'), 403);

        $this->validate([
            'company_name' => ['required', 'string', 'max:190'],
            'trade_name' => ['required', 'string', 'max:190'],
            'currency_code' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'logoLightUpload' => ['nullable', 'image', 'max:2048'],
            'logoDarkUpload' => ['nullable', 'image', 'max:2048'],
            'logoSmallUpload' => ['nullable', 'image', 'max:2048'],
            'faviconUpload' => ['nullable', 'file', 'mimes:png,ico,svg,jpg', 'max:1024'],
        ]);

        $setting = CompanySetting::first() ?? new CompanySetting();

        $payload = [
            'company_name' => $this->company_name,
            'trade_name' => $this->trade_name,
            'tagline' => $this->tagline,
            'registration_no' => $this->registration_no,
            'tax_number' => $this->tax_number,
            'license_no' => $this->license_no,
            'business_type' => $this->business_type,
            'industry' => $this->industry,
            'website' => $this->website,
            'email' => $this->email,
            'support_email' => $this->support_email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'default_theme' => $this->default_theme,
            'footer_text' => $this->footer_text,
            'currency_code' => $this->currency_code,
            'currency_symbol' => $this->currency_symbol,
            'currency_position' => $this->currency_position,
            'currency_decimals' => $this->currency_decimals,
            'thousand_separator' => $this->thousand_separator,
            'decimal_separator' => $this->decimal_separator,
            'date_format' => $this->date_format,
            'time_format' => $this->time_format,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'is_rtl' => $this->is_rtl,
            'fiscal_year_label' => $this->fiscal_year_label,
            'fiscal_year_start_month' => $this->fiscal_year_start_month,
            'fiscal_year_end_month' => $this->fiscal_year_end_month,
            'fiscal_lock_date' => $this->fiscal_lock_date ?: null,
            'tax_enabled' => $this->tax_enabled,
            'tax_label' => $this->tax_label,
            'default_tax_rate' => $this->default_tax_rate,
            'tax_inclusive' => $this->tax_inclusive,
            'sales_order_prefix' => $this->sales_order_prefix,
            'sales_invoice_prefix' => $this->sales_invoice_prefix,
            'sales_return_prefix' => $this->sales_return_prefix,
            'purchase_order_prefix' => $this->purchase_order_prefix,
            'purchase_invoice_prefix' => $this->purchase_invoice_prefix,
            'purchase_return_prefix' => $this->purchase_return_prefix,
            'job_card_prefix' => $this->job_card_prefix,
            'employee_prefix' => $this->employee_prefix,
            'receipt_prefix' => $this->receipt_prefix,
            'payment_prefix' => $this->payment_prefix,
            'invoice_terms' => $this->invoice_terms,
            'invoice_footer_note' => $this->invoice_footer_note,
            'show_logo_on_docs' => $this->show_logo_on_docs,
            'show_tax_on_docs' => $this->show_tax_on_docs,
            'mail_host' => $this->mail_host,
            'mail_port' => $this->mail_port,
            'mail_username' => $this->mail_username,
            'mail_encryption' => $this->mail_encryption,
            'mail_from_address' => $this->mail_from_address,
            'mail_from_name' => $this->mail_from_name,
            'notify_low_stock' => $this->notify_low_stock,
            'notify_invoice_created' => $this->notify_invoice_created,
            'notify_payroll_processed' => $this->notify_payroll_processed,
            'notify_job_card_updates' => $this->notify_job_card_updates,
            'low_stock_threshold' => $this->low_stock_threshold,
            'per_page' => $this->per_page,
            'maintenance_mode' => $this->maintenance_mode,
            'session_timeout' => $this->session_timeout,
        ];

        if ($this->logoLightUpload) {
            $payload['logo_light'] = $this->logoLightUpload->store('branding', 'public');
        }
        if ($this->logoDarkUpload) {
            $payload['logo_dark'] = $this->logoDarkUpload->store('branding', 'public');
        }
        if ($this->logoSmallUpload) {
            $payload['logo_small'] = $this->logoSmallUpload->store('branding', 'public');
        }
        if ($this->faviconUpload) {
            $payload['favicon'] = $this->faviconUpload->store('branding', 'public');
        }

        $setting->fill($payload)->save();
        $this->reset(['logoLightUpload', 'logoDarkUpload', 'logoSmallUpload', 'faviconUpload']);
        erp_settings_clear_cache();
        log_activity('settings', 'updated', "Updated ERP configuration (Tab: {$this->activeTab}).", $setting);
        $this->dispatch('toast', message: 'Company & application settings saved.', type: 'success');
    }

    // Bank Accounts CRUD
    public function openBankModal(?int $id = null): void
    {
        if ($id) {
            $b = BankAccount::findOrFail($id);
            $this->editingBankId = $b->id;
            $this->bank_account_name = $b->account_name;
            $this->bank_name = $b->bank_name;
            $this->bank_account_number = $b->account_number;
            $this->bank_iban = (string) ($b->iban ?? '');
            $this->bank_swift = (string) ($b->swift_code ?? '');
            $this->bank_branch = (string) ($b->branch ?? '');
            $this->bank_opening_balance = (float) $b->opening_balance;
            $this->bank_is_default = (bool) $b->is_default;
        } else {
            $this->reset(['editingBankId', 'bank_account_name', 'bank_name', 'bank_account_number', 'bank_iban', 'bank_swift', 'bank_branch']);
            $this->bank_opening_balance = 0;
            $this->bank_is_default = false;
        }
        $this->showBankModal = true;
    }

    public function saveBankAccount(): void
    {
        $this->validate([
            'bank_account_name' => ['required', 'string', 'max:150'],
            'bank_name' => ['required', 'string', 'max:150'],
            'bank_account_number' => ['required', 'string', 'max:80'],
        ]);

        if ($this->bank_is_default) {
            BankAccount::query()->update(['is_default' => false]);
        }

        if ($this->editingBankId) {
            BankAccount::findOrFail($this->editingBankId)->update([
                'account_name' => $this->bank_account_name,
                'bank_name' => $this->bank_name,
                'account_number' => $this->bank_account_number,
                'iban' => $this->bank_iban,
                'swift_code' => $this->bank_swift,
                'branch' => $this->bank_branch,
                'is_default' => $this->bank_is_default,
            ]);
        } else {
            BankAccount::create([
                'account_name' => $this->bank_account_name,
                'bank_name' => $this->bank_name,
                'account_number' => $this->bank_account_number,
                'iban' => $this->bank_iban,
                'swift_code' => $this->bank_swift,
                'branch' => $this->bank_branch,
                'opening_balance' => $this->bank_opening_balance,
                'current_balance' => $this->bank_opening_balance,
                'is_default' => $this->bank_is_default,
                'status' => 'active',
            ]);
        }
        $this->showBankModal = false;
        log_activity('settings', 'bank_saved', "Saved bank account {$this->bank_account_name}");
        $this->dispatch('toast', message: 'Bank account saved.', type: 'success');
    }

    public function deleteBankAccount(int $id): void
    {
        BankAccount::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Bank account removed.', type: 'success');
    }

    // Payment Methods CRUD
    public function openMethodModal(?int $id = null): void
    {
        if ($id) {
            $pm = PaymentMethod::findOrFail($id);
            $this->editingMethodId = $pm->id;
            $this->pm_name = $pm->name;
            $this->pm_code = $pm->code;
            $this->pm_type = $pm->type;
            $this->pm_bank_account_id = $pm->bank_account_id;
            $this->pm_is_active = (bool) $pm->is_active;
        } else {
            $this->reset(['editingMethodId', 'pm_name', 'pm_code', 'pm_bank_account_id']);
            $this->pm_type = 'bank';
            $this->pm_is_active = true;
        }
        $this->showMethodModal = true;
    }

    public function savePaymentMethod(): void
    {
        $this->validate([
            'pm_name' => ['required', 'string', 'max:100'],
            'pm_code' => ['required', 'string', 'max:40', 'unique:payment_methods,code'.($this->editingMethodId ? ','.$this->editingMethodId : '')],
            'pm_type' => ['required', 'in:cash,bank,card,cheque,online'],
        ]);

        PaymentMethod::updateOrCreate(
            ['id' => $this->editingMethodId],
            [
                'name' => $this->pm_name,
                'code' => strtoupper($this->pm_code),
                'type' => $this->pm_type,
                'bank_account_id' => $this->pm_bank_account_id ?: null,
                'is_active' => $this->pm_is_active,
            ]
        );
        $this->showMethodModal = false;
        $this->dispatch('toast', message: 'Payment method saved.', type: 'success');
    }

    public function deletePaymentMethod(int $id): void
    {
        PaymentMethod::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Payment method deleted.', type: 'success');
    }

    // Tax Rates CRUD
    public function openTaxModal(?int $id = null): void
    {
        if ($id) {
            $t = TaxRate::findOrFail($id);
            $this->editingTaxId = $t->id;
            $this->tax_name = $t->name;
            $this->tax_code = $t->code;
            $this->tax_rate_value = (float) $t->rate;
            $this->tax_is_default = (bool) $t->is_default;
        } else {
            $this->reset(['editingTaxId', 'tax_name', 'tax_code']);
            $this->tax_rate_value = 5.0;
            $this->tax_is_default = false;
        }
        $this->showTaxModal = true;
    }

    public function saveTaxRate(): void
    {
        $this->validate([
            'tax_name' => ['required', 'string', 'max:100'],
            'tax_code' => ['required', 'string', 'max:30', 'unique:tax_rates,code'.($this->editingTaxId ? ','.$this->editingTaxId : '')],
            'tax_rate_value' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($this->tax_is_default) {
            TaxRate::query()->update(['is_default' => false]);
        }

        TaxRate::updateOrCreate(
            ['id' => $this->editingTaxId],
            [
                'name' => $this->tax_name,
                'code' => strtoupper($this->tax_code),
                'rate' => $this->tax_rate_value,
                'is_default' => $this->tax_is_default,
                'is_active' => true,
            ]
        );
        $this->showTaxModal = false;
        $this->dispatch('toast', message: 'Tax rate saved.', type: 'success');
    }

    public function deleteTaxRate(int $id): void
    {
        TaxRate::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Tax rate deleted.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.company-settings-manager', [
            'bankAccounts' => BankAccount::orderByDesc('is_default')->orderBy('account_name')->get(),
            'paymentMethods' => PaymentMethod::with('bankAccount')->orderBy('name')->get(),
            'taxRates' => TaxRate::orderBy('rate')->get(),
        ]);
    }
}
