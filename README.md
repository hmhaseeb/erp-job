# erp-job

Complete integrated **ERP (Enterprise Resource Planning)** web application built with **Laravel 12**, **Livewire 4**, **Tailwind CSS v4**, and **MySQL**.

## Modules Included

1. **Settings & Company Configuration** — Branding/logos/favicon, registration & tax settings, currencies, date/time formats, financial year, bank accounts, payment methods, document prefixes, notifications, and app settings.
2. **HRM (Human Resource Management)** — Employee directory & profiles, document uploads, full lifecycle management, departments & designations, attendance, leaves, and payroll processing with finance posting.
3. **Inventory Management** — Product management & multi-image gallery details page, categories & nested subcategories, brands & units, suppliers, stock in/out, stock adjustments, history, low stock alerts, and valuation reports.
4. **Sales Management** — Customers & individual customer profiles, sales orders, sales invoices/bills (duplicate product line prevention), payment collection, customer balances, sales returns, and sales reports.
5. **Purchase Management** — Vendors & individual vendor profiles, purchase orders, goods received (GRN), purchase bills, supplier payments, vendor balances, purchase returns, and purchase reports.
6. **Finance & Accounts Management** — Expenses & categories, income management, Daybook, Cash Book, Bank Book, Account Transactions ledger, payment/receipt vouchers, and financial statements (Profit & Loss, Balance Sheet, Cash Flow, Outstanding Reports).
7. **Job Card / Service Management** — Service job card registration (`Direct Shop` or `On-Site`), searchable customer selector & inline customer quick-add, multiple image attachments, progress tracking, spare parts usage, customer approval, quality checking, delivery, service billing, and payment history.
8. **User Management & Access Control** — Authentication, role & module-wise CRUD permission matrix, admin/staff accounts, and activity logs.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

### Demo Logins (`password`)
- Admin: `admin@dreamserp.com`
- Manager: `manager@dreamserp.com`
- Staff: `staff@dreamserp.com`
