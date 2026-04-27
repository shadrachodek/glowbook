# GlowBook

Premium standalone booking system for beauty salons, hair braiders, and service businesses. GlowBook helps salons manage services, add-ons, availability, customer profiles, deposits/retainers, Square payments, confirmations, and customer self-service from one polished WordPress plugin.

![Version](https://img.shields.io/badge/version-2.3.18-gold)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)

## Highlights

- **Standalone booking flow** with mobile-first service, add-on, date/time, details, Square checkout, and confirmation screens.
- **Square payments** for booking-time payments, retainers, saved-card support, receipts, and customer balance payments.
- **Configurable customer payments** for returning-customer and new-customer amounts, with optional automatic enforcement.
- **Daily booking limits** with global default, weekday-specific limits, and specific-date overrides.
- **Backend add-on filtering** so services only show valid add-ons without extra frontend hiding logic.
- **Customer portal** for viewing appointments, paying balances, rescheduling/cancelling when policy allows, and opening magic-link access.
- **Admin customer directory** with customer profiles, booking history, payment summaries, and WordPress-user linking context.
- **Import/export tools** for full backups and entity-level migration across services, add-ons, categories, customers, staff, availability, and bookings.
- **Booking-only admin roles** for staff/admin users who should only access GlowBook booking tools.
- **Responsive admin and frontend UI** designed to feel clean on desktop and phone.

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Square account for standalone card payments
- WooCommerce is optional for compatibility/integration, but GlowBook can run as a standalone booking system

## Installation

1. Upload the `glowbook` folder to `/wp-content/plugins/`.
2. Activate **GlowBook** from **WordPress Admin -> Plugins**.
3. Go to **Bookings -> Settings** and configure business, payment, notification, and portal settings.
4. Go to **Bookings -> Availability** and configure weekly schedule, daily limits, blocked dates, and date overrides.
5. Add services, categories, and add-ons from the GlowBook admin menu.

## Booking Pages

GlowBook supports the standalone booking URL, usually:

```text
/book/
```

You can also embed the booking flow with:

```text
[sodek_gb_booking_form]
```

or for a specific service:

```text
[gb_booking_form service_id="123"]
```

## Customer Portal

The customer portal is usually available at:

```text
/my-appointments/
```

Customers can access the portal with their booking email and a magic link. After a successful booking, GlowBook can generate a short-lived portal access link so customers can view their appointment without re-entering their email immediately.

## Payment Rules

GlowBook supports configurable booking-time payments:

- Returning customer payment amount, default `$50`.
- New customer payment amount, default `$150`.
- Optional enforcement based on customer history/email/phone.
- Manual mode where customers can choose the applicable option and staff verify later.
- 50% and full-payment options for customers who want to reduce or clear their balance.

## Availability And Limits

Availability includes:

- Weekly schedule.
- Minimum booking notice.
- Maximum advance booking window.
- Blocked dates.
- Global daily booking limit.
- Weekday-specific daily limits.
- Specific-date overrides for holidays, extensions, or special operating days.
- Add-on-aware duration checks for the main booking flow.

## Admin Tools

- Dashboard and calendar.
- Booking list and booking details.
- Services, categories, and add-ons.
- Customer directory and customer profile pages.
- Reports and analytics.
- Import/export and full backup tools.
- Staff and booking-only admin roles.
- Manual balance reconciliation for payments received in salon.

## Notifications

GlowBook includes booking confirmation, cancellation, reschedule, reminder, and admin notification support. Email delivery depends on the WordPress mail environment, so production sites should use a reliable SMTP/mail provider.

## Version 2.3.18

This release tightens the standalone production path:

- Hardened overlap locks so in-progress checkouts block overlapping times across services.
- Fixed timezone-sensitive booking and portal reschedule time calculations.
- Made staff availability add-on aware across AJAX and REST paths.
- Removed missing standalone asset enqueues and disabled the retired legacy standalone booking endpoint.
- Cleaned standalone page rendering for block themes and disabled public debug output in site config.

## Version 2.3.17

This release packages the production-ready standalone booking flow work:

- Hardened Square checkout and booking-time payment handling.
- Configurable returning/new customer payment amounts.
- Daily booking limits with weekday and date overrides.
- Add-on-aware frontend availability and checkout duration.
- Improved confirmation, customer portal, admin customer pages, reports, import/export, and mobile UI polish.
- Booking-only admin role support for staff/admin access.

## Known Follow-Up Cleanup

These items are known and planned for a later cleanup pass:

- Remove or hard-disable the legacy `sodek_gb_standalone_booking` AJAX endpoint.
- Remove or guard missing optional frontend asset enqueues for old staff/phone-verification scripts.
- Release temporary slot locks when transaction creation fails.
- Make portal reschedule validation fully add-on-duration aware.

## Development

Install development dependencies with Composer:

```bash
composer install
```

Useful scripts:

```bash
composer test
composer phpstan
composer phpcs
```

## License

This project is licensed under GPL v2 or later.

## Credits

Built by [Shadrach Odekhiran](https://shadrachodek.com).
