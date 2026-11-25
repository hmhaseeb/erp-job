<div>
    <div class="mb-4">
        <h1 class="text-gray-900 text-xl font-bold mb-1">Settings &amp; Company Configuration</h1>
        <p class="text-sm text-default mb-0">Centralized control for company profile, branding, currency, VAT/Tax, financial year, banks, payment methods, documents, and notifications.</p>
    </div>

    <!-- Settings Navigation Tabs -->
    <div class="inline-flex items-center gap-1.5 border border-border-color rounded-md p-1 mb-4 bg-white flex-wrap">
        @foreach ([
            'company' => ['label' => 'Company & Address', 'icon' => 'ph-buildings'],
            'branding' => ['label' => 'Logos & Branding', 'icon' => 'ph-image'],
            'localization' => ['label' => 'Currency, Date, FY & Language', 'icon' => 'ph-globe'],
            'tax_banks' => ['label' => 'Tax / VAT, Banks & Payments', 'icon' => 'ph-bank'],
            'documents' => ['label' => 'Invoice & Documents', 'icon' => 'ph-receipt'],
            'email_app' => ['label' => 'Email, Notifications & App', 'icon' => 'ph-gear-six'],
        ] as $key => $tab)
            <button type="button" wire:click="$set('activeTab', '{{ $key }}')" class="px-3 py-1.5 text-xs font-semibold rounded-md inline-flex items-center gap-1.5 cursor-pointer {{ $activeTab === $key ? 'bg-dark text-white' : 'text-gray-900 hover:bg-light' }}">
                <i class="ph-duotone {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    <form wire:submit="saveSettings">
        @if ($activeTab === 'company')
        <div class="bg-white border border-border-color rounded-md p-5 space-y-6">
            <div>
                <h2 class="text-base font-bold text-title mb-3">Company Name, Details &amp; Registration</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Company Legal Name *</label>
                        <input type="text" wire:model="company_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Trade / Brand Name *</label>
                        <input type="text" wire:model="trade_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Tagline</label>
                        <input type="text" wire:model="tagline" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Registration / CR No.</label>
                        <input type="text" wire:model="registration_no" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Tax / VAT / TRN Number</label>
                        <input type="text" wire:model="tax_number" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Trade License No.</label>
                        <input type="text" wire:model="license_no" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Business Type</label>
                        <input type="text" wire:model="business_type" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Industry</label>
                        <input type="text" wire:model="industry" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Website</label>
                        <input type="text" wire:model="website" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-border-color">
                <h2 class="text-base font-bold text-title mb-3">Company Contact Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Primary Email</label>
                        <input type="email" wire:model="email" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Support Email</label>
                        <input type="email" wire:model="support_email" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Phone</label>
                        <input type="text" wire:model="phone" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Mobile</label>
                        <input type="text" wire:model="mobile" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-border-color">
                <h2 class="text-base font-bold text-title mb-3">Company Address</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Address Line 1</label>
                        <input type="text" wire:model="address_line1" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Address Line 2</label>
                        <input type="text" wire:model="address_line2" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">City</label>
                        <input type="text" wire:model="city" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">State / Province</label>
                        <input type="text" wire:model="state" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Country</label>
                        <input type="text" wire:model="country" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Postal Code</label>
                        <input type="text" wire:model="postal_code" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-border-color">
                <button type="submit" class="btn-sm bg-dark text-white border border-dark hover:bg-primary-hover">Save Company Details</button>
            </div>
        </div>
        @endif

        @if ($activeTab === 'branding')
        <div class="bg-white border border-border-color rounded-md p-5 space-y-6">
            <h2 class="text-base font-bold text-title mb-3">Company Logo, Favicon &amp; Application Branding</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="border border-border-color rounded-md p-4">
                    <p class="text-sm font-semibold text-title mb-2">Light Theme Logo</p>
                    <img src="{{ $logoLightUpload ? $logoLightUpload->temporaryUrl() : erp_logo('light') }}" class="h-10 mb-3 bg-light p-1 rounded" alt="Light Logo">
                    <input type="file" wire:model="logoLightUpload" class="text-xs" accept="image/*">
                </div>
                <div class="border border-border-color rounded-md p-4">
                    <p class="text-sm font-semibold text-title mb-2">Dark Theme Logo</p>
                    <img src="{{ $logoDarkUpload ? $logoDarkUpload->temporaryUrl() : erp_logo('dark') }}" class="h-10 mb-3 bg-dark p-1 rounded" alt="Dark Logo">
                    <input type="file" wire:model="logoDarkUpload" class="text-xs" accept="image/*">
                </div>
                <div class="border border-border-color rounded-md p-4">
                    <p class="text-sm font-semibold text-title mb-2">Compact / Mini Sidebar Logo</p>
                    <img src="{{ $logoSmallUpload ? $logoSmallUpload->temporaryUrl() : erp_logo('small') }}" class="h-10 mb-3 bg-light p-1 rounded" alt="Small Logo">
                    <input type="file" wire:model="logoSmallUpload" class="text-xs" accept="image/*">
                </div>
                <div class="border border-border-color rounded-md p-4">
                    <p class="text-sm font-semibold text-title mb-2">Browser Favicon</p>
                    <img src="{{ $faviconUpload ? $faviconUpload->temporaryUrl() : erp_logo('favicon') }}" class="h-10 mb-3 bg-light p-1 rounded" alt="Favicon">
                    <input type="file" wire:model="faviconUpload" class="text-xs" accept="image/*,.ico">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-border-color">
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Default UI Theme</label>
                    <select wire:model="default_theme" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                        <option value="light">Light Mode</option>
                        <option value="dark">Dark Mode</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Application Footer Copyright</label>
                    <input type="text" wire:model="footer_text" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-border-color">
                <button type="submit" class="btn-sm bg-dark text-white border border-dark hover:bg-primary-hover">Save Branding</button>
            </div>
        </div>
        @endif

        @if ($activeTab === 'localization')
        <div class="bg-white border border-border-color rounded-md p-5 space-y-6">
            <div>
                <h2 class="text-base font-bold text-title mb-3">Currency Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Currency Code</label>
                        <input type="text" wire:model="currency_code" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white" placeholder="USD">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Currency Symbol</label>
                        <input type="text" wire:model="currency_symbol" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white" placeholder="$">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Symbol Position</label>
                        <select wire:model="currency_position" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                            <option value="before">Before Amount ($100.00)</option>
                            <option value="after">After Amount (100.00 $)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Decimal Places</label>
                        <input type="number" min="0" max="4" wire:model="currency_decimals" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Thousand Separator</label>
                        <input type="text" wire:model="thousand_separator" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Decimal Separator</label>
                        <input type="text" wire:model="decimal_separator" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-border-color">
                <h2 class="text-base font-bold text-title mb-3">Date, Time &amp; Language Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Date Format</label>
                        <select wire:model="date_format" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                            <option value="d M Y">25 Nov 2026 (d M Y)</option>
                            <option value="Y-m-d">2026-11-25 (Y-m-d)</option>
                            <option value="d/m/Y">25/11/2026 (d/m/Y)</option>
                            <option value="m/d/Y">11/25/2026 (m/d/Y)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Time Format</label>
                        <select wire:model="time_format" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                            <option value="h:i A">12-Hour (09:30 AM)</option>
                            <option value="H:i">24-Hour (09:30)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Timezone</label>
                        <input type="text" wire:model="timezone" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Language</label>
                        <select wire:model="language" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                            <option value="en">English (EN)</option>
                            <option value="ar">Arabic (AR)</option>
                            <option value="de">German (DE)</option>
                            <option value="fr">French (FR)</option>
                        </select>
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 mt-3 text-sm text-title cursor-pointer">
                    <input type="checkbox" wire:model="is_rtl" class="size-4 rounded border-border-color text-primary">
                    <span>Enable Right-to-Left (RTL) Layout Direction</span>
                </label>
            </div>

            <div class="pt-4 border-t border-border-color">
                <h2 class="text-base font-bold text-title mb-3">Financial Year Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Fiscal Year Label</label>
                        <input type="text" wire:model="fiscal_year_label" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Start Month (1-12)</label>
                        <input type="number" min="1" max="12" wire:model="fiscal_year_start_month" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">End Month (1-12)</label>
                        <input type="number" min="1" max="12" wire:model="fiscal_year_end_month" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Accounting Lock Date</label>
                        <input type="date" wire:model="fiscal_lock_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-border-color">
                <button type="submit" class="btn-sm bg-dark text-white border border-dark hover:bg-primary-hover">Save Localization &amp; FY</button>
            </div>
        </div>
        @endif

        @if ($activeTab === 'tax_banks')
        <div class="space-y-4">
            <!-- Global Tax / VAT Settings -->
            <div class="bg-white border border-border-color rounded-md p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-bold text-title mb-0">Tax / VAT Configuration</h2>
                    <button type="button" wire:click="openTaxModal" class="btn-sm bg-primary text-white border border-primary">+ Add Tax Rate</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Tax Label</label>
                        <input type="text" wire:model="tax_label" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-title mb-1 block">Default Rate (%)</label>
                        <input type="number" step="0.01" wire:model="default_tax_rate" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white">
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 text-sm text-title cursor-pointer">
                            <input type="checkbox" wire:model="tax_enabled" class="size-4 rounded border-border-color text-primary"> Tax Enabled
                        </label>
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 text-sm text-title cursor-pointer">
                            <input type="checkbox" wire:model="tax_inclusive" class="size-4 rounded border-border-color text-primary"> Tax Inclusive Pricing
                        </label>
                    </div>
                </div>
                <div class="overflow-x-auto border border-border-color rounded mb-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs bg-light border-b border-border-color">
                                <th class="text-left py-2 px-3">Tax Name</th>
                                <th class="text-left py-2 px-3">Code</th>
                                <th class="text-left py-2 px-3">Rate</th>
                                <th class="text-left py-2 px-3">Default</th>
                                <th class="text-right py-2 px-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($taxRates as $tr)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="py-2 px-3 font-medium text-title">{{ $tr->name }}</td>
                                    <td class="py-2 px-3 text-default">{{ $tr->code }}</td>
                                    <td class="py-2 px-3 text-default">{{ $tr->rate }}%</td>
                                    <td class="py-2 px-3">{!! $tr->is_default ? '<span class="text-[11px] bg-success-transparent text-success px-2 py-0.5 rounded">Default</span>' : '—' !!}</td>
                                    <td class="py-2 px-3 text-right space-x-1">
                                        <button type="button" wire:click="openTaxModal({{ $tr->id }})" class="btn-xsmall bg-light border border-border-color text-title">Edit</button>
                                        <button type="button" wire:click="deleteTaxRate({{ $tr->id }})" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn-sm bg-dark text-white border border-dark">Save Tax Settings</button>
                </div>
            </div>

            <!-- Bank Account Details -->
            <div class="bg-white border border-border-color rounded-md p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-bold text-title mb-0">Bank Account Details</h2>
                    <button type="button" wire:click="openBankModal" class="btn-sm bg-dark text-white border border-dark">+ Add Bank Account</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs bg-light border-b border-border-color">
                                <th class="text-left py-2 px-3">Account Name</th>
                                <th class="text-left py-2 px-3">Bank &amp; Branch</th>
                                <th class="text-left py-2 px-3">Account No / IBAN</th>
                                <th class="text-right py-2 px-3">Current Balance</th>
                                <th class="text-right py-2 px-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bankAccounts as $bank)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="py-2.5 px-3 font-semibold text-title">
                                        {{ $bank->account_name }}
                                        @if ($bank->is_default) <span class="text-[10px] bg-primary-transparent text-primary px-1.5 py-0.5 rounded ms-1">Default</span> @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-default">{{ $bank->bank_name }} ({{ $bank->branch ?? 'Main' }})</td>
                                    <td class="py-2.5 px-3 text-xs text-default">{{ $bank->account_number }} @if($bank->iban)<br>{{ $bank->iban }}@endif</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-title">{{ format_currency($bank->current_balance) }}</td>
                                    <td class="py-2.5 px-3 text-right space-x-1">
                                        <button type="button" wire:click="openBankModal({{ $bank->id }})" class="btn-xsmall bg-light border border-border-color text-title">Edit</button>
                                        <button type="button" wire:click="deleteBankAccount({{ $bank->id }})" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="bg-white border border-border-color rounded-md p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-bold text-title mb-0">Payment Methods</h2>
                    <button type="button" wire:click="openMethodModal" class="btn-sm bg-dark text-white border border-dark">+ Add Payment Method</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs bg-light border-b border-border-color">
                                <th class="text-left py-2 px-3">Method Name</th>
                                <th class="text-left py-2 px-3">Code</th>
                                <th class="text-left py-2 px-3">Book / Type</th>
                                <th class="text-left py-2 px-3">Linked Bank</th>
                                <th class="text-right py-2 px-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paymentMethods as $pm)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="py-2.5 px-3 font-semibold text-title">{{ $pm->name }}</td>
                                    <td class="py-2.5 px-3 text-default">{{ $pm->code }}</td>
                                    <td class="py-2.5 px-3 text-default">{{ ucfirst($pm->type) }}</td>
                                    <td class="py-2.5 px-3 text-default">{{ $pm->bankAccount?->account_name ?? 'Cash Drawer' }}</td>
                                    <td class="py-2.5 px-3 text-right space-x-1">
                                        <button type="button" wire:click="openMethodModal({{ $pm->id }})" class="btn-xsmall bg-light border border-border-color text-title">Edit</button>
                                        <button type="button" wire:click="deletePaymentMethod({{ $pm->id }})" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if ($activeTab === 'documents')
        <div class="bg-white border border-border-color rounded-md p-5 space-y-5">
            <h2 class="text-base font-bold text-title mb-3">Invoice &amp; Document Numbering Prefixes</h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div><label class="text-xs font-semibold text-title mb-1 block">Sales Order</label><input type="text" wire:model="sales_order_prefix" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                <div><label class="text-xs font-semibold text-title mb-1 block">Sales Invoice</label><input type="text" wire:model="sales_invoice_prefix" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                <div><label class="text-xs font-semibold text-title mb-1 block">Sales Return</label><input type="text" wire:model="sales_return_prefix" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                <div><label class="text-xs font-semibold text-title mb-1 block">Purchase Order</label><input type="text" wire:model="purchase_order_prefix" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                <div><label class="text-xs font-semibold text-title mb-1 block">Purchase Bill</label><input type="text" wire:model="purchase_invoice_prefix" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                <div><label class="text-xs font-semibold text-title mb-1 block">Purchase Return</label><input type="text" wire:model="purchase_return_prefix" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                <div><label class="text-xs font-semibold text-title mb-1 block">Job Card</label><input type="text" wire:model="job_card_prefix" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                <div><label class="text-xs font-semibold text-title mb-1 block">Employee ID</label><input type="text" wire:model="employee_prefix" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                <div><label class="text-xs font-semibold text-title mb-1 block">Receipt Voucher</label><input type="text" wire:model="receipt_prefix" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                <div><label class="text-xs font-semibold text-title mb-1 block">Payment Voucher</label><input type="text" wire:model="payment_prefix" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Default Invoice Terms &amp; Conditions</label>
                    <textarea wire:model="invoice_terms" rows="3" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-title mb-1 block">Document Footer Note</label>
                    <textarea wire:model="invoice_footer_note" rows="3" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></textarea>
                </div>
            </div>
            <div class="flex flex-wrap gap-6">
                <label class="inline-flex items-center gap-2 text-sm text-title"><input type="checkbox" wire:model="show_logo_on_docs" class="size-4"> Show Company Logo on Printed Documents</label>
                <label class="inline-flex items-center gap-2 text-sm text-title"><input type="checkbox" wire:model="show_tax_on_docs" class="size-4"> Show Tax Breakdown on Invoices</label>
            </div>
            <div class="flex justify-end pt-4 border-t border-border-color">
                <button type="submit" class="btn-sm bg-dark text-white border border-dark hover:bg-primary-hover">Save Document Settings</button>
            </div>
        </div>
        @endif

        @if ($activeTab === 'email_app')
        <div class="bg-white border border-border-color rounded-md p-5 space-y-6">
            <div>
                <h2 class="text-base font-bold text-title mb-3">Email &amp; SMTP Configuration</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div><label class="text-xs font-semibold text-title mb-1 block">SMTP Host</label><input type="text" wire:model="mail_host" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                    <div><label class="text-xs font-semibold text-title mb-1 block">SMTP Port</label><input type="number" wire:model="mail_port" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                    <div><label class="text-xs font-semibold text-title mb-1 block">Encryption</label><select wire:model="mail_encryption" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"><option value="tls">TLS</option><option value="ssl">SSL</option><option value="none">None</option></select></div>
                    <div><label class="text-xs font-semibold text-title mb-1 block">SMTP Username</label><input type="text" wire:model="mail_username" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                    <div><label class="text-xs font-semibold text-title mb-1 block">From Address</label><input type="email" wire:model="mail_from_address" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                    <div><label class="text-xs font-semibold text-title mb-1 block">From Name</label><input type="text" wire:model="mail_from_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                </div>
            </div>

            <div class="pt-4 border-t border-border-color">
                <h2 class="text-base font-bold text-title mb-3">Notification Triggers</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="notify_low_stock" class="size-4"> Low Stock Alerts</label>
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="notify_invoice_created" class="size-4"> Sales Invoice Created Alerts</label>
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="notify_payroll_processed" class="size-4"> Payroll Processed Alerts</label>
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="notify_job_card_updates" class="size-4"> Job Card Status &amp; Delivery Notifications</label>
                </div>
            </div>

            <div class="pt-4 border-t border-border-color">
                <h2 class="text-base font-bold text-title mb-3">General Application Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div><label class="text-xs font-semibold text-title mb-1 block">Default Low Stock Threshold</label><input type="number" wire:model="low_stock_threshold" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                    <div><label class="text-xs font-semibold text-title mb-1 block">Table Pagination Size</label><input type="number" min="5" max="100" wire:model="per_page" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                    <div><label class="text-xs font-semibold text-title mb-1 block">Session Timeout (mins)</label><input type="number" min="15" wire:model="session_timeout" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white"></div>
                    <div class="flex items-end pb-2"><label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="maintenance_mode" class="size-4"> Maintenance Flag</label></div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-border-color">
                <button type="submit" class="btn-sm bg-dark text-white border border-dark hover:bg-primary-hover">Save Application Settings</button>
            </div>
        </div>
        @endif
    </form>

    <!-- Bank Modal -->
    @if ($showBankModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-lg p-5">
            <h3 class="text-base font-bold text-title mb-3">{{ $editingBankId ? 'Edit Bank Account' : 'Add Bank Account' }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="text-xs font-semibold block mb-1">Account Name *</label><input type="text" wire:model="bank_account_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Bank Name *</label><input type="text" wire:model="bank_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Account Number *</label><input type="text" wire:model="bank_account_number" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Branch</label><input type="text" wire:model="bank_branch" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">IBAN</label><input type="text" wire:model="bank_iban" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">SWIFT Code</label><input type="text" wire:model="bank_swift" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                @if (! $editingBankId)
                <div><label class="text-xs font-semibold block mb-1">Opening Balance</label><input type="number" step="0.01" wire:model="bank_opening_balance" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                @endif
                <div class="flex items-end pb-2"><label class="flex items-center gap-2 text-xs"><input type="checkbox" wire:model="bank_is_default" class="size-4"> Set as Default Bank</label></div>
            </div>
            <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-border-color">
                <button type="button" wire:click="$set('showBankModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                <button type="button" wire:click="saveBankAccount" class="btn-sm bg-dark text-white border border-dark">Save Bank</button>
            </div>
        </div>
    </div>
    @endif

    <!-- Payment Method Modal -->
    @if ($showMethodModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-md p-5">
            <h3 class="text-base font-bold text-title mb-3">{{ $editingMethodId ? 'Edit Payment Method' : 'Add Payment Method' }}</h3>
            <div class="space-y-3">
                <div><label class="text-xs font-semibold block mb-1">Method Name *</label><input type="text" wire:model="pm_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Code *</label><input type="text" wire:model="pm_code" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Type</label><select wire:model="pm_type" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="cash">Cash</option><option value="bank">Bank Transfer</option><option value="card">Card</option><option value="cheque">Cheque</option><option value="online">Online</option></select></div>
                <div><label class="text-xs font-semibold block mb-1">Default Bank Account</label><select wire:model="pm_bank_account_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Cash Drawer (No Bank)</option>@foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->account_name }}</option>@endforeach</select></div>
            </div>
            <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-border-color">
                <button type="button" wire:click="$set('showMethodModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                <button type="button" wire:click="savePaymentMethod" class="btn-sm bg-dark text-white border border-dark">Save Method</button>
            </div>
        </div>
    </div>
    @endif

    <!-- Tax Rate Modal -->
    @if ($showTaxModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-md p-5">
            <h3 class="text-base font-bold text-title mb-3">{{ $editingTaxId ? 'Edit Tax Rate' : 'Add Tax Rate' }}</h3>
            <div class="space-y-3">
                <div><label class="text-xs font-semibold block mb-1">Tax Name *</label><input type="text" wire:model="tax_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Code *</label><input type="text" wire:model="tax_code" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Rate (%)</label><input type="number" step="0.01" wire:model="tax_rate_value" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <label class="flex items-center gap-2 text-xs"><input type="checkbox" wire:model="tax_is_default" class="size-4"> Set as Default Tax Rate</label>
            </div>
            <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-border-color">
                <button type="button" wire:click="$set('showTaxModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                <button type="button" wire:click="saveTaxRate" class="btn-sm bg-dark text-white border border-dark">Save Tax</button>
            </div>
        </div>
    </div>
    @endif
</div>
