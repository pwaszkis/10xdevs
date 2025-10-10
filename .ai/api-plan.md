# REST API Plan - VibeTravels MVP

## 1. Resources

| Resource | Database Table | Description |
|----------|---------------|-------------|
| User | `users` | User accounts with authentication |
| User Preferences | `user_preferences` | Travel preferences and interests |
| Travel Plan | `travel_plans` | Travel itineraries created by users |
| Plan Day | `plan_days` | Individual days within a travel plan |
| Plan Point | `plan_points` | Sightseeing points/activities per day |
| AI Generation | `ai_generations` | AI generation history and metadata |
| Feedback | `feedback` | User feedback on generated plans |
| PDF Export | `pdf_exports` | PDF export history |
| Email Verification | `email_verifications` | Email verification tokens |

---

## 2. API Endpoints

### 2.1 Authentication & Authorization

#### POST /api/auth/register
Register a new user with email and password.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123",
  "password_confirmation": "SecurePassword123"
}
```

**Validation:**
- `email`: required, valid email format, unique, max 255 chars
- `password`: required, min 8 characters
- `password_confirmation`: required, must match password

**Success Response (201 Created):**
```json
{
  "message": "Registration successful. Please verify your email.",
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "email_verified_at": null,
      "provider": "email",
      "onboarding_completed_at": null,
      "onboarding_step": 0,
      "created_at": "2025-10-08T10:00:00.000000Z"
    }
  }
}
```

**Error Responses:**
- `422 Unprocessable Entity`: Validation errors
  ```json
  {
    "message": "The email has already been taken.",
    "errors": {
      "email": ["The email has already been taken."]
    }
  }
  ```
- `429 Too Many Requests`: Rate limit exceeded (3 attempts/hour)

---

#### GET /api/auth/google
Redirect to Google OAuth consent screen.

**Query Parameters:** None

**Success Response (302 Redirect):**
Redirects to Google OAuth consent screen.

---

#### GET /api/auth/google/callback
Handle Google OAuth callback.

**Query Parameters:**
- `code`: OAuth authorization code (provided by Google)
- `state`: CSRF token (provided by Google)

**Success Response (200 OK):**
```json
{
  "message": "Authentication successful.",
  "data": {
    "user": {
      "id": 2,
      "email": "user@gmail.com",
      "email_verified_at": "2025-10-08T10:00:00.000000Z",
      "provider": "google",
      "provider_id": "google_user_id_123",
      "nickname": "John",
      "onboarding_completed_at": null,
      "onboarding_step": 0,
      "created_at": "2025-10-08T10:00:00.000000Z"
    },
    "token": "session_token_here"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: OAuth authentication failed
- `422 Unprocessable Entity`: Account merging conflict

**Business Logic:**
- If OAuth email matches existing email account, merge accounts (add Google provider to existing user)
- Email is automatically verified for Google OAuth users

---

#### POST /api/auth/login
Login with email and password.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123"
}
```

**Validation:**
- `email`: required, valid email format
- `password`: required

**Success Response (200 OK):**
```json
{
  "message": "Login successful.",
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "email_verified_at": "2025-10-08T10:05:00.000000Z",
      "nickname": "Jane",
      "home_location": "Warsaw, Poland",
      "onboarding_completed_at": "2025-10-08T10:15:00.000000Z",
      "onboarding_step": 4,
      "ai_generations_count_current_month": 3,
      "ai_generations_reset_at": "2025-11-01T00:00:00.000000Z",
      "created_at": "2025-10-08T10:00:00.000000Z"
    },
    "token": "session_token_here"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Invalid credentials
- `429 Too Many Requests`: Rate limit exceeded (5 attempts/5 minutes)

---

#### POST /api/auth/logout
Logout the authenticated user.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Request Body:** None

**Success Response (200 OK):**
```json
{
  "message": "Logout successful."
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated

---

#### GET /api/auth/verify-email/{token}
Verify email address using token from verification email.

**Path Parameters:**
- `token`: Email verification token (64 characters)

**Query Parameters:** None

**Success Response (200 OK):**
```json
{
  "message": "Email verified successfully.",
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com",
      "email_verified_at": "2025-10-08T10:05:00.000000Z"
    }
  }
}
```

**Error Responses:**
- `404 Not Found`: Invalid or expired token
  ```json
  {
    "message": "Invalid or expired verification token."
  }
  ```
- `400 Bad Request`: Email already verified

**Business Logic:**
- Token valid for 24 hours
- Token is hashed (SHA256) in database
- After verification, user can proceed with onboarding

---

#### POST /api/auth/resend-verification
Resend email verification link.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Request Body:** None

**Success Response (200 OK):**
```json
{
  "message": "Verification email sent successfully."
}
```

**Error Responses:**
- `400 Bad Request`: Email already verified
- `429 Too Many Requests`: Rate limit exceeded (1 email/5 minutes)
- `401 Unauthorized`: Not authenticated

---

### 2.2 User Profile & Preferences

#### GET /api/users/me
Get authenticated user profile.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Query Parameters:** None

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "email": "user@example.com",
    "email_verified_at": "2025-10-08T10:05:00.000000Z",
    "nickname": "Jane",
    "home_location": "Warsaw, Poland",
    "timezone": "Europe/Warsaw",
    "onboarding_completed_at": "2025-10-08T10:15:00.000000Z",
    "onboarding_step": 4,
    "ai_generations_count_current_month": 3,
    "ai_generations_reset_at": "2025-11-01T00:00:00.000000Z",
    "created_at": "2025-10-08T10:00:00.000000Z",
    "updated_at": "2025-10-08T10:15:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated

---

#### PATCH /api/users/me
Update authenticated user profile.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Request Body:**
```json
{
  "nickname": "Jane Doe",
  "home_location": "Krakow, Poland",
  "timezone": "Europe/Warsaw"
}
```

**Validation:**
- `nickname`: optional, max 100 characters
- `home_location`: optional, max 255 characters
- `timezone`: optional, valid timezone string

**Success Response (200 OK):**
```json
{
  "message": "Profile updated successfully.",
  "data": {
    "id": 1,
    "email": "user@example.com",
    "nickname": "Jane Doe",
    "home_location": "Krakow, Poland",
    "timezone": "Europe/Warsaw",
    "updated_at": "2025-10-08T11:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `422 Unprocessable Entity`: Validation errors

---

#### DELETE /api/users/me
Delete authenticated user account (hard delete, GDPR compliance).

**Headers:**
- `Authorization: Bearer {token}` (required)

**Request Body:**
```json
{
  "confirmation": "DELETE"
}
```

**Validation:**
- `confirmation`: required, must be exactly "DELETE"

**Success Response (200 OK):**
```json
{
  "message": "Account deleted successfully."
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `422 Unprocessable Entity`: Invalid confirmation

**Business Logic:**
- Hard delete (CASCADE) all user data: profile, plans, feedback, AI generations, PDF exports
- Session is terminated
- Irreversible operation

---

#### PATCH /api/users/me/onboarding
Update onboarding progress.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Request Body:**
```json
{
  "step": 1,
  "data": {
    "nickname": "Jane",
    "home_location": "Warsaw, Poland"
  }
}
```

**Validation:**
- `step`: required, integer, 1-4
- `data`: required, object (validation depends on step)

**Step 1 - Basic Information:**
```json
{
  "step": 1,
  "data": {
    "nickname": "Jane",
    "home_location": "Warsaw, Poland"
  }
}
```
- `nickname`: required, max 100 characters
- `home_location`: required, max 255 characters

**Step 2 - Interest Categories:**
```json
{
  "step": 2,
  "data": {
    "interests_categories": [
      "historia_kultura",
      "przyroda_outdoor",
      "gastronomia"
    ]
  }
}
```
- `interests_categories`: required, array, min 1 item
- Valid values: `historia_kultura`, `przyroda_outdoor`, `gastronomia`, `nocne_zycie`, `plaze_relaks`, `sporty_aktywnosci`, `sztuka_muzea`

**Step 3 - Practical Parameters:**
```json
{
  "step": 3,
  "data": {
    "travel_pace": "umiarkowane",
    "budget_level": "standardowy",
    "transport_preference": "pieszo_publiczny",
    "restrictions": "brak"
  }
}
```
- `travel_pace`: required, enum (`spokojne`, `umiarkowane`, `intensywne`)
- `budget_level`: required, enum (`ekonomiczny`, `standardowy`, `premium`)
- `transport_preference`: required, enum (`pieszo_publiczny`, `wynajem_auta`, `mix`)
- `restrictions`: required, enum (`brak`, `dieta`, `mobilnosc`)

**Step 4 - Completion:**
```json
{
  "step": 4,
  "data": {}
}
```

**Success Response (200 OK):**
```json
{
  "message": "Onboarding step completed.",
  "data": {
    "user": {
      "id": 1,
      "onboarding_step": 3,
      "onboarding_completed_at": null
    }
  }
}
```

**Step 4 Response (Onboarding Complete):**
```json
{
  "message": "Onboarding completed successfully.",
  "data": {
    "user": {
      "id": 1,
      "onboarding_step": 4,
      "onboarding_completed_at": "2025-10-08T10:15:00.000000Z"
    }
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `422 Unprocessable Entity`: Validation errors
- `400 Bad Request`: Onboarding already completed

**Business Logic:**
- Steps must be completed sequentially (can't skip)
- Step 4 triggers welcome email
- Creates `user_preferences` record on step 2-3

---

#### GET /api/users/me/preferences
Get user travel preferences.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Query Parameters:** None

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "interests_categories": [
      "historia_kultura",
      "przyroda_outdoor",
      "gastronomia"
    ],
    "travel_pace": "umiarkowane",
    "budget_level": "standardowy",
    "transport_preference": "pieszo_publiczny",
    "restrictions": "brak",
    "created_at": "2025-10-08T10:10:00.000000Z",
    "updated_at": "2025-10-08T10:10:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `404 Not Found`: Preferences not created yet (onboarding incomplete)

---

#### PATCH /api/users/me/preferences
Update user travel preferences.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Request Body:**
```json
{
  "interests_categories": [
    "historia_kultura",
    "sztuka_muzea",
    "gastronomia",
    "nocne_zycie"
  ],
  "travel_pace": "intensywne",
  "budget_level": "premium",
  "transport_preference": "wynajem_auta",
  "restrictions": "dieta"
}
```

**Validation:**
- All fields optional (partial update)
- Same validation rules as onboarding step 2-3

**Success Response (200 OK):**
```json
{
  "message": "Preferences updated successfully.",
  "data": {
    "id": 1,
    "interests_categories": [
      "historia_kultura",
      "sztuka_muzea",
      "gastronomia",
      "nocne_zycie"
    ],
    "travel_pace": "intensywne",
    "budget_level": "premium",
    "transport_preference": "wynajem_auta",
    "restrictions": "dieta",
    "updated_at": "2025-10-08T11:30:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `404 Not Found`: Preferences not created yet
- `422 Unprocessable Entity`: Validation errors

**Business Logic:**
- Cache invalidation for user preferences (Redis)
- Changes affect future AI generations

---

### 2.3 Travel Plans

#### GET /api/travel-plans
Get list of authenticated user's travel plans.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Query Parameters:**
- `status`: Filter by status (`draft`, `planned`, `completed`) - optional
- `sort`: Sort field (`created_at`, `updated_at`, `departure_date`) - default: `-created_at`
- `page`: Page number - default: 1
- `per_page`: Items per page (1-100) - default: 20

**Example Request:**
```
GET /api/travel-plans?status=planned&sort=-departure_date&page=1&per_page=20
```

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Summer Trip to Barcelona",
      "destination": "Barcelona, Spain",
      "destination_lat": 41.3874,
      "destination_lng": 2.1686,
      "departure_date": "2025-07-15",
      "number_of_days": 7,
      "number_of_people": 2,
      "budget_per_person": 1500.00,
      "budget_currency": "EUR",
      "status": "planned",
      "has_ai_plan": true,
      "created_at": "2025-10-08T10:00:00.000000Z",
      "updated_at": "2025-10-08T12:00:00.000000Z"
    },
    {
      "id": 2,
      "title": "Weekend in Prague",
      "destination": "Prague, Czech Republic",
      "destination_lat": null,
      "destination_lng": null,
      "departure_date": "2025-11-20",
      "number_of_days": 3,
      "number_of_people": 1,
      "budget_per_person": null,
      "budget_currency": "PLN",
      "status": "draft",
      "has_ai_plan": false,
      "created_at": "2025-10-07T15:00:00.000000Z",
      "updated_at": "2025-10-07T15:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 45,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/travel-plans?page=1",
    "last": "/api/travel-plans?page=3",
    "prev": null,
    "next": "/api/travel-plans?page=2"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `422 Unprocessable Entity`: Invalid query parameters

**Business Logic:**
- Only returns plans belonging to authenticated user (row-level security)
- Soft-deleted plans are excluded
- `has_ai_plan` indicates if plan has completed AI generation

---

#### GET /api/travel-plans/{id}
Get detailed travel plan with days and points.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Path Parameters:**
- `id`: Travel plan ID (required)

**Query Parameters:**
- `include`: Comma-separated relations (`days`, `days.points`, `feedback`) - optional

**Example Request:**
```
GET /api/travel-plans/1?include=days,days.points,feedback
```

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "title": "Summer Trip to Barcelona",
    "destination": "Barcelona, Spain",
    "destination_lat": 41.3874,
    "destination_lng": 2.1686,
    "departure_date": "2025-07-15",
    "number_of_days": 7,
    "number_of_people": 2,
    "budget_per_person": 1500.00,
    "budget_currency": "EUR",
    "user_notes": "Want to see Gaudi architecture and try local tapas.",
    "status": "planned",
    "created_at": "2025-10-08T10:00:00.000000Z",
    "updated_at": "2025-10-08T12:00:00.000000Z",
    "days": [
      {
        "id": 1,
        "travel_plan_id": 1,
        "day_number": 1,
        "date": "2025-07-15",
        "summary": "Explore Gothic Quarter and La Rambla",
        "created_at": "2025-10-08T12:00:00.000000Z",
        "points": [
          {
            "id": 1,
            "plan_day_id": 1,
            "order_number": 1,
            "day_part": "rano",
            "name": "Sagrada Familia",
            "description": "Visit Gaudi's masterpiece basilica. Book tickets in advance to avoid queues. Allow 2-3 hours for thorough exploration.",
            "justification": "Matches your interest in historia_kultura and is Barcelona's most iconic landmark.",
            "duration_minutes": 180,
            "google_maps_url": "https://maps.google.com/?q=Sagrada+Familia,Barcelona",
            "location_lat": 41.4036,
            "location_lng": 2.1744,
            "created_at": "2025-10-08T12:00:00.000000Z"
          },
          {
            "id": 2,
            "plan_day_id": 1,
            "order_number": 2,
            "day_part": "poludnie",
            "name": "La Boqueria Market",
            "description": "Explore this vibrant food market on La Rambla. Try fresh seafood, jamón ibérico, and local cheeses.",
            "justification": "Perfect for your gastronomia interest with authentic local cuisine.",
            "duration_minutes": 90,
            "google_maps_url": "https://maps.google.com/?q=La+Boqueria,Barcelona",
            "location_lat": 41.3818,
            "location_lng": 2.1721,
            "created_at": "2025-10-08T12:00:00.000000Z"
          }
        ]
      }
    ],
    "feedback": {
      "id": 1,
      "travel_plan_id": 1,
      "satisfied": true,
      "issues": null,
      "other_comment": null,
      "created_at": "2025-10-08T13:00:00.000000Z"
    }
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Plan does not belong to authenticated user
- `404 Not Found`: Plan not found or soft-deleted

**Business Logic:**
- Row-level security enforced (user can only access own plans)
- Days returned in order by `day_number`
- Points returned in order by `order_number` within each day

---

#### POST /api/travel-plans
Create new travel plan (as draft).

**Headers:**
- `Authorization: Bearer {token}` (required)

**Request Body:**
```json
{
  "title": "Weekend in Prague",
  "destination": "Prague, Czech Republic",
  "departure_date": "2025-11-20",
  "number_of_days": 3,
  "number_of_people": 1,
  "budget_per_person": 800.00,
  "budget_currency": "PLN",
  "user_notes": "Interested in castles and Czech beer culture.",
  "generate_now": false
}
```

**Validation:**
- `title`: required, max 255 characters
- `destination`: required, max 255 characters
- `departure_date`: required, date format (YYYY-MM-DD), not in past
- `number_of_days`: required, integer, between 1 and 30
- `number_of_people`: required, integer, between 1 and 10
- `budget_per_person`: optional, decimal (max 10 digits, 2 decimals), positive
- `budget_currency`: optional, in ['PLN', 'USD', 'EUR'], default 'PLN'
- `user_notes`: optional, text
- `generate_now`: optional, boolean, default false

**Success Response (201 Created):**
```json
{
  "message": "Travel plan created successfully.",
  "data": {
    "id": 2,
    "title": "Weekend in Prague",
    "destination": "Prague, Czech Republic",
    "departure_date": "2025-11-20",
    "number_of_days": 3,
    "number_of_people": 1,
    "budget_per_person": 800.00,
    "budget_currency": "PLN",
    "user_notes": "Interested in castles and Czech beer culture.",
    "status": "draft",
    "created_at": "2025-10-08T14:00:00.000000Z",
    "updated_at": "2025-10-08T14:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `422 Unprocessable Entity`: Validation errors
- `400 Bad Request`: Onboarding not completed

**Business Logic:**
- Plan created with status `draft`
- If `generate_now: true`, immediately triggers AI generation (see POST /api/travel-plans/{id}/generate)
- User must have completed onboarding to create plans
- Records `plan_created` event in `user_events`

---

#### PATCH /api/travel-plans/{id}
Update existing travel plan (drafts only).

**Headers:**
- `Authorization: Bearer {token}` (required)

**Path Parameters:**
- `id`: Travel plan ID (required)

**Request Body:**
```json
{
  "title": "Extended Weekend in Prague",
  "number_of_days": 4,
  "budget_per_person": 1000.00,
  "user_notes": "Added one more day to visit Kutná Hora."
}
```

**Validation:**
- All fields optional (partial update)
- Same validation rules as POST

**Success Response (200 OK):**
```json
{
  "message": "Travel plan updated successfully.",
  "data": {
    "id": 2,
    "title": "Extended Weekend in Prague",
    "destination": "Prague, Czech Republic",
    "departure_date": "2025-11-20",
    "number_of_days": 4,
    "number_of_people": 1,
    "budget_per_person": 1000.00,
    "budget_currency": "PLN",
    "user_notes": "Added one more day to visit Kutná Hora.",
    "status": "draft",
    "updated_at": "2025-10-08T15:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Plan does not belong to authenticated user
- `404 Not Found`: Plan not found
- `422 Unprocessable Entity`: Validation errors
- `400 Bad Request`: Cannot edit plans with status `planned` or `completed` (MVP limitation)

**Business Logic:**
- Only drafts can be edited in MVP
- For generated plans, use regeneration instead
- Updates `updated_at` timestamp

---

#### DELETE /api/travel-plans/{id}
Delete travel plan (soft delete).

**Headers:**
- `Authorization: Bearer {token}` (required)

**Path Parameters:**
- `id`: Travel plan ID (required)

**Request Body:** None

**Success Response (200 OK):**
```json
{
  "message": "Travel plan deleted successfully."
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Plan does not belong to authenticated user
- `404 Not Found`: Plan not found or already deleted

**Business Logic:**
- Soft delete (sets `deleted_at` timestamp)
- Does not restore consumed AI generation limit
- Cascade deletes days, points, feedback, PDF export records via database constraints

---

### 2.4 AI Generation

#### POST /api/travel-plans/{id}/generate
Generate or regenerate AI travel plan.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Path Parameters:**
- `id`: Travel plan ID (required)

**Request Body:** None (uses data from travel plan + user preferences)

**Success Response (202 Accepted):**
```json
{
  "message": "AI generation started. Check status for progress.",
  "data": {
    "generation_id": 15,
    "travel_plan_id": 2,
    "status": "pending",
    "started_at": null,
    "estimated_duration_seconds": 30
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Plan does not belong to authenticated user
- `404 Not Found`: Plan not found
- `429 Too Many Requests`: AI generation limit exceeded (10/month)
  ```json
  {
    "message": "Monthly AI generation limit reached (10/10). Limit resets on November 1, 2025.",
    "data": {
      "current_count": 10,
      "max_count": 10,
      "reset_at": "2025-11-01T00:00:00.000000Z"
    }
  }
  ```
- `400 Bad Request`: User preferences not set (onboarding incomplete)

**Business Logic:**
- Checks user's monthly AI generation limit (10/month)
- Creates new `ai_generations` record with status `pending`
- Queues job for async AI generation (Redis queue)
- Only `completed` generations count towards limit
- Failed generations don't consume limit (rollback)
- Records `ai_generated` or `ai_regenerated` event in `user_events`
- For regeneration, overwrites existing plan days/points (no versioning in MVP)

**Notification Triggers:**
- At 8/10 generations: Send "limit_warning" email
- At 10/10 generations: Send "limit_reached" email

---

#### GET /api/travel-plans/{id}/generation-status
Check AI generation status for a travel plan.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Path Parameters:**
- `id`: Travel plan ID (required)

**Query Parameters:** None

**Success Response (200 OK):**

**Status: Pending/Processing**
```json
{
  "data": {
    "generation_id": 15,
    "travel_plan_id": 2,
    "status": "processing",
    "progress_percentage": 45,
    "started_at": "2025-10-08T14:30:00.000000Z",
    "estimated_time_remaining_seconds": 20
  }
}
```

**Status: Completed**
```json
{
  "data": {
    "generation_id": 15,
    "travel_plan_id": 2,
    "status": "completed",
    "model_used": "gpt-4o-mini",
    "tokens_used": 1250,
    "cost_usd": 0.0375,
    "started_at": "2025-10-08T14:30:00.000000Z",
    "completed_at": "2025-10-08T14:30:45.000000Z",
    "duration_seconds": 45
  }
}
```

**Status: Failed**
```json
{
  "data": {
    "generation_id": 15,
    "travel_plan_id": 2,
    "status": "failed",
    "error_message": "OpenAI API timeout after 120 seconds. Please try again.",
    "started_at": "2025-10-08T14:30:00.000000Z",
    "completed_at": "2025-10-08T14:32:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Plan does not belong to authenticated user
- `404 Not Found`: Plan or generation not found

**Business Logic:**
- Returns latest generation attempt for the plan
- Frontend should poll this endpoint during generation (every 3-5 seconds)
- `progress_percentage` is estimated based on elapsed time vs average generation time

---

### 2.5 Feedback

#### POST /api/travel-plans/{id}/feedback
Submit feedback for a generated travel plan.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Path Parameters:**
- `id`: Travel plan ID (required)

**Request Body:**
```json
{
  "satisfied": false,
  "issues": [
    "za_malo_szczegolow",
    "nie_pasuje_do_preferencji"
  ],
  "other_comment": "Too many museums, not enough outdoor activities."
}
```

**Validation:**
- `satisfied`: required, boolean
- `issues`: optional (required if `satisfied: false`), array
  - Valid values: `za_malo_szczegolow`, `nie_pasuje_do_preferencji`, `slaba_kolejnosc`, `inne`
- `other_comment`: optional, text (max 1000 characters)

**Success Response (201 Created):**
```json
{
  "message": "Feedback submitted successfully. Thank you!",
  "data": {
    "id": 3,
    "travel_plan_id": 2,
    "satisfied": false,
    "issues": [
      "za_malo_szczegolow",
      "nie_pasuje_do_preferencji"
    ],
    "other_comment": "Too many museums, not enough outdoor activities.",
    "created_at": "2025-10-08T16:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Plan does not belong to authenticated user
- `404 Not Found`: Plan not found
- `422 Unprocessable Entity`: Validation errors
- `400 Bad Request`: Feedback already submitted for this plan (unique constraint)

**Business Logic:**
- One feedback per user per plan (unique constraint)
- Feedback is optional but encouraged
- Records `feedback_submitted` event in `user_events`
- Used for analytics and plan satisfaction rate metrics

---

#### GET /api/travel-plans/{id}/feedback
Get feedback for a travel plan.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Path Parameters:**
- `id`: Travel plan ID (required)

**Query Parameters:** None

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 3,
    "travel_plan_id": 2,
    "satisfied": false,
    "issues": [
      "za_malo_szczegolow",
      "nie_pasuje_do_preferencji"
    ],
    "other_comment": "Too many museums, not enough outdoor activities.",
    "created_at": "2025-10-08T16:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Plan does not belong to authenticated user
- `404 Not Found`: Plan or feedback not found

---

### 2.6 PDF Export

#### GET /api/travel-plans/{id}/pdf
Export travel plan to PDF (triggers download).

**Headers:**
- `Authorization: Bearer {token}` (required)

**Path Parameters:**
- `id`: Travel plan ID (required)

**Query Parameters:** None

**Success Response (200 OK):**
- Content-Type: `application/pdf`
- Content-Disposition: `attachment; filename="Barcelona_Summer_Trip.pdf"`
- Body: PDF file binary

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Plan does not belong to authenticated user
- `404 Not Found`: Plan not found
- `400 Bad Request`: Plan has no AI-generated content (drafts cannot be exported)
- `500 Internal Server Error`: PDF generation failed

**Business Logic:**
- Server-side rendering using Spatie Laravel PDF (Chromium)
- PDF contains:
  - Plan header (title, destination, dates, people, budget)
  - User assumptions section (collapsed in web, expanded in PDF)
  - Full day-by-day itinerary with descriptions
  - Textual Google Maps URLs (not embedded maps)
  - Watermark: "Generated by VibeTravels"
- On-demand generation (no caching in MVP)
- Records export in `pdf_exports` table with metadata
- Records `pdf_exported` event in `user_events`
- Filename format: `{Destination}_{Title}.pdf` (sanitized, spaces replaced with underscores)

---

#### GET /api/travel-plans/{id}/pdf-exports
Get PDF export history for a travel plan.

**Headers:**
- `Authorization: Bearer {token}` (required)

**Path Parameters:**
- `id`: Travel plan ID (required)

**Query Parameters:**
- `page`: Page number - default: 1
- `per_page`: Items per page (1-50) - default: 10

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 12,
      "travel_plan_id": 2,
      "file_size_bytes": 245680,
      "exported_at": "2025-10-08T17:00:00.000000Z"
    },
    {
      "id": 8,
      "travel_plan_id": 2,
      "file_size_bytes": 245320,
      "exported_at": "2025-10-07T10:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 2,
    "per_page": 10
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: Plan does not belong to authenticated user
- `404 Not Found`: Plan not found

---

### 2.7 Analytics (Admin Only)

#### GET /api/admin/analytics/overview
Get high-level analytics overview.

**Headers:**
- `Authorization: Bearer {token}` (required)
- User must have admin role

**Query Parameters:**
- `period`: Time period (`7d`, `30d`, `90d`, `1y`, `all`) - default: `30d`

**Success Response (200 OK):**
```json
{
  "data": {
    "period": "30d",
    "users": {
      "total_registered": 347,
      "new_this_period": 42,
      "onboarding_completion_rate": 87.3,
      "with_complete_preferences": 89.2,
      "monthly_active_users": 215,
      "retention_30d": 56.8
    },
    "plans": {
      "total_created": 892,
      "created_this_period": 128,
      "by_status": {
        "draft": 234,
        "planned": 598,
        "completed": 60
      },
      "average_per_user": 2.57,
      "users_with_3plus_plans": 34.2
    },
    "ai_generations": {
      "total_this_period": 156,
      "success_rate": 96.8,
      "average_duration_seconds": 38,
      "total_cost_usd": 5.34,
      "average_cost_per_plan": 0.034
    },
    "feedback": {
      "total_submitted": 423,
      "satisfaction_rate": 73.5,
      "common_issues": {
        "za_malo_szczegolow": 45,
        "nie_pasuje_do_preferencji": 38,
        "slaba_kolejnosc": 12,
        "inne": 17
      }
    },
    "pdf_exports": {
      "total_this_period": 287,
      "export_rate": 45.2,
      "average_per_plan": 1.3
    }
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: User is not admin

---

#### GET /api/admin/analytics/ai-costs
Get detailed AI cost breakdown.

**Headers:**
- `Authorization: Bearer {token}` (required)
- User must have admin role

**Query Parameters:**
- `period`: Time period (`7d`, `30d`, `90d`, `1y`, `all`) - default: `30d`
- `group_by`: Grouping (`day`, `week`, `month`) - default: `day`

**Success Response (200 OK):**
```json
{
  "data": {
    "period": "30d",
    "total_cost_usd": 5.34,
    "total_generations": 156,
    "average_cost_per_plan": 0.034,
    "total_tokens_used": 195000,
    "by_model": {
      "gpt-4o-mini": {
        "generations": 156,
        "cost_usd": 5.34,
        "tokens_used": 195000
      }
    },
    "daily_breakdown": [
      {
        "date": "2025-10-08",
        "generations": 8,
        "cost_usd": 0.27,
        "tokens_used": 10000
      },
      {
        "date": "2025-10-07",
        "generations": 12,
        "cost_usd": 0.41,
        "tokens_used": 15000
      }
    ],
    "projections": {
      "monthly_cost_usd": 5.50,
      "yearly_cost_usd": 66.00,
      "cost_per_user_per_month": 0.016
    }
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Not authenticated
- `403 Forbidden`: User is not admin

---

## 3. Authentication & Authorization

### Authentication Method
**Laravel Sanctum (SPA Authentication)**
- Token-based authentication for API requests
- Secure, HTTP-only session cookies for web application
- CSRF protection enabled for state-changing operations

### Authentication Flow

#### Email + Password Registration
1. User submits registration form → `POST /api/auth/register`
2. Password is hashed using bcrypt (Laravel Breeze)
3. User record created with `email_verified_at: null`
4. Email verification token generated (SHA256 hashed, 24h validity)
5. Verification email queued (Laravel queue)
6. User receives email with verification link
7. User clicks link → `GET /api/auth/verify-email/{token}`
8. `email_verified_at` timestamp set, user can proceed with onboarding

#### Google OAuth Flow
1. User clicks "Sign in with Google" → `GET /api/auth/google`
2. Redirected to Google consent screen (Laravel Socialite)
3. User authorizes → Google redirects to `GET /api/auth/google/callback`
4. Backend receives OAuth code and exchanges for user data
5. Check if email exists:
   - **If exists**: Add Google provider to existing user (account merge)
   - **If new**: Create new user with `provider: 'google'`, `email_verified_at: now()`
6. User authenticated, session created, redirected to dashboard or onboarding

#### Login
1. User submits credentials → `POST /api/auth/login`
2. Credentials verified (email + bcrypt password hash)
3. Rate limiting: 5 attempts per 5 minutes (Laravel throttle middleware)
4. Session created with secure cookie
5. User data returned with auth token

#### Session Management
- Session stored in encrypted cookies (Laravel default)
- Session lifetime: 120 minutes (configurable)
- "Remember me" option extends session to 2 weeks
- Logout → `POST /api/auth/logout` invalidates session

### Authorization

#### Row-Level Security
**Implemented via Eloquent Global Scopes + Laravel Policies**

**Global Scopes (automatic filtering):**
```php
// Automatically applied to all queries
User::addGlobalScope('owned', function ($query) {
    $query->where('user_id', auth()->id());
});
```

**Laravel Policies (authorization checks):**
```php
// Example: TravelPlanPolicy
public function view(User $user, TravelPlan $plan)
{
    return $user->id === $plan->user_id;
}

public function update(User $user, TravelPlan $plan)
{
    return $user->id === $plan->user_id && $plan->status === 'draft';
}

public function delete(User $user, TravelPlan $plan)
{
    return $user->id === $plan->user_id;
}
```

**Enforcement:**
- All API endpoints check policy authorization before performing actions
- Unauthorized access returns `403 Forbidden`
- Users can only access their own resources (travel plans, preferences, feedback, etc.)

#### Admin Authorization
- Admin role stored in `users.role` column (enum: `user`, `admin`)
- Admin endpoints protected by `admin` middleware
- Admin users can access analytics and system-wide data
- No admin endpoints in public MVP (internal use only)

### Rate Limiting

**Implemented via Laravel Throttle Middleware + Redis**

| Endpoint Pattern | Limit | Window | Identifier |
|-----------------|-------|--------|------------|
| `POST /api/auth/register` | 3 attempts | 1 hour | IP address |
| `POST /api/auth/login` | 5 attempts | 5 minutes | Email + IP |
| `POST /api/auth/resend-verification` | 1 request | 5 minutes | User ID |
| `POST /api/travel-plans/{id}/generate` | 10 requests | 1 month | User ID |
| All other authenticated endpoints | 60 requests | 1 minute | User ID |
| All unauthenticated endpoints | 20 requests | 1 minute | IP address |

**Rate Limit Response (429 Too Many Requests):**
```json
{
  "message": "Too many requests. Please try again in 120 seconds.",
  "retry_after": 120
}
```

### Security Headers
- `Strict-Transport-Security`: HTTPS enforcement
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Content-Security-Policy`: Configured for Livewire + Alpine.js

---

## 4. Validation & Business Logic

### 4.1 Input Validation

**General Validation Rules (Applied to All Endpoints):**
- All input sanitized to prevent XSS attacks
- SQL injection prevented via Eloquent ORM parameter binding
- File uploads (if added later) validated for type and size
- JSON payloads validated for structure and data types
- Maximum request size: 1MB (sufficient for text-based API)

**Field-Specific Validation:**

#### User Fields
```php
'email' => 'required|email:rfc,dns|max:255|unique:users,email',
'password' => 'required|min:8|max:255',
'nickname' => 'nullable|string|max:100',
'home_location' => 'nullable|string|max:255',
'timezone' => 'nullable|timezone',
```

#### User Preferences
```php
'interests_categories' => 'required|array|min:1|max:7',
'interests_categories.*' => 'required|in:historia_kultura,przyroda_outdoor,gastronomia,nocne_zycie,plaze_relaks,sporty_aktywnosci,sztuka_muzea',
'travel_pace' => 'required|in:spokojne,umiarkowane,intensywne',
'budget_level' => 'required|in:ekonomiczny,standardowy,premium',
'transport_preference' => 'required|in:pieszo_publiczny,wynajem_auta,mix',
'restrictions' => 'required|in:brak,dieta,mobilnosc',
```

#### Travel Plan
```php
'title' => 'required|string|max:255',
'destination' => 'required|string|max:255',
'departure_date' => 'required|date|after_or_equal:today',
'number_of_days' => 'required|integer|between:1,30',
'number_of_people' => 'required|integer|between:1,10',
'budget_per_person' => 'nullable|numeric|min:0|max:99999999.99',
'budget_currency' => 'nullable|in:PLN,USD,EUR',
'user_notes' => 'nullable|string|max:5000',
```

#### Feedback
```php
'satisfied' => 'required|boolean',
'issues' => 'required_if:satisfied,false|array|max:4',
'issues.*' => 'in:za_malo_szczegolow,nie_pasuje_do_preferencji,slaba_kolejnosc,inne',
'other_comment' => 'nullable|string|max:1000',
```

### 4.2 Business Logic Implementation

#### Onboarding Flow
**Sequential Step Completion:**
1. User registers → `onboarding_step: 0`, `onboarding_completed_at: null`
2. Step 1 (Basic Info) → Updates `nickname`, `home_location` → `onboarding_step: 1`
3. Step 2 (Interests) → Creates `user_preferences` record → `onboarding_step: 2`
4. Step 3 (Parameters) → Updates `user_preferences` → `onboarding_step: 3`
5. Step 4 (Completion) → Sets `onboarding_completed_at: now()` → `onboarding_step: 4`
6. Welcome email queued and sent

**Enforcement:**
- Users cannot skip steps (validated by checking `onboarding_step`)
- Users cannot create travel plans until onboarding complete
- Onboarding cannot be restarted once completed

#### AI Generation Limit Management
**Monthly Limit: 10 Generations**

**Limit Check (before generation):**
```php
if ($user->ai_generations_count_current_month >= 10) {
    return response()->json([
        'message' => 'Monthly AI generation limit reached...',
    ], 429);
}
```

**Increment Logic:**
- Counter incremented ONLY when generation status becomes `completed`
- Failed generations do NOT consume limit (status remains `failed`, no increment)
- Regenerations consume additional limit (each attempt counted separately)

**Reset Logic (Cron Job):**
```php
// Run daily at 00:00 UTC
if (now()->day === 1) {
    User::query()->update([
        'ai_generations_count_current_month' => 0,
        'ai_generations_reset_at' => now()->addMonth()->startOfMonth(),
    ]);
}
```

**Email Notifications:**
- At 8/10 generations: Queue "limit_warning" email
- At 10/10 generations: Queue "limit_reached" email
- Each email sent once per month (tracked in `email_logs`)

#### AI Generation Process (Async Queue)
**Queue Job: GenerateTravelPlanJob**

1. **Job Dispatched:**
   - `ai_generations` record created with status `pending`
   - Job queued to Redis (Laravel queue)
   - User receives immediate response with `generation_id`

2. **Job Processing:**
   - Update status to `processing`, set `started_at`
   - Fetch user preferences from cache (Redis) or database
   - Build AI prompt with:
     - Travel plan data (destination, dates, budget, user notes)
     - User preferences (interests, pace, budget level, transport, restrictions)
   - Send request to OpenAI API (GPT-4o-mini)
   - Timeout: 120 seconds

3. **Success Path:**
   - Parse AI response (structured JSON format)
   - Create `plan_days` records (1 per day)
   - Create `plan_points` records (multiple per day, grouped by `day_part`)
   - Update `travel_plans.status` to `planned`
   - Update `ai_generations` status to `completed`
   - Save metadata: `tokens_used`, `cost_usd`, `model_used`, `completed_at`
   - Increment `user.ai_generations_count_current_month`
   - Invalidate cache for this travel plan

4. **Failure Path:**
   - Catch exceptions (timeout, API error, invalid response)
   - Update `ai_generations` status to `failed`
   - Save `error_message`
   - Do NOT increment user's generation count
   - Log error for debugging

5. **User Polling:**
   - Frontend polls `GET /api/travel-plans/{id}/generation-status` every 3-5 seconds
   - When status is `completed`, redirect to plan detail view
   - When status is `failed`, show error message with retry option

#### Travel Plan Status Transitions
**Status Workflow:**
```
draft → planned → completed
   ↓       ↑
   └───────┘ (regeneration resets to planned)
```

**Transition Logic:**
- `draft`: Initial status when plan created
- `planned`: Set after successful AI generation
- `completed`: Manually set by user after trip (future feature) OR automatically set after `departure_date + number_of_days` has passed (optional in MVP)

**Editing Restrictions:**
- Drafts: Can be edited via `PATCH /api/travel-plans/{id}`
- Planned/Completed: Cannot be edited directly (MVP limitation)
- Planned/Completed: Can be regenerated (creates new AI generation, overwrites days/points)

#### Soft Delete with GDPR Hard Delete
**Travel Plans: Soft Delete**
- `DELETE /api/travel-plans/{id}` sets `deleted_at` timestamp
- Soft-deleted plans excluded from all queries (Eloquent Global Scope)
- Allows future recovery feature (not in MVP)

**User Account: Hard Delete (GDPR)**
- `DELETE /api/users/me` triggers CASCADE delete
- All related data permanently removed:
  - User preferences
  - Travel plans (including soft-deleted)
  - Plan days and points
  - AI generations
  - Feedback
  - PDF exports
  - Email logs (optional, for debugging)
  - User events
- Session terminated immediately
- Operation is irreversible

#### Feedback Submission
**One Feedback Per Plan:**
- Unique constraint: `(travel_plan_id, user_id)`
- Cannot submit feedback twice for same plan
- If user wants to update feedback, must contact support (MVP limitation)

**Conditional Validation:**
- If `satisfied: false`, `issues` array is required (at least 1 issue)
- If `satisfied: true`, `issues` must be null or empty
- `other_comment` is always optional

#### PDF Export Generation
**Server-Side Rendering Process:**
1. Fetch travel plan with days and points (eager loading)
2. Render Blade template with plan data
3. Convert HTML to PDF using Spatie Laravel PDF (Chromium)
4. Add watermark: "Generated by VibeTravels"
5. Stream PDF to user (Content-Disposition: attachment)
6. Record export in `pdf_exports` table
7. No file storage (on-demand generation)

**Content Included:**
- Plan header (title, destination, dates, people, budget)
- User assumptions (original notes + preferences used)
- Day-by-day itinerary:
  - Day number and date
  - Summary (if available)
  - Points grouped by day part (morning, afternoon, evening, night)
  - Point details: name, description, justification, duration, Google Maps URL
- Footer: "Generated by VibeTravels" watermark

**Performance Consideration:**
- Average generation time: 5-10 seconds (depends on plan size)
- No caching in MVP (always generate fresh PDF)
- Future optimization: Cache PDF for 24 hours with invalidation on plan update

---

## 5. API Versioning

**Strategy: URL Path Versioning (Future-Proofing)**

**Current Version:** v1 (implicit, no version prefix in MVP)
- All endpoints currently under `/api/*`
- No version prefix for MVP simplicity

**Future Versioning (Post-MVP):**
- When breaking changes needed, introduce `/api/v2/*`
- Maintain backward compatibility for v1 for at least 6 months
- Deprecation notices in headers: `X-API-Deprecated: true`
- Migration guide provided in documentation

**Breaking Changes Definition:**
- Removing endpoints or fields
- Changing field data types
- Changing validation rules (more restrictive)
- Changing authentication mechanism

**Non-Breaking Changes (Safe to Deploy):**
- Adding new endpoints
- Adding optional fields to requests
- Adding new fields to responses (clients should ignore unknown fields)
- Making validation rules less restrictive

---

## 6. Error Handling

### Standard Error Response Format

**Structure:**
```json
{
  "message": "Human-readable error message",
  "errors": {
    "field_name": [
      "Specific validation error for this field"
    ]
  },
  "code": "ERROR_CODE",
  "debug": {
    "file": "/path/to/file.php",
    "line": 123,
    "trace": "..."
  }
}
```

**Notes:**
- `errors` object only present for validation errors (422)
- `debug` object only present in development environment (never in production)
- `code` is optional machine-readable error code for client handling

### HTTP Status Codes

| Status Code | Meaning | Usage |
|-------------|---------|-------|
| 200 OK | Success | Successful GET, PATCH, DELETE |
| 201 Created | Resource created | Successful POST |
| 202 Accepted | Async operation started | AI generation queued |
| 204 No Content | Success, no response body | Alternative for DELETE |
| 400 Bad Request | Invalid request | Business logic violation |
| 401 Unauthorized | Authentication required | Missing or invalid token |
| 403 Forbidden | Insufficient permissions | Resource doesn't belong to user |
| 404 Not Found | Resource not found | Invalid ID or soft-deleted |
| 422 Unprocessable Entity | Validation errors | Invalid input data |
| 429 Too Many Requests | Rate limit exceeded | Too many login attempts, AI generations |
| 500 Internal Server Error | Server error | Unexpected exception |
| 503 Service Unavailable | Service down | Maintenance mode, OpenAI API down |

### Common Error Scenarios

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated. Please login to continue."
}
```

#### 403 Forbidden
```json
{
  "message": "You do not have permission to access this resource."
}
```

#### 404 Not Found
```json
{
  "message": "Travel plan not found."
}
```

#### 422 Validation Errors
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email has already been taken."
    ],
    "password": [
      "The password must be at least 8 characters."
    ],
    "number_of_days": [
      "The number of days must be between 1 and 30."
    ]
  }
}
```

#### 429 Rate Limit
```json
{
  "message": "Too many login attempts. Please try again in 300 seconds.",
  "retry_after": 300
}
```

#### 500 Internal Server Error
```json
{
  "message": "An unexpected error occurred. Please try again later.",
  "code": "INTERNAL_ERROR"
}
```

### Error Logging
- All 500 errors logged to Laravel log files
- Sensitive data (passwords, tokens) excluded from logs
- Production: Log to file + optional external service (e.g., Sentry, future)
- Development: Log to file + display in browser (with debug info)

---

## 7. Performance Optimization

### Caching Strategy (Redis)

**Cached Data:**
1. **User Preferences** (TTL: 1 hour)
   - Key: `user:preferences:{user_id}`
   - Invalidation: On preference update

2. **AI Generation Limit** (TTL: 1 hour)
   - Key: `user:ai_limit:{user_id}`
   - Invalidation: On generation completion, monthly reset

3. **Travel Plan List** (TTL: 15 minutes)
   - Key: `user:plans:{user_id}:page:{page}:status:{status}`
   - Invalidation: On plan create/update/delete

4. **Travel Plan Detail** (TTL: 30 minutes)
   - Key: `plan:detail:{plan_id}`
   - Invalidation: On plan update, AI generation completion

**Cache Implementation:**
```php
// Example: Caching user preferences
$preferences = Cache::remember(
    "user:preferences:{$userId}",
    3600, // 1 hour
    fn() => UserPreference::where('user_id', $userId)->first()
);
```

### Database Query Optimization

**Eager Loading (N+1 Prevention):**
```php
// Good: Eager load relationships
TravelPlan::with(['days.points', 'feedback'])->find($id);

// Bad: N+1 queries (lazy loading)
$plan = TravelPlan::find($id);
foreach ($plan->days as $day) {
    foreach ($day->points as $point) { /* ... */ }
}
```

**Index Usage:**
- All foreign keys indexed (automatic)
- Frequently filtered fields indexed: `status`, `created_at`
- Composite indexes for common queries: `(user_id, created_at)`, `(travel_plan_id, day_number)`

**Query Pagination:**
- All list endpoints paginated (default 20 items)
- Maximum `per_page`: 100 (prevent excessive memory usage)

**Select Only Required Columns:**
```php
// Good: Select specific columns
User::select('id', 'email', 'nickname')->find($id);

// Avoid: Select all columns when not needed
User::find($id); // Selects all columns including password hash
```

### Async Processing (Queue System)

**Queued Jobs:**
1. **AI Generation** (GenerateTravelPlanJob)
   - Long-running operation (30-120 seconds)
   - Must not block HTTP request/response
   - Retry: 3 attempts with exponential backoff (30s, 60s, 120s)

2. **Email Sending** (SendEmailJob)
   - All emails sent asynchronously
   - Retry: 3 attempts with exponential backoff
   - Failed emails logged in `email_logs` with error message

3. **PDF Generation (Future Optimization)**
   - Currently synchronous (blocking)
   - Future: Queue for large plans (>10 days)

**Queue Configuration:**
- Driver: Redis (fast, reliable)
- Queue names: `default`, `emails`, `ai_generation`
- Worker processes: 2-4 (adjustable based on load)
- Supervisor for worker management (auto-restart on failure)

### Response Compression
- Gzip compression enabled for all JSON responses
- Reduces bandwidth by 60-80% for large payloads
- Configured at web server level (Nginx/Apache)

---

## 8. API Documentation

### Documentation Format
**OpenAPI 3.0 (Swagger) Specification**
- Machine-readable YAML/JSON format
- Interactive documentation UI (Swagger UI)
- Enables automatic client SDK generation

### Documentation Access
- **Development:** `http://localhost/api/documentation`
- **Production:** `https://vibetravels.com/api/documentation` (password-protected for MVP)

### Documentation Includes
- All endpoints with descriptions
- Request/response examples
- Validation rules
- Authentication requirements
- Rate limiting details
- Error response formats

### Maintenance
- Documentation updated with every API change
- Generated from Laravel annotations (L5-Swagger package)
- Versioned alongside API code

---

## 9. Testing Strategy

### Test Types

#### Unit Tests
- Model validation rules
- Business logic methods
- Helper functions
- Target coverage: >80%

#### Feature Tests (API Integration)
- All API endpoints tested
- Happy path + error scenarios
- Authentication/authorization checks
- Database state assertions

#### Example Feature Test:
```php
public function test_user_can_create_travel_plan()
{
    $user = User::factory()->create(['onboarding_completed_at' => now()]);

    $response = $this->actingAs($user)->postJson('/api/travel-plans', [
        'title' => 'Test Trip',
        'destination' => 'Paris',
        'departure_date' => now()->addDays(30)->format('Y-m-d'),
        'number_of_days' => 5,
        'number_of_people' => 2,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'data' => ['id', 'title', 'status']]);

    $this->assertDatabaseHas('travel_plans', [
        'title' => 'Test Trip',
        'user_id' => $user->id,
        'status' => 'draft',
    ]);
}
```

### Test Database
- Separate test database (MySQL or SQLite in-memory)
- Database refreshed before each test
- Factories for generating test data (Laravel Factory)

### Mocking External Services
- OpenAI API mocked in tests (avoid real API calls)
- Email sending mocked (avoid sending test emails)
- Mock implementations return realistic data

### CI/CD Integration
- Tests run automatically on every push (GitHub Actions)
- Pull requests blocked if tests fail
- Code coverage reports generated

---

## 10. Deployment Considerations

### Environment Variables
**Required Configuration:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vibetravels.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vibetravels_prod
DB_USERNAME=vibeuser
DB_PASSWORD=secure_password_here

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis

OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
AI_USE_REAL_API=true

MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.vibetravels.com
MAILGUN_SECRET=key-...
MAILGUN_ENDPOINT=api.eu.mailgun.net

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://vibetravels.com/api/auth/google/callback

SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

### Docker Configuration (Mentioned in Tech Stack)
- Application containerized with Docker
- Dockerfile includes: PHP 8.3, Nginx, Composer
- Docker Compose for local development (app, MySQL, Redis)
- DigitalOcean deployment via Docker image

### Production Checklist
- [ ] HTTPS enforced (SSL certificate installed)
- [ ] Environment variables set correctly
- [ ] Database migrations run
- [ ] Redis server running and accessible
- [ ] Queue workers running (Supervisor)
- [ ] Cron job configured for monthly limit reset
- [ ] Error logging configured (Laravel log)
- [ ] Rate limiting enabled
- [ ] CORS configured (if needed for frontend)
- [ ] API documentation deployed (password-protected)
- [ ] Monitoring enabled (uptime, error rates)

### Scaling Considerations (Post-MVP)
- Horizontal scaling: Multiple app servers behind load balancer
- Database: Read replicas for analytics queries
- Redis: Separate instances for cache vs queue
- Queue workers: Scale based on queue depth
- CDN for static assets (future optimization)

---

## 11. Appendix

### A. Database Schema Reference
See detailed schema in `db-plan.md`

### B. Tech Stack Reference
See tech stack details in `tech-stack.md`

### C. PRD Reference
See full product requirements in `prd.md`

### D. API Changelog (Future)
- Version 1.0 (MVP): Initial release
- Track breaking/non-breaking changes
- Migration guides for version upgrades

---

**Document Version:** 1.0
**Last Updated:** 2025-10-08
**Author:** VibeTravels Development Team
**Status:** Draft for Review
