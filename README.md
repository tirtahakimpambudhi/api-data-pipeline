

# Elastic Connector Alert

> A lightweight service to store and manage application configuration centrally across services.

---

## Table of Contents

* [Overview](#overview)
* [Features](#features)
* [Tech Stack](#tech-stack)
* [Architecture](#architecture)
* [Project Structure](#project-structure)
* [Getting Started](#getting-started)

  * [Prerequisites](#prerequisites)
  * [Installation](#installation)
  * [Configuration](#configuration)
  * [Run Locally](#run-locally)
  * [Seeding & Route Generation](#seeding--route-generation)
  * [Available Scripts](#available-scripts)
* [Database](#database)
* [Deployment](#deployment)

---

## Overview

Aino SVC (Service Configuration) is a lightweight internal tool to **store and manage configuration** for applications and services. It provides a simple UI and API to read/update config values with auditability.

## Features

* CRUD for namespace, environment, channel, service, service environment, and configuration
* Role-based access for editing
* Email notifications

## Tech Stack

**Core:**

* **Backend:** Laravel 12
* **Frontend:** Inertia.js + React
* **UI Components:** shadcn/ui
* **Routing:** Wayfinder
* **Build Tool:** Vite
* **Database:** SQLite
* **Mail:** Laravel Mailer (SMTP)

> Package manager: **npm**

## Architecture

```
[ React UI (shadcn/ui) ] --Inertia--> [ Laravel Controllers ] --> [ Services ] --> [ SQLite DB file ]
                                      |-> [ Wayfinder routing ]
                                      |-> [ Mailer (SMTP) ]
```

* **Inertia** bridges React pages to Laravel routes/controllers without a REST layer for most pages.
* **Wayfinder** manages route discovery/navigation within Laravel.
* **shadcn/ui** supplies accessible, themeable components.
* **Mailer** sends outbound emails (SMTP).

## Project Structure

```
.
├── app/                    # Laravel app 
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── database.sqlite     # SQLite file
├── public/
├── resources/
│   ├── js/                 # React (Inertia) pages/components
│   ├── views/app.blade.php # Inertia entry
│   └── css/
├── routes/
│   └── web.php             # Inertia pages
├── storage/
├── tests/
├── vite.config.ts
└── composer.json / package.json
```

## Getting Started

### Prerequisites

* PHP 8.2+, Composer
* Node.js 20+ and **npm**
* SQLite (sudah tersedia di banyak sistem atau melalui ekstensi PHP `pdo_sqlite`)

### Installation

```bash
# clone
git clone https://git.ainosi.co.id/infrastructure/elastic-connector-alert
cd elastic-connector-alert

# install backend deps
composer install

# install frontend deps
npm install

# env
cp .env.example .env
php artisan key:generate
```

### Configuration

Update `.env` untuk SQLite dan SMTP.

```dotenv
APP_NAME="Aino SVC"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/project/database/database.sqlite

# use app password google
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.googlemail.com
MAIL_PORT=587
MAIL_USERNAME=example@example.com
MAIL_PASSWORD=examplepassword
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="example@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

> **Catatan:** Ganti `DB_DATABASE` sesuai path absolut file `database.sqlite` di sistem Anda. Biasanya cukup:
> `DB_DATABASE=${APP_PATH}/database/database.sqlite`
> atau
> `DB_DATABASE=/var/www/database/database.sqlite` (jika di Docker).

### Run Locally

```bash
# buat file database jika belum ada
touch database/database.sqlite

# jalankan migrasi
php artisan migrate

# start dev servers
php artisan serve
npm run dev
```

### Seeding & Route Generation

```bash
# seed initial data
php artisan db:seed --class=ProdSeeder # for production
php artisan db:seed --class=DevSeeder  # for development

# generate routes via Wayfinder
php artisan wayfinder:generate
```

### Available Scripts

```bash
# frontend
npm run dev     # Vite dev
npm run build   # production build

# backend
php artisan migrate
php artisan db:seed
php artisan test

# Running front end and back end
composer run dev
```

## Database

* **Engine:** SQLite (single file DB at `database/database.sqlite`)

## Deployment

* Pastikan `.env` menggunakan path absolut ke `database.sqlite` di server.
* Build frontend: `npm run build`.
* Configure web server (Nginx/Apache) to serve Laravel public path and Vite assets.
