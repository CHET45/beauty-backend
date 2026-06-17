# Beauty Booking API

Laravel REST API for a beauty‑salon booking app. Customers browse services, pick a
free time slot and leave a booking; they can later look up their bookings by phone
number. An administrator manages the service catalog and the bookings.

## Requirements

- PHP 8.3+
- Composer
- SQLite (default, zero‑config) or MySQL

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

`migrate --seed` creates the demo services and an admin user from `ADMIN_EMAIL` /
`ADMIN_PASSWORD` in `.env` (defaults: `admin@example.com` / `password`).

The local `.env` uses SQLite out of the box. For the bundled Docker/MySQL setup use
[Laravel Sail](https://laravel.com/docs/sail): `./vendor/bin/sail up`.

## API

### Public

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET`  | `/api/services` | Active services |
| `GET`  | `/api/available-slots?service_id=&date=` | Free time slots for a service on a date |
| `POST` | `/api/appointments` | Create a booking |
| `GET`  | `/api/appointments?phone=` | Bookings for a phone number (no auth by design) |

### Admin (Bearer token via Laravel Sanctum)

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST`   | `/api/admin/login` | Log in, returns a token |
| `GET`    | `/api/admin/me` | Current admin |
| `POST`   | `/api/admin/logout` | Revoke the current token |
| `GET`    | `/api/admin/services` | Full catalog (incl. inactive) |
| `POST`   | `/api/admin/services` | Create a service |
| `PUT`    | `/api/admin/services/{id}` | Update a service |
| `DELETE` | `/api/admin/services/{id}` | Delete a service (blocked if it has bookings) |
| `GET`    | `/api/admin/appointments?date=&status=&phone=` | List/filter bookings |
| `PATCH`  | `/api/admin/appointments/{id}/status` | Change booking status |
| `DELETE` | `/api/admin/appointments/{id}` | Delete a booking |

Booking statuses: `pending`, `confirmed`, `completed`, `cancelled`.

## Tests

```bash
php artisan test
```
