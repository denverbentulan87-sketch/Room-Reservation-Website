# Pajuleras Boarding House Management System

A PHP + MySQL website for Pajuleras Boarding House (Poblacion, Inabanga, Bohol), built from the
group's documentation (Group 7, IS105). It has two sides:

* **Public website** (customers and tenants, no login): see rooms and live bed availability,
  read rates, requirements and house rules, **send an inquiry**, and later **read the landlord's
  reply and send follow-up messages** using an inquiry code.
* **Admin panel** (`/admin`): **one account only** (the landlord). Manages rooms, tenants,
  reservations, room assignment, check-in/check-out, payments and receipts, inquiries, reports.

Customers can never log in or change anything. There is no registration and no second admin
(the database itself refuses a second admin row).

## Requirements
PHP 8.1 or newer (with PDO MySQL, fileinfo, mbstring) and MySQL 5.7+ / MySQL 8 / MariaDB 10.3+.
XAMPP (current version) already includes everything.

## Install with XAMPP (Windows)
1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this whole folder to `C:\xampp\htdocs\` and keep the name **pajuleras_bh**.
3. Open **http://localhost/phpmyadmin** > **Import**:
   1. choose `database/01_schema.sql` > **Go** (creates database `pajuleras_bh`, tables, the admin account)
   2. *(optional demo data)* choose `database/02_sample_data.sql` > **Go**
4. Open **http://localhost/pajuleras_bh/**
5. Landlord sign in: **http://localhost/pajuleras_bh/admin/login.php**
   * username `admin`, password `Pajuleras@2026`
   * You are **forced to choose a new password** at first sign-in.

If your MySQL has a root password (or a different port), edit `config/config.php`.

### Without XAMPP (quick test)
```
mysql -u root -p < database/01_schema.sql
mysql -u root -p < database/02_sample_data.sql      # optional
php -S localhost:8080
```
then open http://localhost:8080 (run the command inside this folder).

## First things to do as the landlord
1. Sign in, set your new password.
2. **Settings > Boarding house**: real name, phone, address, GCash number.
3. **Settings > Rates, requirements & rules**: the text customers see; the advance payment (months).
4. **Rooms**: the demo rooms, rates and tenants are **made-up samples**. Edit or delete them, add
   your real rooms (beds, rent per bed, deposit, who may stay, photo).
   *If you skipped the sample data, the site starts empty: add rooms first.*

## How it works (matches the documentation)
| Step in the process | In the system |
|---|---|
| Customer asks about rooms | **Send an inquiry** (public) > shows an inquiry code |
| Landlord checks availability and replies | **Inquiries** inbox > reply; customer reads it via **Check my inquiry** |
| Collect customer details | **Register tenant** (prefilled from the inquiry) |
| Confirm room / record reservation | **Reservation**: pending > **Confirm** (room is held) |
| Advance payment | **Record payment** > official receipt (printable) |
| Room assignment | Room chosen on the reservation; **Transfer room** later if needed |
| Move in / move out | **Check in** / **Check out** (room status updates automatically) |
| Reports | **Reports**: occupancy, payments, reservations, unpaid balances, tenants (print or CSV) |

**Room status** is automatic: *Available* (open beds), *Reserved* (all beds held by confirmed
reservations), *Occupied* (all beds checked in), *Under maintenance* (set by the landlord).
Shared rooms have several beds, so one room can be partly occupied.

**No double booking.** A reservation holds beds only when *confirmed*. Every confirm, edit,
check-in and transfer re-checks the room's real date ranges and locks the room, so two people
(or two browser tabs) can never take the last bed at the same time. A new tenant may move in
on the day another moves out.

**Billing.** Rent is billed monthly in advance from the check-in date. Balance =
(deposit + rent for each started month) minus (advance + deposit + rent paid). Refunds are limited
to the deposit and rent paid ahead. Payments are never deleted; mistakes are **voided** with a reason.

## Security built in
* Single admin, hashed password (bcrypt), forced change of the default password, session timeout (30 min)
* Login lock after 5 wrong tries; limits on inquiry spam and inquiry-code guessing
* CSRF protection on every form, prepared SQL statements everywhere, all output escaped
* Strict Content-Security-Policy, secure session cookies, upload validation (real image check, random names)
* `includes/`, `config/`, `database/` and `uploads/` are blocked from direct web access (`.htaccess`)

**Before going online:** use HTTPS, set a strong MySQL password (update `config/config.php`), and keep
`'debug' => false`. The site does not send emails (XAMPP has no mail server); the landlord replies
inside the system and can also call or text the number the customer left.

## Folder guide
```
index.php rooms.php room.php inquire.php track.php policies.php   public pages
admin/        landlord panel
includes/     shared code (database, security, availability, billing, layout)
config/       database settings
database/     01_schema.sql, 02_sample_data.sql
assets/       css, js, fonts, images
uploads/      room photos uploaded by the landlord
```
