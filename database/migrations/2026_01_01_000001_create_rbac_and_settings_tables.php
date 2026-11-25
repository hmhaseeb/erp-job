<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module', 60);
            $table->string('feature', 100);
            $table->string('slug', 100)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 40)->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->foreignId('role_id')->nullable()->after('avatar')->constrained('roles')->nullOnDelete();
            $table->unsignedBigInteger('department_id')->nullable()->after('role_id');
            $table->string('user_type', 20)->default('staff')->after('department_id');
            $table->string('status', 20)->default('active')->after('user_type');
            $table->timestamp('last_login_at')->nullable()->after('status');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module', 60);
            $table->string('action', 60);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['module', 'created_at']);
        });

        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            // Company Details & Registration
            $table->string('company_name')->default('Dreams ERP Solutions');
            $table->string('trade_name')->default('Dreams ERP');
            $table->string('tagline')->nullable();
            $table->string('registration_no', 100)->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('license_no', 100)->nullable();
            $table->string('business_type', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('website')->nullable();
            // Contact Information
            $table->string('email')->nullable();
            $table->string('support_email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('mobile', 40)->nullable();
            // Address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('postal_code', 30)->nullable();
            // Logos & Branding
            $table->string('logo_light')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('logo_small')->nullable();
            $table->string('favicon')->nullable();
            $table->string('default_theme', 15)->default('light');
            $table->string('footer_text')->default('2026 © Dreams ERP. All Rights Reserved');
            // Currency
            $table->string('currency_code', 10)->default('USD');
            $table->string('currency_symbol', 10)->default('$');
            $table->string('currency_position', 10)->default('before');
            $table->unsignedTinyInteger('currency_decimals')->default(2);
            $table->string('thousand_separator', 5)->default(',');
            $table->string('decimal_separator', 5)->default('.');
            // Tax / VAT
            $table->boolean('tax_enabled')->default(true);
            $table->string('tax_label', 30)->default('VAT');
            $table->decimal('default_tax_rate', 5, 2)->default(5.00);
            $table->boolean('tax_inclusive')->default(false);
            // Date, Time, Language, Financial Year
            $table->string('date_format', 30)->default('d M Y');
            $table->string('time_format', 30)->default('h:i A');
            $table->string('timezone', 60)->default('UTC');
            $table->string('language', 10)->default('en');
            $table->boolean('is_rtl')->default(false);
            $table->string('fiscal_year_label', 40)->default('2026-2027');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1);
            $table->unsignedTinyInteger('fiscal_year_end_month')->default(12);
            $table->date('fiscal_lock_date')->nullable();
            // Invoice & Document Settings
            $table->string('sales_order_prefix', 20)->default('SO-');
            $table->string('sales_invoice_prefix', 20)->default('INV-');
            $table->string('sales_return_prefix', 20)->default('SR-');
            $table->string('purchase_order_prefix', 20)->default('PO-');
            $table->string('purchase_invoice_prefix', 20)->default('PINV-');
            $table->string('purchase_return_prefix', 20)->default('PR-');
            $table->string('job_card_prefix', 20)->default('JC-');
            $table->string('employee_prefix', 20)->default('EMP-');
            $table->string('receipt_prefix', 20)->default('RCP-');
            $table->string('payment_prefix', 20)->default('PAY-');
            $table->text('invoice_terms')->nullable();
            $table->text('invoice_footer_note')->nullable();
            $table->boolean('show_logo_on_docs')->default(true);
            $table->boolean('show_tax_on_docs')->default(true);
            // Email & Notifications
            $table->string('mail_host')->nullable();
            $table->integer('mail_port')->default(587);
            $table->string('mail_username')->nullable();
            $table->string('mail_encryption', 20)->default('tls');
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->boolean('notify_low_stock')->default(true);
            $table->boolean('notify_invoice_created')->default(true);
            $table->boolean('notify_payroll_processed')->default(true);
            $table->boolean('notify_job_card_updates')->default(true);
            // General Application Settings
            $table->unsignedInteger('low_stock_threshold')->default(10);
            $table->unsignedInteger('per_page')->default(10);
            $table->boolean('maintenance_mode')->default(false);
            $table->unsignedInteger('session_timeout')->default(120);
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('bank_name');
            $table->string('account_number', 80);
            $table->string('iban', 80)->nullable();
            $table->string('swift_code', 40)->nullable();
            $table->string('branch')->nullable();
            $table->string('account_type', 40)->default('checking');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->string('type', 30)->default('cash'); // cash, bank, card, cheque, online
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->decimal('rate', 6, 2)->default(0);
            $table->string('type', 20)->default('percentage');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('activity_logs');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['phone', 'avatar', 'department_id', 'user_type', 'status', 'last_login_at']);
        });
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
