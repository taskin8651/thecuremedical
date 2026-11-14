# The Cure Medicall Hall

Laravel 10 pharmacy website and admin panel. It uses MariaDB/MySQL, Blade, Bootstrap, Spatie Media Library, and Spatie Permission.

## Local setup

1. Install PHP 8.2+, Composer 2, and MariaDB/MySQL. Enable `gd`, `mbstring`, `pdo_mysql`, `fileinfo`, and `zip`.
2. Install dependencies with `composer install`.
3. Copy `.env.example` to `.env`, set `APP_URL`, and configure `DB_*` for a database you created.
4. Run `php artisan key:generate`, `php artisan migrate --seed`, `php artisan storage:link`, then `php artisan serve`.
5. Set `CURE_ADMIN_NAME`, `CURE_ADMIN_EMAIL`, and `CURE_ADMIN_PASSWORD` in `.env` before seeding to create the first admin.

Public website: `/`. Staff panel: `/login` and `/admin`. Customer account: `/account/register` and `/account/login`.

## Customer shopping

- Visitors can keep a cart before signing in. It merges into the customer's cart after registration or login.
- Customers can verify their email, manage contact/address details, reset their password, place orders, view order status, and cancel pending unpaid orders.
- Checkout supports store pickup and configured delivery zones, eligible coupons, pay-at-store, and cash on delivery. No card details or online payment credentials are collected.
- New orders remain pending until staff confirmation. Stock is deducted and restored through `InventoryService` when staff changes order status.
- Products are orderable only after a pharmacist verifies them, only when they are not prescription-only, and only when a confirmed price is set. Prescription products remain enquiry/store-only.
- Online ordering remains off by default. A Super Admin must enable it in Settings after confirming the store's licensing and delivery requirements. Checkout also requires public prices to be enabled.
- Customer email verification and password reset links use the configured `MAIL_*` settings. With `MAIL_MAILER=log`, messages are written to `storage/logs/laravel.log`.

## Admin features

Role-based staff access; product/category/brand management; Brand logos and product photos through Media Library; stock batches and ledger; customers and enquiries; content management; orders and staff-recorded payments; reports, settings, notifications, and activity history.

## Notes

- Create the database before running migrations. The application does not create a MySQL database automatically.
- Ordering, payment recording, and prescription workflows are feature flags. They are disabled by default and are not enabled by the setup command.
- Run the scheduler each minute for expiry alerts: `php artisan schedule:run`.
