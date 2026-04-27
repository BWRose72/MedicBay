# MedicBay

MedicBay is a Laravel, Inertia, and Vue application for managing healthcare appointments. It supports patient registration, doctor discovery, appointment booking, role-based dashboards, doctor schedule management, reviews, and basic administration.

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Setup](#setup)
- [Environment Configuration](#environment-configuration)
- [Running the Application](#running-the-application)
- [Demo Accounts](#demo-accounts)
- [User Input Fields](#user-input-fields)
- [Common Commands](#common-commands)
- [Testing and Code Quality](#testing-and-code-quality)
- [Project Structure](#project-structure)
- [Troubleshooting](#troubleshooting)
- [License](#license)

## Features

- Public home and about pages
- Patient registration and login through Laravel Fortify
- Email verification, password reset, password confirmation, and two-factor authentication support
- Role-based access for admins, doctors, and patients
- Patient dashboard with past, current, and upcoming appointments
- Doctor dashboard with daily appointment overview
- Doctor listing with specialisation filtering
- Doctor profile pages with booking workflow
- Appointment cancellation and status updates
- Patient reviews for completed appointments
- Admin user management, including making users doctors, firing users, and deleting users
- Doctor profile, photo, schedule, and time-off management

## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Fortify
- Inertia.js 2
- Vue 3
- TypeScript
- Vite
- Tailwind CSS 4
- Spatie Laravel Permission
- SQLite by default, with MySQL, MariaDB, PostgreSQL, and SQL Server config available
- PHPUnit
- Laravel Pint

## Requirements

Install these before starting:

- PHP 8.2 or newer
- Composer
- Node.js 22 or newer is recommended
- npm
- SQLite PHP extension for the default database setup

Optional, depending on your local database choice:

- MySQL, MariaDB, PostgreSQL, or SQL Server
- Redis, if you change the cache or queue driver from the default database-backed setup

## Setup

Clone the repository and enter the project directory:

```bash
git clone <repository-url>
cd MedicBay
```

Install PHP dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Create the default SQLite database file:

```bash
touch database/database.sqlite
```

On Windows PowerShell:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force
```

Run migrations and seed the database:

```bash
php artisan migrate --seed
```

Create the public storage symlink for uploaded doctor photos and public storage assets:

```bash
php artisan storage:link
```

Install frontend dependencies:

```bash
npm install
```

Build frontend assets once:

```bash
npm run build
```

### Alternative Composer Setup

The project includes a Composer setup script:

```bash
composer run setup
```

This installs PHP dependencies, creates `.env` if missing, generates the app key, runs migrations, installs npm dependencies, and builds assets. If you use SQLite, make sure `database/database.sqlite` exists before migrations run.

## Environment Configuration

The default `.env.example` is configured for local development with SQLite:

```env
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=Europe/Sofia

DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log
```

Recommended local change:

```env
APP_NAME=MedicBay
APP_URL=http://127.0.0.1:8000
```

For MySQL or MariaDB, update the database section:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medicbay
DB_USERNAME=root
DB_PASSWORD=
```

After changing environment values, clear cached configuration:

```bash
php artisan config:clear
```

## Running the Application

For the full local development stack, run:

```bash
composer run dev
```

This starts:

- Laravel development server
- Queue listener
- Vite development server

The Laravel app is usually available at:

```text
http://127.0.0.1:8000
```

If you prefer running services separately:

```bash
php artisan serve
```

```bash
npm run dev
```

```bash
php artisan queue:listen --tries=1
```

## Demo Accounts

The seeders create these users:

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@example.com | admin1234 |
| Patient | ivanivanov@example.com | ii1234 |
| Patient | mariapetrova@example.com | mp1234 |
| Patient | dimitardimitrov@example.com | dd1234 |
| Doctor | georgi.nikolov@medicbay.bg | gn1234 |
| Doctor | elena.stoyanova@medicbay.bg | es1234 |
| Doctor | petar.dimitrov@medicbay.bg | pd1234 |

Seeded roles:

- `admin`
- `doctor`
- `patient`

Seeded specialisations:

- Cardiology
- Dermatology
- Endocrinology
- Gastroenterology
- Neurology
- Oncology
- Orthopaedics
- Paediatrics
- Psychiatry

## User Input Fields

Patient registration creates both a user account and a patient profile.

Required registration fields:

| Field | Purpose |
| --- | --- |
| Name | User display name and patient profile name |
| Email address | Login identifier and account email |
| Gender | Patient profile demographic field |
| Date of birth | Patient profile age and medical context |
| Password | Account password |
| Confirm password | Password confirmation |

Optional registration fields:

| Field | Purpose |
| --- | --- |
| Phone | Patient contact number |

Other important required fields in the application:

| Area | Required fields |
| --- | --- |
| Login | Email address, password |
| Appointment booking | Appointment start date and time |
| Appointment status updates | Status |
| Reviews | Attitude rating, professionalism rating |
| Doctor profile updates | Name, phone |
| Doctor schedule updates | Day of week, start time, end time |
| Doctor time-off updates | Start time, end time |
| Doctor photo upload | Image file in JPG, JPEG, PNG, or WebP format, up to 4 MB |
| Admin make-doctor action | Name, phone, specialisation |

## Common Commands

```bash
composer run dev
```

Start the Laravel server, queue listener, and Vite dev server together.

```bash
npm run dev
```

Start only the Vite dev server.

```bash
npm run build
```

Build production frontend assets.

```bash
php artisan migrate
```

Run pending database migrations.

```bash
php artisan migrate:fresh --seed
```

Rebuild the database from scratch and load seed data.

```bash
php artisan route:list
```

Display registered application routes.

```bash
php artisan storage:link
```

Expose files from `storage/app/public` through `public/storage`.

## Testing and Code Quality

Run the backend test suite and Laravel Pint checks:

```bash
composer test
```

Run Laravel Pint only:

```bash
composer run test:lint
```

Automatically format PHP code with Pint:

```bash
composer run lint
```

Format frontend resources:

```bash
npm run format
```

Check frontend formatting:

```bash
npm run format:check
```

Run ESLint with automatic fixes:

```bash
npm run lint
```

## Project Structure

```text
app/
  Actions/Fortify/        Fortify authentication actions
  Http/Controllers/       Request handlers for dashboards, doctors, appointments, settings, and admin pages
  Models/                 Eloquent models
  Services/               Domain services for doctors, appointments, reviews, schedules, and time off
config/                   Laravel configuration
database/
  migrations/             Database schema
  seeders/                Demo roles, users, doctors, patients, and specialisations
public/
  images/                 Public marketing images
resources/
  css/                    Application styles
  js/
    components/           Reusable Vue components
    layouts/              App and auth layouts
    pages/                Inertia page components
routes/                   Web and settings routes
tests/                    Feature and unit tests
```

## Troubleshooting

### SQLite database file is missing

If migrations fail because SQLite cannot open the database file, create it:

```bash
touch database/database.sqlite
```

On Windows PowerShell:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force
```

Then rerun:

```bash
php artisan migrate --seed
```

### Environment changes are not applied

Clear Laravel configuration cache:

```bash
php artisan config:clear
```

### Frontend assets are missing

Install dependencies and run Vite:

```bash
npm install
npm run dev
```

For production assets:

```bash
npm run build
```

### Uploaded doctor photos do not appear

Create the storage symlink:

```bash
php artisan storage:link
```

### PowerShell blocks `npx`

If PowerShell blocks `npx` because script execution is disabled, run npm scripts through `npm`:

```powershell
npm run dev
npm run build
npm run format
```

You can also use Command Prompt or Git Bash for Node tooling.

## License

This project is licensed under the MIT license, as declared in `composer.json`.
