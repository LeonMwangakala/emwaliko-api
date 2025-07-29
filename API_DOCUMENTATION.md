# Event Invitation Card Management System API

## Overview
This API provides endpoints for managing event invitation cards, guests, and RSVP functionality with QR code generation.

## Base URL
```
http://localhost:8000/api
```

## Authentication
All endpoints require authentication using Laravel Sanctum. Include the Bearer token in the Authorization header:
```
Authorization: Bearer {your-token}
```

## Endpoints

### Customers

#### Get all customers
```
GET /customers
```

#### Create a customer
```
POST /customers
Content-Type: application/json

{
    "name": "John Doe",
    "phone_number": "+254700000000",
    "title": "Mr.",
    "physical_location": "Nairobi, Kenya"
}
```

#### Get a specific customer
```
GET /customers/{id}
```

#### Update a customer
```
PUT /customers/{id}
Content-Type: application/json

{
    "name": "John Doe Updated",
    "phone_number": "+254700000001"
}
```

#### Delete a customer
```
DELETE /customers/{id}
```

### Events

#### Get all events
```
GET /events
```

#### Create an event
```
POST /events
Content-Type: application/json

{
    "event_name": "Wedding Ceremony",
    "customer_id": 1,
    "event_type_id": 1,
    "card_type_id": 1,
    "card_class_id": 1,
    "package_id": 1,
    "event_location": "Nairobi Convention Centre",
    "event_date": "2024-12-25 18:00:00",
    "notification_date": "2024-11-25 10:00:00",
    "card_design_path": "card_designs/wedding_design_001.jpg"
}
```

#### Get event options (for forms)
```
GET /events/{event}/options
```

#### Get a specific event
```
GET /events/{id}
```

#### Update an event
```
PUT /events/{id}
Content-Type: application/json

{
    "event_name": "Updated Wedding Ceremony"
}
```

#### Delete an event
```
DELETE /events/{id}
```

### Guests

#### Get all guests
```
GET /guests
```

#### Create a guest
```
POST /guests
Content-Type: application/json

{
    "event_id": 1,
    "name": "Jane Smith",
    "title": "Ms.",
    "card_class_id": 1,
    "rsvp_status": "Pending"
}
```

#### Get a specific guest
```
GET /guests/{id}
```

#### Update a guest
```
PUT /guests/{id}
Content-Type: application/json

{
    "name": "Jane Smith Updated",
    "rsvp_status": "Yes"
}
```

#### Delete a guest
```
DELETE /guests/{id}
```

#### Get guests for a specific event
```
GET /events/{event}/guests
```

#### Bulk create guests for an event
```
POST /events/{event}/guests/bulk
Content-Type: application/json

{
    "guests": [
        {
            "name": "Guest 1",
            "title": "Mr.",
            "card_class_id": 1
        },
        {
            "name": "Guest 2",
            "title": "Ms.",
            "card_class_id": 1
        }
    ]
}
```

### RSVP Functionality

#### Get guest by invite code
```
GET /guests/invite/{inviteCode}
```

#### Update RSVP status
```
POST /guests/{inviteCode}/rsvp
Content-Type: application/json

{
    "rsvp_status": "Yes"
}
```

Valid RSVP statuses: `Yes`, `No`, `Maybe`

## QR Code Generation

Each guest automatically gets:
- A unique `invite_code` (UUID)
- A QR code generated and stored in `qr_code_path`
- The QR code contains a link to the RSVP endpoint

## Response Format

All endpoints return JSON responses with the following structure:

### Success Response
```json
{
    "data": {...},
    "message": "Success message"
}
```

### Error Response
```json
{
    "message": "Error message",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

## Database Schema

### Users
- `id`, `name`, `email`, `phone_number`, `password`, `role_id`, `created_at`, `updated_at`

### Roles
- `id`, `name`, `created_at`, `updated_at`

### Customers
- `id`, `name`, `phone_number`, `title`, `physical_location`, `created_at`, `updated_at`

### Event Types
- `id`, `name`, `created_at`, `updated_at`

### Card Types
- `id`, `name`, `created_at`, `updated_at`

### Card Classes
- `id`, `name`, `max_guests`, `created_at`, `updated_at`

### Packages
- `id`, `name`, `amount`, `created_at`, `updated_at`

### Events
- `id`, `event_name`, `customer_id`, `event_type_id`, `card_type_id`, `card_class_id`, `package_id`, `event_location`, `event_date`, `notification_date`, `card_design_path`, `created_at`, `updated_at`

### Guests
- `id`, `event_id`, `name`, `title`, `card_class_id`, `invite_code`, `qr_code_path`, `rsvp_status`, `created_at`, `updated_at`

## Setup Instructions

1. Install dependencies:
```bash
composer install
```

2. Copy environment file:
```bash
cp .env.example .env
```

3. Generate application key:
```bash
php artisan key:generate
```

4. Configure database in `.env`

5. Run migrations:
```bash
php artisan migrate
```

6. Seed the database:
```bash
php artisan db:seed
```

7. Create storage link for QR codes:
```bash
php artisan storage:link
```

8. Start the server:
```bash
php artisan serve
```

## Default Admin User
- Email: admin@kadirafiki.com
- Password: password
- Role: Admin 