# Technical Implementation Document: Backend (Laravel)

## 1. Overview
This document outlines the technical approach and decisions for building the  backend. The goal is to provide a robust, maintainable, and scalable API to support hospital appointment and queue management, consumed by a Vue.js frontend. We chose **Laravel 11+** (with **PHP 8.2+**) for its mature MVC architecture, Eloquent ORM, and strong support for authentication, authorization, and API development. All endpoints are versioned (e.g., `/api/v1/`) to ensure future compatibility.

---

## 2. Key Requirements
- **User Roles:** Admin, Doctor, Patient. We enforce role-based access control throughout the system.
- **Authentication:** Token-based using Laravel Sanctum, which fits our stateless API needs.
- **Core Modules:** User, Doctor, Patient, Appointment, and Queue management. These map directly to our main business processes.
- **Database:** MariaDB, using a normalized schema for data integrity and performance.
- **Real-Time:** Queue updates are pushed to clients using WebSockets (Laravel Echo/Pusher), so patients and doctors always see the latest status.
- **Documentation:** We maintain API docs using OpenAPI/Swagger and Laravel API resources for clarity and frontend alignment.
- **API Versioning:** All endpoints are under `/api/v1/` to make future upgrades easier.

---

## 3. Project Structure
```
backend/
├── app/
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Policies/
│   └── Services/           # Service layer for business logic (planned)
│   └── Repositories/       # Repository pattern for data access (optional, recommended for complex queries)
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── tests/
├── .env
└── ...
```

---

## 4. Database Design
### Entities & Relationships
We designed the schema to reflect real-world relationships and ensure data consistency:
- **User** (id, name, email, password, role)
- **Doctor** (id, user_id, specialization, availability)
- **Patient** (id, user_id, medical_record_number, etc.)
- **Appointment** (id, doctor_id, patient_id, scheduled_at, status)
- **Queue** (id, doctor_id, patient_id, position, status, called_at)

**Relationships:**
- User 1:1 Doctor
- User 1:1 Patient
- Doctor 1:M Appointment
- Patient 1:M Appointment
- Doctor 1:M Queue
- Patient 1:M Queue

---

## 5. Authentication & Authorization
- **Authentication:** We use Laravel Sanctum for API token authentication. Tokens are issued on login and must be sent with each request (via HttpOnly cookie or Authorization header).
- **Authorization:** Laravel Policies and Gates enforce role-based access. This keeps our authorization logic centralized and testable.
- **Middleware:** Custom middleware ensures only the right roles can access certain routes.
- **CORS:** Configured in `config/cors.php` to allow requests from the frontend domain.

---

## 6. API Endpoints (Sample, Versioned)
| Method | Endpoint                        | Description                        | Access   |
|--------|----------------------------------|------------------------------------|----------|
| POST   | /api/v1/register                | Register user                      | Public   |
| POST   | /api/v1/login                   | Login user, get token              | Public   |
| GET    | /api/v1/user                    | Get current user profile           | Auth     |
| GET    | /api/v1/doctors                 | List doctors                       | Auth     |
| POST   | /api/v1/doctors/availability    | Set doctor availability            | Doctor   |
| GET    | /api/v1/appointments            | List appointments                  | Auth     |
| POST   | /api/v1/appointments            | Book appointment                   | Patient  |
| GET    | /api/v1/queue                   | Get queue status                   | Auth     |
| POST   | /api/v1/queue/call-next         | Doctor calls next patient          | Doctor   |

---

## 7. Core Modules & Logic
### User Management
- Registration, login, and profile management are handled via dedicated endpoints.
- Role assignment is explicit and enforced at creation.

### Doctor Management
- Admins can create, update, and delete doctors.
- Doctors can set their own availability.

### Patient Management
- Admins manage patient records.
- Patients can view their appointments and queue status.

### Appointment Management
- Patients book appointments with available doctors.
- Doctors manage their own appointments.
- The system prevents double-booking at the database and application level.

### Queue Management
- Patients join the queue after check-in.
- Real-time updates keep everyone informed of their position.
- Doctors can "call next" to advance the queue and notify the next patient.

---

## 8. Service & Repository Pattern
- **Service Layer:** We plan to encapsulate business logic in service classes (e.g., `AppointmentService`, `QueueService`). This will help us keep controllers thin and improve testability.
- **Repository Layer (optional):** For complex queries or to decouple Eloquent from business logic, we recommend using repositories.
- **Benefits:** These patterns improve maintainability and make the codebase easier to test and extend.

---

## 9. API Response Standardization
- All responses use **Laravel API Resources** for a consistent structure.
- **Example Response:**
```json
{
  "data": { ... },
  "message": "Success",
  "errors": null
}
```
- **Error Responses:**
```json
{
  "message": "Validation failed.",
  "errors": {
    "field": ["Error message"]
  }
}
```
- We document all response formats to ensure smooth frontend integration.

---

## 10. Real-Time Features
- **Queue Updates:** We use Laravel Echo with Pusher or WebSockets to broadcast queue changes in real time.
- **Notifications:** (Optional) Patients can be notified when they are next in line.
- **Event Naming:** We standardize event and channel names for easy frontend integration.

---

## 11. Validation & Error Handling
- Input validation is handled by Laravel Form Requests.
- API errors are managed by Laravel's exception handler, with custom exceptions as needed.
- We ensure all error responses are consistent and API-friendly.
- Edge cases like double-booking, unauthorized access, and queue race conditions are explicitly handled.

---

## 12. Security
- Passwords are hashed using bcrypt.
- API rate limiting is enforced via throttle middleware.
- All input is validated and sanitized.
- Role-based access control is strictly enforced.
- CORS is configured for secure frontend-backend communication.

---

## 13. Testing
- **Unit Tests:** Cover models, services, and policies.
- **Feature Tests:** Cover API endpoints, authentication, and role restrictions.
- **Factories/Seeders:** Used for generating test data.
- **API Test Coverage:** We test authentication, role access, and edge cases.

---

## 14. Documentation
- **API Docs:** Maintained in OpenAPI/Swagger and Laravel API resources. We keep these up to date for the frontend team.
- **README:** Includes setup instructions, environment variables, migration/seed commands, and known issues.
- **Frontend Compatibility:** We ensure all endpoints, error formats, and authentication flows are documented for the frontend.

---

## 15. Deployment
- Environment configuration is managed via `.env` files.
- Database migrations and seeders are used for setup.
- (Optional) We may deploy to AWS EC2 or similar cloud infrastructure.

---

## 16. Example Development Workflow
1. Initialize the Laravel project in `backend/` (Laravel 11+, PHP 8.2+)
2. Set up the MariaDB connection in `.env`
3. Create migrations and models for all entities
4. Implement authentication (Sanctum)
5. Build controllers, services, and policies
6. Use API Resources for all responses
7. Write and run tests
8. Document the API and setup (OpenAPI/Swagger)
9. Prepare for deployment

---

## 17. Risks & Considerations
- Real-time features require additional setup (Pusher/WebSockets)
- Role management must be strictly enforced to avoid privilege escalation
- Data consistency, especially for queue and appointments, is critical
- API versioning and documentation must be maintained for frontend compatibility

---

