# Technical Implementation Document: Backend (Laravel)

## 1. Overview
The backend will be built using **Laravel 11+** (with **PHP 8.2+**), leveraging its MVC architecture, Eloquent ORM, and built-in features for authentication, authorization, and API development. The backend will expose a RESTful, versioned API (e.g., `/api/v1/`) consumed by the Vue.js frontend.

---

## 2. Key Requirements
- **User Roles:** Admin, Doctor, Patient (Role-Based Access Control)
- **Authentication:** Token-based (Laravel Sanctum)
- **Core Modules:** User Management, Doctor Management, Patient Management, Appointment Scheduling, Queue Management
- **Database:** MariaDB, normalized schema
- **Real-Time:** Queue updates (WebSockets or Laravel Echo/Pusher)
- **Documentation:** API docs (OpenAPI/Swagger or Laravel API resources)
- **API Versioning:** All endpoints under `/api/v1/` for future-proofing

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
│   └── Services/           # Service layer for business logic
│   └── Repositories/       # Repository pattern for data access (optional but recommended)
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
- **Authentication:** Laravel Sanctum for API token authentication. Tokens are issued on login and must be sent with each request (via HttpOnly cookie or Authorization header).
- **Authorization:** Laravel Policies and Gates for role-based access.
- **Middleware:** Custom middleware to enforce role restrictions on routes.
- **CORS:** Configure CORS in `config/cors.php` to allow requests from the frontend domain.

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
- Registration, login, profile management
- Role assignment (Admin, Doctor, Patient)

### Doctor Management
- CRUD for doctors (Admin)
- Set availability (Doctor)

### Patient Management
- CRUD for patients (Admin)
- View appointments, queue status

### Appointment Management
- Patients book appointments with available doctors
- Doctors view/manage their appointments
- Prevent double-booking

### Queue Management
- Patients join queue after check-in
- Real-time queue position updates
- Doctor can "call next" (updates queue and notifies patient)

---

## 8. Service & Repository Pattern
- **Service Layer:** Encapsulate business logic in service classes (e.g., `AppointmentService`, `QueueService`).
- **Repository Layer (optional):** Abstract data access for complex queries or to decouple Eloquent from business logic.
- **Benefits:** Improves testability, maintainability, and separation of concerns.

---

## 9. API Response Standardization
- Use **Laravel API Resources** for all responses to ensure a consistent structure.
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
- Document all response formats for frontend compatibility.

---

## 10. Real-Time Features
- **Queue Updates:** Use Laravel Echo with Pusher or WebSockets to broadcast queue changes.
- **Notifications:** Optional—notify patients when they are next in line.
- **Event Naming:** Standardize event/channel names for frontend integration.

---

## 11. Validation & Error Handling
- Use Laravel Form Requests for input validation.
- Use Laravel's exception handler for API errors; create custom exceptions as needed.
- Consistent API error responses (see above).
- Handle edge cases: double-booking, unauthorized access, queue race conditions.

---

## 12. Security
- Password hashing (bcrypt)
- API rate limiting (throttle middleware)
- Input validation/sanitization
- Role-based access control
- CORS configuration for frontend-backend communication

---

## 13. Testing
- **Unit Tests:** Models, Services, Policies
- **Feature Tests:** API endpoints, authentication, role restrictions
- **Factories/Seeders:** For test data
- **API Test Coverage:** Authentication, role access, edge cases

---

## 14. Documentation
- **API Docs:** Use OpenAPI/Swagger and Laravel API resources. Keep docs up to date for frontend reference.
- **README:** Setup instructions, environment variables, migration/seed commands, known issues.
- **Frontend Compatibility:** Ensure all endpoints, error formats, and authentication flows are documented for frontend integration.

---

## 15. Deployment
- Environment configuration via `.env`
- Database migrations and seeders
- (Optional) Deploy to AWS EC2

---

## 16. Example Development Workflow
1. Initialize Laravel project in `backend/` (Laravel 11+, PHP 8.2+)
2. Set up MariaDB connection in `.env`
3. Create migrations/models for all entities
4. Implement authentication (Sanctum)
5. Build controllers, services, and policies
6. Use API Resources for all responses
7. Write and run tests
8. Document API and setup (OpenAPI/Swagger)
9. Prepare for deployment

---

## 17. Risks & Considerations
- Real-time features require additional setup (Pusher/WebSockets)
- Role management must be strictly enforced
- Data consistency (especially for queue/appointments) is critical
- API versioning and documentation must be maintained for frontend compatibility

---

**This document provides a clear, maintainable, and scalable approach for your backend, fully compatible with the frontend and up to date with 2024 best practices.** 