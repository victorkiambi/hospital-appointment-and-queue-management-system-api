# Database Design: Backend

## Overview
The backend uses a normalized MariaDB schema to support robust, consistent management of users, doctors, patients, appointments, and queues. The design reflects real-world relationships and enforces data integrity, supporting the business logic and API requirements described in the technical implementation doc.

---

## Entity-Relationship Diagram (Textual)

```
User (1) ──── (1) Doctor
   │             │
   │             └─────< Appointment >─────┐
   │                                      │
   └───── (1) Patient                     │
                 │                        │
                 └─────< Queue >──────────┘
```
- **User** can be either a Doctor or a Patient (1:1 relationships)
- **Doctor** and **Patient** can each have many Appointments and Queue entries
- **Appointment** links a Doctor and a Patient
- **Queue** links a Doctor and a Patient, with position and status

---

## Table Breakdown

### users
| Field     | Type         | Constraints                |
|-----------|--------------|----------------------------|
| id        | BIGINT, PK   | auto-increment             |
| name      | VARCHAR(255) | not null                   |
| email     | VARCHAR(255) | unique, not null           |
| password  | VARCHAR(255) | not null                   |
| role      | ENUM         | ('admin','doctor','patient')|
| ...       | ...          | ...                        |

### doctors
| Field         | Type         | Constraints                |
|---------------|--------------|----------------------------|
| id            | BIGINT, PK   | auto-increment             |
| user_id       | BIGINT, FK   | unique, not null, users.id |
| specialization| VARCHAR(255) | not null                   |
| availability  | JSON/ARRAY   | nullable                   |
| ...           | ...          | ...                        |

### patients
| Field                 | Type         | Constraints                |
|-----------------------|--------------|----------------------------|
| id                    | BIGINT, PK   | auto-increment             |
| user_id               | BIGINT, FK   | unique, not null, users.id |
| medical_record_number | VARCHAR(255) | unique, not null           |
| ...                   | ...          | ...                        |

### appointments
| Field         | Type         | Constraints                |
|---------------|--------------|----------------------------|
| id            | BIGINT, PK   | auto-increment             |
| doctor_id     | BIGINT, FK   | not null, doctors.id       |
| patient_id    | BIGINT, FK   | not null, patients.id      |
| scheduled_at  | DATETIME     | not null                   |
| status        | ENUM         | ('scheduled','completed','cancelled') |
| ...           | ...          | ...                        |

### queues
| Field      | Type         | Constraints                |
|------------|--------------|----------------------------|
| id         | BIGINT, PK   | auto-increment             |
| doctor_id  | BIGINT, FK   | not null, doctors.id       |
| patient_id | BIGINT, FK   | not null, patients.id      |
| position   | INT          | not null                   |
| status     | ENUM         | ('waiting','called','done')|
| called_at  | DATETIME     | nullable                   |
| ...        | ...          | ...                        |

---

## Key Relationships & Integrity Rules
- **users.user_id** is referenced uniquely by both doctors and patients (enforces 1:1 mapping)
- **appointments** and **queues** reference both doctor and patient by FK
- **Cascade deletes** are used where appropriate (e.g., deleting a user removes their doctor/patient profile)
- **Unique constraints** on user emails, medical record numbers, and user_id in doctors/patients
- **No double-booking**: Application logic and DB constraints prevent overlapping appointments for the same doctor

---

## Migration & Seeding Notes
- All tables are created via Laravel migrations in `database/migrations/`
- Seeders and factories in `database/seeders/` and `database/factories/` provide test/demo data
- Use `php artisan migrate --seed` to set up and populate the database

---

## Extensibility Considerations
- The schema is versioned and normalized for easy extension (e.g., adding new roles, appointment types, or queue statuses)
- JSON/array fields (e.g., doctor availability) allow flexible scheduling
- All relationships are explicit and indexed for performance

---

## References
- See `Technical_implementation_doc.md` for rationale and requirements
- See `SYSTEM_ARCHITECTURE.md` for system-level context
- See migrations for exact field definitions and constraints 