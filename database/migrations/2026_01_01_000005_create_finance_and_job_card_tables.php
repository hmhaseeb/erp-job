<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 30)->unique();
            $table->string('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_no', 40)->unique();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('title');
            $table->date('expense_date');
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->string('attachment')->nullable();
            $table->string('status', 20)->default('approved');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->string('income_no', 40)->unique();
            $table->string('category', 80);
            $table->string('title');
            $table->date('income_date');
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('received');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no', 40)->unique();
            $table->date('transaction_date');
            $table->string('book_type', 20)->default('cash'); // cash, bank
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('type', 20); // inflow, outflow
            $table->string('category', 80);
            $table->string('party_type', 40)->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('party_name')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no', 60)->nullable();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['transaction_date', 'book_type']);
        });

        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->string('job_card_no', 40)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('item_name');
            $table->string('brand_model')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('accessories_received')->nullable();
            $table->text('service_description');
            $table->text('diagnosis_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
            $table->string('status', 30)->default('registered'); // registered, diagnosed, awaiting_approval, in_progress, quality_check, completed, delivered, cancelled
            $table->unsignedTinyInteger('progress_percent')->default(10);
            $table->date('received_date');
            $table->date('expected_delivery_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('estimated_cost', 14, 2)->default(0);
            $table->decimal('labor_charges', 14, 2)->default(0);
            $table->decimal('parts_total', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('customer_approval_status', 20)->default('pending'); // pending, approved, rejected
            $table->timestamp('customer_approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->string('qc_status', 20)->default('pending'); // pending, passed, failed
            $table->foreignId('qc_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('qc_checked_at')->nullable();
            $table->text('qc_notes')->nullable();
            $table->string('delivered_to')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('job_card_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('part_name');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->boolean('stock_deducted')->default(false);
            $table->timestamps();
        });

        Schema::create('job_card_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->string('stage', 50);
            $table->string('status', 30);
            $table->text('notes')->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_logs');
        Schema::dropIfExists('job_card_parts');
        Schema::dropIfExists('job_cards');
        Schema::dropIfExists('account_transactions');
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
