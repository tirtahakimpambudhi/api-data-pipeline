# Aino SVC

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
* **Database:** MySQL (via Docker)
* **Mail:** Laravel Mailer (SMTP)

> Package manager: **npm**

## Architecture

```
[ React UI (shadcn/ui) ] --Inertia--> [ Laravel Controllers ] --> [ Services ] --> [ DB ]
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
│   └── seeders/
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
* Docker & Docker Compose (for MySQL)

### Installation

```bash
# clone
git clone https://github.com/tirtahakimpambudhi/aino-web-svc-conf.git
cd aino-svc

# install backend deps
composer install

# install frontend deps
npm install

# env
cp .env.example .env
php artisan key:generate
```

### Configuration

Update `.env` for MySQL, app URL, and SMTP.

```bash
APP_NAME="Aino SVC"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aino_svc
DB_USERNAME=root
DB_PASSWORD=secret

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

### Run Locally

```bash
# start database (if using Docker)
docker compose up -d db

# run migrations
php artisan migrate

# start dev servers
php artisan serve
npm run dev
```

### Seeding & Route Generation

```bash
# seed initial data
php artisan db:seed

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
```

## Database

* **Engine:** MySQL (via Docker Compose)

## Deployment

* Ensure `.env` uses production DB credentials and mail settings.
* Build frontend: `npm run build`.
* Configure web server (Nginx/Apache) to serve Laravel public path and Vite assets.