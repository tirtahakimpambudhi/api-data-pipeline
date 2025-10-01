

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
  * [Run Application](#run-application)
  * [Seeding & Route Generation](#seeding--route-generation)
  * [Available Scripts](#available-scripts)
* [Database](#database)
* [Deployment](#deployment)
* [Detail Features](#detail-features)
* [Flow Login App](#flow-login)

---

## Overview

Elastic Connector Alert (Service Configuration) is a lightweight internal tool to **store and manage configuration** for applications and services. It provides a simple UI and API to read/update config values with auditability.

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
* **Package manager** : npm and composer

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

# Required when running without docker, not required if running with docker
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
APP_NAME="Elastic Connector Alert"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/project/database/database.sqlite

## default admin user  
ADMIN_EMAIL=example@gmail.com
ADMIN_USERNAME=exampleusername
ADMIN_PASSWORD=examplepassword

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

### Run Application

#### Running Local without docker-compose
```bash
# create sqlite if not exists 
touch database/database.sqlite

# running migration database
php artisan migrate

# start dev servers
php artisan serve
npm run dev
# or
composer run dev
```

#### Running Local with docker-compose
```bash
## Starts the containers defined in docker-compose.yml in detached mode (running in the background).
docker compose up -d

## Builds the images (if needed) before starting the containers, and runs them in detached mode.
docker compose up --build -d

## Starts the containers in the foreground and attaches logs to the terminal.
docker compose up

## Builds the images (if needed) before starting the containers, and runs them in the foreground with logs attached.
docker compose up --build
```

#### Stop App if running with docker compose
```bash
## Stops and removes all containers, networks, and named/anonymous volumes created by Docker Compose.
docker compose down -v

## Stops and removes containers and networks created by Docker Compose, but keeps volumes and images.
docker compose down

## Stops and removes containers, networks, and also removes images that were built locally (not pulled from Docker Hub).
docker compose down --rmi local

## Stops and removes containers, networks, and removes all images used by the services (both local builds and pulled images).
docker compose down --rmi all
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

* Ensure that `.env` uses the absolute path to `database.sqlite` on the server.
* Build frontend: `npm run build`.
* Configure web server (Nginx/Apache) to serve Laravel public path and Vite assets.


## Detail Features

## 1. Core Data Management

* **Namespace**

    * Add a new Namespace.
    * Display the list of Namespaces.
    * Update Namespace information.
    * Delete a Namespace.

* **Service**

    * Add a new Service associated with a Namespace.
    * Display the list of Services within a specific Namespace.
    * Update Service information.
    * Delete a Service.

* **Service Environment**

    * Add an Environment to a Service (e.g., development, staging, production).
    * Display the list of Service Environments.
    * Update Service Environment information.
    * Delete a Service Environment.

* **Channel**

    * Add a Communication Channel (e.g., Email, Slack, Webhook).
    * Display the list of Channels.
    * Update Channel information.
    * Delete a Channel.

* **Configuration**

    * Add a Configuration associated with a Service Environment and a Channel.
    * Display the list of Configurations.
    * Update Configuration details.
    * Delete a Configuration.

---

## 2. Access Control

* **Admin Role**

    * Full access to manage all data (Namespace, Service, Service Environment, Channel, Configuration).

* **Regular User Role**

    * Full access to Configuration resources.
    * Read-only access to Service Environments and Channels.

---

## 3. Authentication and Registration

* User registration with a regular user role.
* Login as either a regular user or an admin.
* Admin user registration requires SMTP configuration in the Laravel environment. The admin registration process requires approval via the developer’s email or the configured Laravel environment email.
* A seeder is provided to supply initial user data.

---

## 4. Email Configuration (Laravel SMTP)

To ensure the system functions properly for sending verification emails, password resets, and admin registration notifications.

Standard configuration in Laravel `.env` file:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.provider.com
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Application Name"
```

---


## Flow Login
### Before You Begin (prerequisites)

* If you plan to use **admin self-registration with approval**, ensure your SMTP/email settings are correctly configured in `.env` (`MAIL_*` keys).
---

### A) Default Admin via Seeder (with ENV values)

**When to use:** You want a known, generic admin for local/dev or initial bootstrap.

1. **Set environment variables** in your `.env`

   ```
   ADMIN_USERNAME=System Admin
   ADMIN_EMAIL=admin@yourdomain.com
   ADMIN_PASSWORD=your-strong-password-here
   ```
2. **Run database migrations (if needed)**

   ```
   php artisan migrate --force
   ```
3. **Run the seeder** that creates the default admin

    * Commonly:

      ```bash
      php artisan db:seed 
      # or
      php artisan db:seed --class=ProdSeeder # for production
      # or
      php artisan db:seed --class=DevSeeder  # for development
      ```
4. **Log in** at:
    * `POST /login` (UI path: `/login`)
    * Use `ADMIN_EMAIL` and `ADMIN_PASSWORD`.
---

### B) Admin Self-Registration with Approval (no ENV values)

**When to use:** You do **not** set `ADMIN_*` ENV values, or you want auditable admin creation with human approval.

1. **Confirm email/SMTP config** in `.env` (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_*`).
   *(Without working email, approval will not be delivered.)*
2. **Register as an admin**

    * Navigate to `/admin/register` and submit the form.
    * The new admin account will be **Pending Approval**.
3. **Approval step**

    * An approval request is sent to the **configured approver email** (e.g., developer email or an address set in your environment).
    * Approver reviews and **approves** the request via the link or approval process provided.
4. **Log in after approval**

    * Use `/login` with the email/password you registered at `/admin/register`.

---

### C) Regular User Registration (non-admin)

* Path: `/register`
* Result: **non-admin role** by default.
* Login at `/login`.
* This path never grants admin privileges.
---
