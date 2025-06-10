# API Documentation

## Table of Contents
- [Register](#register)
- [Login](#login)
- [User Profile](#user-profile)
- [Doctors](#doctors)
- [Patients](#patients)
- [Appointments](#appointments)
- [Queues](#queues)
- [Changelog](#changelog)

---

## Register

**POST /register**

- Registers a new user (admin, doctor, or patient)
- **Authentication:** Not required
- **Request Body:**
  - `name` (string, required)
  - `email` (string, required)
  - `password` (string, required)
  - `password_confirmation` (string, required)
  - `role` (string, required: `admin`, `doctor`, `patient`)
- **Example:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "secret",
  "password_confirmation": "secret",
  "role": "patient"
}
```
- **Response:** 201 Created on success

---

## Login

**POST /login**

- Authenticates a user and returns a token
- **Authentication:** Not required
- **Request Body:**
  - `email` (string, required)
  - `password` (string, required)
- **Example:**
```json
{
  "email": "jane@example.com",
  "password": "secret"
}
```
- **Response:** 200 OK with token

---

## User Profile

**GET /user**

- Returns the current authenticated user's profile
- **Authentication:** Bearer token required
- **Response:** 200 OK with user data

---

## Doctors

### Doctor Creation: Two-Step Process

#### Step 1: Create a User with Role 'doctor'
- **Endpoint:** `POST /register` (or admin user creation endpoint)
- **Required fields:** `name`, `email`, `password`, `role` (must be `'doctor'`)
- **Example:**
```json
{
  "name": "Dr. Jane Smith",
  "email": "jane.smith@hospital.com",
  "password": "securepassword",
  "password_confirmation": "securepassword",
  "role": "doctor"
}
```
- **Response:** 201 Created, returns user data with `id`.

#### Step 2: Create Doctor Profile
- **Endpoint:** `POST /doctors`
- **Required fields:** `user_id` (from previous step), `specialization`
- **Optional:** `availability` (array)
- **Example:**
```json
{
  "user_id": 42,
  "specialization": "Cardiology",
  "availability": [
    { "day": "Monday", "start": "09:00", "end": "12:00" }
  ]
}
```
- **Response:** 201 Created, returns doctor profile data.

**Validation Notes:**
- `user_id` must reference a user with role `'doctor'` (enforced in business logic or should be added if not present).
- `user_id` must be unique in the doctors table (a user can only have one doctor profile).
- `specialization` is required.

---

### Combined Doctor Creation (Admin Only)
If using the admin endpoint (e.g., `AdminUserController@storeDoctor`), you can create both the user and doctor profile in a single request:

- **Endpoint:** `POST /admin/doctors` (or similar)
- **Required fields:** `name`, `email`, `password`, `specialization`
- **Optional:** `availability`
- **Example:**
```json
{
  "name": "Dr. Jane Smith",
  "email": "jane.smith@hospital.com",
  "password": "securepassword",
  "specialization": "Cardiology",
  "availability": [
    { "day": "Monday", "start": "09:00", "end": "12:00" }
  ]
}
```
- **Response:** 201 Created, returns both user and doctor profile data.

**Validation Notes:**
- Email must be unique in users.
- User is created with role `'doctor'`.
- Doctor profile is linked to the new user.
- All operations are performed in a transaction for atomicity.

---

### Other Doctor Endpoints

#### List Doctors
**GET /doctors**
- Returns a list of doctors
- **Authentication:** Bearer token required
- **Response:** 200 OK, paginated

#### Get Doctor by ID
**GET /doctors/{id}**
- Returns a single doctor by ID
- **Authentication:** Bearer token required
- **Path Parameter:** `id` (integer, required)
- **Response:** 200 OK

#### Update Doctor
**PUT /doctors/{id}**
- Updates a doctor (admin or doctor themselves)
- **Authentication:** Bearer token required
- **Path Parameter:** `id` (integer, required)
- **Request Body:**
  - `specialization` (string, optional)
  - `availability` (array, optional)
- **Response:** 200 OK

#### Delete Doctor
**DELETE /doctors/{id}**
- Deletes a doctor (admin only)
- **Authentication:** Bearer token required
- **Path Parameter:** `id` (integer, required)
- **Response:** 204 No Content

---

For full details on request/response schemas, see the `openapi.yaml` file in the project root.

---

## Patients

### List Patients
**GET /patients**
- Returns a list of patients
- **Authentication:** Bearer token required
- **Response:** 200 OK

### Create Patient
**POST /patients**
- Creates a new patient
- **Authentication:** Bearer token required
- **Request Body:**
  - See OpenAPI spec for full schema
- **Response:** 201 Created

### Get Patient by ID
**GET /patients/{id}**
- Returns a single patient by ID
- **Authentication:** Bearer token required
- **Path Parameter:** `id` (integer, required)
- **Response:** 200 OK

### Update Patient
**PUT /patients/{id}**
- Updates a patient
- **Authentication:** Bearer token required
- **Path Parameter:** `id` (integer, required)
- **Request Body:**
  - See OpenAPI spec for full schema
- **Response:** 200 OK

### Delete Patient
**DELETE /patients/{id}**
- Deletes a patient
- **Authentication:** Bearer token required
- **Path Parameter:** `id` (integer, required)
- **Response:** 204 No Content

---

## Appointments

# Appointments API Documentation

## Endpoint

`GET /appointments`

## Authentication
- Requires Bearer token authentication

## Query Parameters
| Name                | Type    | Format      | Example                        | Description                                                      |
|---------------------|---------|-------------|--------------------------------|------------------------------------------------------------------|
| doctor_id           | integer |             | 1                              | Filter by doctor ID                                              |
| patient_id          | integer |             | 2                              | Filter by patient ID                                             |
| status              | string  |             | scheduled                      | Filter by appointment status (`scheduled`, `completed`, `cancelled`) |
| date                | string  | date        | 2024-03-20                     | Filter by specific date (YYYY-MM-DD). Takes precedence over date range. |
| scheduled_at_start  | string  | date-time   | 2024-03-20T00:00:00Z           | Filter by start date and time (YYYY-MM-DDThh:mm:ssZ)             |
| scheduled_at_end    | string  | date-time   | 2024-03-21T23:59:59Z           | Filter by end date and time (YYYY-MM-DDThh:mm:ssZ)               |
| search              | string  |             | John                           | Search by appointment ID, patient name, or doctor name           |
| per_page            | integer |             | 20                             | Number of items per page (default: 20)                           |

### Filtering Logic
- If `date` is provided, it filters appointments for that specific date (ignores date range).
- If `date` is not provided, `scheduled_at_start` and/or `scheduled_at_end` can be used to filter by a date/time range.
- All other filters (doctor_id, patient_id, status, search) can be combined.

## Example Requests

**Filter by date:**
```
GET /appointments?date=2024-03-20
```

**Filter by date range:**
```
GET /appointments?scheduled_at_start=2024-03-20T00:00:00Z&scheduled_at_end=2024-03-21T23:59:59Z
```

**Filter by doctor and status:**
```
GET /appointments?doctor_id=1&status=scheduled
```

**Paginated request:**
```
GET /appointments?per_page=10
```

## Example Response
```
{
  "data": [
    {
      "id": 1,
      "doctor": { /* ... */ },
      "patient": { /* ... */ },
      "scheduled_at": "2024-03-20T10:00:00Z",
      "status": "scheduled",
      "created_at": "2024-03-01T12:00:00Z",
      "updated_at": "2024-03-01T12:00:00Z"
    },
    // ...
  ],
  "meta": {
    "total": 100,
    "per_page": 20,
    "current_page": 1,
    "last_page": 5
  },
  "message": "Appointments fetched successfully",
  "errors": null
}
```

## Pagination
- Use the `per_page` parameter to control the number of results per page.
- The `meta` object in the response provides pagination details.

## Further Reference
- See `openapi.yaml` in the project root for the full OpenAPI specification.

## Changelog
- **2024-06-07**: Added support for date range filtering (`scheduled_at_start`, `scheduled_at_end`) to the appointments API.

---

## Queues

### List Queue Entries
**GET /queues**
- Returns a list of queue entries (filterable by doctor_id)
- **Authentication:** Bearer token required
- **Query Parameter:** `doctor_id` (integer, optional)
- **Response:** 200 OK

### Add to Queue
**POST /queues**
- Adds a patient to a doctor's queue
- **Authentication:** Bearer token required
- **Request Body:**
  - `doctor_id` (integer, required)
  - `patient_id` (integer, required)
- **Response:** 201 Created

### Get Queue Entry by ID
**GET /queues/{id}**
- Returns a single queue entry by ID
- **Authentication:** Bearer token required
- **Path Parameter:** `id` (integer, required)
- **Response:** 200 OK

### Update Queue Entry
**PUT /queues/{id}**
- Updates a queue entry
- **Authentication:** Bearer token required
- **Path Parameter:** `id` (integer, required)
- **Request Body:**
  - See OpenAPI spec for full schema
- **Response:** 200 OK

### Delete Queue Entry
**DELETE /queues/{id}**
- Deletes a queue entry
- **Authentication:** Bearer token required
- **Path Parameter:** `id` (integer, required)
- **Response:** 204 No Content

---

## Changelog
- **2024-06-07**: Added support for date range filtering (`scheduled_at_start`, `scheduled_at_end`) to the appointments API.
- **2024-06-07**: Initial documentation for all endpoints.

---

For full details on request/response schemas, see the `openapi.yaml` file in the project root. 