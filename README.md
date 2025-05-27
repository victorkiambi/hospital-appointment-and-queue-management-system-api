# Medbook Backend (Laravel)

## Overview
Medbook Backend is a RESTful API for hospital appointment and queue management, built with Laravel 12 (PHP 8.2+). It supports role-based access for Admins, Doctors, and Patients, and provides real-time queue updates for seamless clinic operations. The backend is designed for integration with a Vue.js frontend and is fully documented with OpenAPI.

## Key Features
- **Role-Based Access Control**: Admin, Doctor, Patient
- **User, Doctor, Patient Management**: CRUD operations and profile management
- **Appointment Scheduling**: Book, view, and manage appointments
- **Queue Management**: Real-time patient queueing and notifications
- **Token Authentication**: Secure API access via Laravel Sanctum
- **API Versioning**: All endpoints under `/api/v1/`
- **OpenAPI Documentation**: Comprehensive API docs in `openapi.yaml`
- **Testing**: Unit and feature tests for all major flows

## Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm (for frontend assets, if needed)
- MariaDB (or MySQL)
- [Optional] Docker & Docker Compose

## Setup & Installation

### 1. Clone the Repository
```bash
git clone <repo-url>
cd backend
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
- Copy `.env.example` to `.env` and update database and other settings as needed.
- If `.env.example` is missing, create a `.env` file based on Laravel's defaults and set your DB credentials:
  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=medbook_db
  DB_USERNAME=medbook_user
  DB_PASSWORD=medbook_pass
  ```
- Generate application key:
```bash
php artisan key:generate
```

### 4. Database Setup
- Create the database (if not using Docker):
  - MariaDB: `CREATE DATABASE medbook_db;`
- Run migrations and seeders:
```bash
php artisan migrate --seed
```

### 5. (Optional) Run with Docker
- Start MariaDB with Docker Compose:
```bash
docker-compose up -d
```
- Update your `.env` to match the Docker DB credentials (see above).

## Running the Application
- Start the Laravel development server:
```bash
php artisan serve
```
- The API will be available at `http://localhost:8000/api/v1/`

## API Documentation
- The OpenAPI/Swagger spec is in [`openapi.yaml`](openapi.yaml).
- Use Swagger UI, Postman, or Insomnia to explore and test endpoints.
- **Note:** System architecture, database schema, and detailed API docs are in separate documents (see `Technical_implementation_doc.md` and future docs).

## Testing
- Run all tests (unit and feature):
```bash
php artisan test
```
- PHPUnit is configured to use an in-memory SQLite database for tests.

## Project Structure
- `app/Models` — Eloquent models
- `app/Http/Controllers` — API controllers
- `app/Http/Requests` — Form request validation
- `app/Http/Resources` — API response formatting
- `app/Policies` — Authorization policies
- `database/migrations` — DB schema
- `tests/` — Unit and feature tests

## Contribution & Support
- Follow PSR-12 and Laravel best practices.
- Use feature branches and submit pull requests for review.
- For major changes, open an issue to discuss proposals.

## Roadmap & Improvements
- [ ] Extract business logic to a Service Layer (`app/Services`)
- [ ] Implement Repository pattern for complex data access
- [ ] Full Dockerization (PHP/Nginx containers)
- [ ] Expand real-time features and monitoring

## License
MIT 