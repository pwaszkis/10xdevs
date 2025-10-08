# Database Planning Summary - VibeTravels MVP

## Decisions Made

### 1. User Authentication & Authorization
- **Single users table** with columns for both email+password and OAuth authentication
- Provider enum: `'email'`, `'google'`
- Nullable `password` for OAuth users
- `provider_id` for OAuth provider ID
- Composite unique index on `(provider, provider_id)` with consideration for NULL values
- Multi-provider support: merge accounts when OAuth email matches existing email account

### 2. User Preferences
- **Separate `user_preferences` table** with 1:1 relationship to users
- JSON column for `interests_categories` (array of selected interests)
- ENUM columns for practical parameters: `travel_pace`, `budget_level`, `transport_preference`, `restrictions`
- Created only on first save (not automatically at registration)
- Interests values: "historia_kultura", "przyroda_outdoor", "gastronomia", "nocne_zycie", "plaze_relaks", "sporty_aktywnosci", "sztuka_muzea"

### 3. Travel Plans Structure
Three-table hierarchy:
- **`travel_plans`** - main plan information
- **`plan_days`** - days of the plan (1:N relationship)
- **`plan_points`** - sightseeing points per day (1:N relationship)

Plan status: ENUM('draft', 'planned', 'completed') with default 'draft'

### 4. AI Generations
- **Separate `ai_generations` table** with 1:N relationship to travel_plans
- Tracks generation history for regenerations
- Metadata includes: tokens_used, cost_usd, model_used, status
- New record created for each regeneration
- Counter incremented only for 'completed' status (failed generations don't consume limit)

### 5. AI Generation Limits
- Counter in `users` table: `ai_generations_count_current_month` (INT, default 0)
- Reset timestamp: `ai_generations_reset_at`
- Cron job resets counter on first day of month
- Alternative: query on `ai_generations` table with MONTH filter

### 6. Feedback System
- **`feedback` table** with 1:1 relationship to travel_plans
- One feedback per user per plan
- Issues stored as JSON array: ["za_malo_szczegolow", "nie_pasuje_do_preferencji", "slaba_kolejnosc", "inne"]
- Optional `other_comment` TEXT field

### 7. PDF Exports
- **`pdf_exports` table** with 1:N relationship to travel_plans
- Metadata only (no file storage)
- Tracks: travel_plan_id, user_id, file_size_bytes, exported_at
- On-demand generation (no caching in MVP)

### 8. Email System
- **`email_verifications`** - verification tokens (hashed with SHA256/bcrypt)
- **`email_logs`** - email tracking without Mailgun webhooks in MVP
- Email templates in Blade files, not database
- Optional metadata JSON in email_logs for template variables

### 9. Data Types & Constraints
- `users.email`: UNIQUE, NOT NULL with format validation in Laravel
- `users.password`: VARCHAR 255, NULLABLE, bcrypt hashed
- `travel_plans.title`: VARCHAR 255, NOT NULL
- `travel_plans.destination`: VARCHAR 255, NOT NULL
- `travel_plans.departure_date`: DATE, NOT NULL (validation in Laravel, not DB constraint)
- `travel_plans.number_of_days`: TINYINT with CHECK BETWEEN 1 AND 30
- `travel_plans.number_of_people`: TINYINT with CHECK BETWEEN 1 AND 10
- `travel_plans.budget_per_person`: DECIMAL(10,2), NULLABLE
- `travel_plans.budget_currency`: VARCHAR(3), DEFAULT 'PLN'

### 10. Indexes Strategy
- `users.email` - UNIQUE index (automatic)
- `travel_plans.user_id` - INDEX
- `travel_plans.status` - INDEX (for quick filters)
- `travel_plans.created_at` - INDEX (for sorting)
- `ai_generations(user_id, created_at)` - Composite index
- `plan_days(travel_plan_id, day_number)` - Composite index + UNIQUE
- `plan_points(plan_day_id, order_number)` - Composite index + UNIQUE

### 11. Security & Privacy
- **Row Level Security**: Laravel Eloquent Global Scopes + Policies (MySQL lacks native RLS)
- **Password hashing**: bcrypt through Laravel Breeze
- **Email verification tokens**: hashed (SHA256/bcrypt)
- **Session management**: encrypted cookies (Laravel default)
- **HTTPS**: enforced on production
- **Soft delete**: travel_plans (with `deleted_at`)
- **Hard delete**: users (GDPR compliance) with CASCADE delete of all related data

### 12. Scalability & Performance
- **No partitioning** for MVP (100-500 users)
- **Queue system**: Redis + Laravel queue with `jobs` table
- **AI generation status**: ENUM('pending', 'processing', 'completed', 'failed')
- **Redis cache**: user preferences, AI limits (TTL: 1 hour)
- **No metrics aggregation** in MVP (on-demand queries)

### 13. Analytics & Tracking
- **`user_events` table** for behavioral analytics
- Event types: login, logout, onboarding_completed, plan_created, plan_saved_as_draft, ai_generated, ai_regenerated, pdf_exported, feedback_submitted
- Event data stored as JSON
- No IP address or user agent tracking in MVP
- AI cost tracking in `ai_generations`: tokens_used (INT), cost_usd (DECIMAL(10,4)), model_used (VARCHAR 50)

### 14. Onboarding
- Onboarding state in `users` table:
  - `onboarding_completed_at` (TIMESTAMP, NULLABLE)
  - `onboarding_step` (TINYINT, DEFAULT 0) - values 0-4
- Allows resuming interrupted onboarding

### 15. Rate Limiting
- Laravel built-in throttle middleware
- Redis counters: `rate_limit:{user_id}:login`, `rate_limit:{user_id}:ai_generation`, `rate_limit:{user_id}:email_verification`
- No separate `rate_limits` table in MVP

### 16. Additional Features
- **Google Maps**: `plan_points.google_maps_url` VARCHAR(500)
- **Optional coordinates**: `destination_lat`, `destination_lng` (DECIMAL) in travel_plans and plan_points
- **Return date**: calculated in application (not stored)
- **Plan summary**: optional TEXT field in plan_days (optional for MVP)
- **Premium waitlist**: optional separate table (can use Google Form instead)

### 17. Database Configuration
- **Charset**: utf8mb4 (emoji support)
- **Collation**: utf8mb4_unicode_ci (case-insensitive, better sorting)
- **Timezone**: UTC for all timestamps
- **ID type**: INT UNSIGNED (sufficient for MVP scale)
- **Timestamps**: created_at, updated_at on all tables (Laravel convention)

### 18. Laravel Standard Tables
- **`password_resets`**: Laravel Breeze standard (email, token, created_at)
- **`jobs`**: Laravel queue table
- **`failed_jobs`**: Failed queue jobs tracking
- **No audit log** in MVP (simplicity over completeness)
- **No full-text search** in MVP (LIKE queries sufficient)

### 19. Foreign Keys & Cascade
- **ON DELETE CASCADE** for all foreign keys:
  - `user_id` FK: deletes all user data (GDPR hard delete)
  - `travel_plan_id` FK: deletes days, points, feedback, exports
  - `plan_day_id` FK: deletes points
- **ON UPDATE RESTRICT**: default (IDs don't change)

### 20. Migrations vs Schema Builder
- Suggested migration order (if using migrations):
  1. create_users_table
  2. create_user_preferences_table
  3. create_travel_plans_table
  4. create_plan_days_table
  5. create_plan_points_table
  6. create_ai_generations_table
  7. create_feedback_table
  8. create_pdf_exports_table
  9. create_email_verifications_table
  10. create_email_logs_table
  11. create_user_events_table
  12. Foreign keys in separate migrations

Alternative: Schema builder may be preferred

### 21. Seed Data
Development seeders for:
- 10-20 fake users (Laravel Faker)
- 50-100 travel plans in various statuses
- AI generations with mock data
- Feedback examples
- Enables testing analytics queries and UI without manual data entry

---

## Matched Recommendations

### Users Table Structure
```sql
users:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - email (VARCHAR 255, UNIQUE, NOT NULL)
  - password (VARCHAR 255, NULLABLE)
  - provider (ENUM('email', 'google'), DEFAULT 'email')
  - provider_id (VARCHAR 255, NULLABLE)
  - email_verified_at (TIMESTAMP, NULLABLE)
  - nickname (VARCHAR 100, NULLABLE)
  - home_location (VARCHAR 255, NULLABLE)
  - timezone (VARCHAR 50, DEFAULT 'UTC', NULLABLE)
  - onboarding_completed_at (TIMESTAMP, NULLABLE)
  - onboarding_step (TINYINT, DEFAULT 0)
  - ai_generations_count_current_month (INT, DEFAULT 0)
  - ai_generations_reset_at (TIMESTAMP, NULLABLE)
  - remember_token (VARCHAR 100, NULLABLE)
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)
  - INDEX (email) - UNIQUE
```

### User Preferences Table Structure
```sql
user_preferences:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - user_id (INT UNSIGNED, UNIQUE, NOT NULL)
  - interests_categories (JSON, NOT NULL)
  - travel_pace (ENUM('spokojne', 'umiarkowane', 'intensywne'), NOT NULL)
  - budget_level (ENUM('ekonomiczny', 'standardowy', 'premium'), NOT NULL)
  - transport_preference (ENUM('pieszo_publiczny', 'wynajem_auta', 'mix'), NOT NULL)
  - restrictions (ENUM('brak', 'dieta', 'mobilnosc'), NOT NULL)
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)
  - FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
```

### Travel Plans Table Structure
```sql
travel_plans:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - user_id (INT UNSIGNED, NOT NULL)
  - title (VARCHAR 255, NOT NULL)
  - destination (VARCHAR 255, NOT NULL)
  - destination_lat (DECIMAL(10,8), NULLABLE)
  - destination_lng (DECIMAL(11,8), NULLABLE)
  - departure_date (DATE, NOT NULL)
  - number_of_days (TINYINT, NOT NULL, CHECK BETWEEN 1 AND 30)
  - number_of_people (TINYINT, NOT NULL, CHECK BETWEEN 1 AND 10)
  - budget_per_person (DECIMAL(10,2), NULLABLE)
  - budget_currency (VARCHAR(3), DEFAULT 'PLN', CHECK IN ('PLN', 'USD', 'EUR'))
  - user_notes (TEXT, NULLABLE)
  - status (ENUM('draft', 'planned', 'completed'), DEFAULT 'draft')
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)
  - deleted_at (TIMESTAMP, NULLABLE)
  - FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  - INDEX (user_id)
  - INDEX (status)
  - INDEX (created_at)
```

### Plan Days Table Structure
```sql
plan_days:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - travel_plan_id (INT UNSIGNED, NOT NULL)
  - day_number (TINYINT, NOT NULL)
  - date (DATE, NOT NULL)
  - summary (TEXT, NULLABLE)
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)
  - FOREIGN KEY (travel_plan_id) REFERENCES travel_plans(id) ON DELETE CASCADE
  - UNIQUE (travel_plan_id, day_number)
  - INDEX (travel_plan_id, day_number)
```

### Plan Points Table Structure
```sql
plan_points:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - plan_day_id (INT UNSIGNED, NOT NULL)
  - order_number (TINYINT, NOT NULL)
  - day_part (ENUM('rano', 'poludnie', 'popoludnie', 'wieczor'), NOT NULL)
  - name (VARCHAR 255, NOT NULL)
  - description (TEXT, NOT NULL)
  - justification (TEXT, NULLABLE)
  - duration_minutes (SMALLINT, NULLABLE)
  - google_maps_url (VARCHAR 500, NULLABLE)
  - location_lat (DECIMAL(10,8), NULLABLE)
  - location_lng (DECIMAL(11,8), NULLABLE)
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)
  - FOREIGN KEY (plan_day_id) REFERENCES plan_days(id) ON DELETE CASCADE
  - UNIQUE (plan_day_id, order_number)
  - INDEX (plan_day_id, order_number)
```

### AI Generations Table Structure
```sql
ai_generations:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - travel_plan_id (INT UNSIGNED, NOT NULL)
  - user_id (INT UNSIGNED, NOT NULL)
  - status (ENUM('pending', 'processing', 'completed', 'failed'), DEFAULT 'pending')
  - model_used (VARCHAR 50, NULLABLE)
  - tokens_used (INT, NULLABLE)
  - cost_usd (DECIMAL(10,4), NULLABLE)
  - error_message (TEXT, NULLABLE)
  - started_at (TIMESTAMP, NULLABLE)
  - completed_at (TIMESTAMP, NULLABLE)
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)
  - FOREIGN KEY (travel_plan_id) REFERENCES travel_plans(id) ON DELETE CASCADE
  - FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  - INDEX (user_id, created_at)
  - INDEX (travel_plan_id)
```

### Feedback Table Structure
```sql
feedback:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - travel_plan_id (INT UNSIGNED, NOT NULL)
  - user_id (INT UNSIGNED, NOT NULL)
  - satisfied (BOOLEAN, NOT NULL)
  - issues (JSON, NULLABLE)
  - other_comment (TEXT, NULLABLE)
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)
  - FOREIGN KEY (travel_plan_id) REFERENCES travel_plans(id) ON DELETE CASCADE
  - FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  - UNIQUE (travel_plan_id, user_id)
```

### PDF Exports Table Structure
```sql
pdf_exports:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - travel_plan_id (INT UNSIGNED, NOT NULL)
  - user_id (INT UNSIGNED, NOT NULL)
  - file_size_bytes (INT, NULLABLE)
  - exported_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
  - FOREIGN KEY (travel_plan_id) REFERENCES travel_plans(id) ON DELETE CASCADE
  - FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  - INDEX (travel_plan_id)
  - INDEX (user_id)
```

### Email Verifications Table Structure
```sql
email_verifications:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - user_id (INT UNSIGNED, NOT NULL)
  - token (VARCHAR 64, UNIQUE, NOT NULL)
  - expires_at (TIMESTAMP, NOT NULL)
  - verified_at (TIMESTAMP, NULLABLE)
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)
  - FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  - INDEX (token)
  - INDEX (user_id)
```

### Email Logs Table Structure
```sql
email_logs:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - user_id (INT UNSIGNED, NULLABLE)
  - email (VARCHAR 255, NOT NULL)
  - email_type (ENUM('verification', 'welcome', 'limit_warning', 'limit_reached', 'trip_reminder'), NOT NULL)
  - status (ENUM('queued', 'sent', 'delivered', 'failed', 'bounced'), DEFAULT 'queued')
  - metadata (JSON, NULLABLE)
  - sent_at (TIMESTAMP, NULLABLE)
  - delivered_at (TIMESTAMP, NULLABLE)
  - error_message (TEXT, NULLABLE)
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)
  - FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  - INDEX (user_id, email_type, sent_at)
```

### User Events Table Structure
```sql
user_events:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - user_id (INT UNSIGNED, NULLABLE)
  - event_type (ENUM('login', 'logout', 'onboarding_completed', 'plan_created', 'plan_saved_as_draft', 'ai_generated', 'ai_regenerated', 'pdf_exported', 'feedback_submitted'), NOT NULL)
  - event_data (JSON, NULLABLE)
  - created_at (TIMESTAMP)
  - FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  - INDEX (user_id, created_at)
  - INDEX (event_type, created_at)
```

### Laravel Standard Tables
```sql
password_resets:
  - email (VARCHAR 255, NOT NULL)
  - token (VARCHAR 255, NOT NULL)
  - created_at (TIMESTAMP)
  - INDEX (email)

jobs:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - queue (VARCHAR 255, NOT NULL)
  - payload (LONGTEXT, NOT NULL)
  - attempts (TINYINT UNSIGNED, NOT NULL)
  - reserved_at (INT UNSIGNED, NULLABLE)
  - available_at (INT UNSIGNED, NOT NULL)
  - created_at (INT UNSIGNED, NOT NULL)
  - INDEX (queue)

failed_jobs:
  - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
  - uuid (VARCHAR 255, UNIQUE, NOT NULL)
  - connection (TEXT, NOT NULL)
  - queue (TEXT, NOT NULL)
  - payload (LONGTEXT, NOT NULL)
  - exception (LONGTEXT, NOT NULL)
  - failed_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
  - INDEX (uuid)
```

---

## Database Planning Summary

### Overview
The VibeTravels MVP database schema is designed for a Laravel 11 + MySQL 8 application supporting AI-powered travel planning. The schema prioritizes simplicity, GDPR compliance, and scalability for 100-500 early adopters with 5-20 AI generations daily.

### Core Entities

#### 1. Users & Authentication
- Unified authentication supporting both email+password and Google OAuth
- Multi-provider support allowing account merging when OAuth email matches existing account
- Mandatory email verification with hashed tokens (24-hour validity)
- Onboarding progress tracking with step counter (0-4)
- AI generation limit tracking with monthly reset mechanism

#### 2. User Preferences
- Separate normalized table for travel preferences (1:1 with users)
- JSON array for interests categories (7 predefined values)
- ENUM fields for practical parameters (pace, budget, transport, restrictions)
- Lazy creation (only when user completes onboarding)

#### 3. Travel Plans Hierarchy
Three-level structure:
- **Travel Plans**: Core plan metadata (destination, dates, budget, user notes)
- **Plan Days**: Individual days within a plan (with date and optional summary)
- **Plan Points**: Sightseeing points within each day (with time, location, description)

This normalized structure enables flexible querying and future features like day reordering.

#### 4. AI Integration
- Comprehensive generation tracking with cost and token metrics
- History preservation for regenerations (new record per attempt)
- Status workflow: pending → processing → completed/failed
- Failed generations don't consume user's monthly limit
- Detailed error logging for troubleshooting

#### 5. Analytics & Tracking
- Event-based tracking for user behavior analysis
- JSON storage for flexible event-specific data
- Cost tracking for AI operations (DECIMAL precision for USD amounts)
- No IP/user agent tracking in MVP (privacy-first approach)

### Security Implementation

#### Row-Level Security
MySQL lacks native RLS, implemented through:
- **Eloquent Global Scopes**: Automatic filtering by `user_id = auth()->id()`
- **Laravel Policies**: Authorization checks for view/update/delete operations
- Example: `$user->id === $travelPlan->user_id`

#### Data Protection
- Bcrypt password hashing (Laravel Breeze automatic)
- SHA256/bcrypt hashing for email verification tokens
- Encrypted session cookies (Laravel default)
- HTTPS enforcement on production
- Hidden password attribute in API responses

#### GDPR Compliance
- Hard delete on account removal (CASCADE delete all related data)
- Soft delete for travel_plans (user-recoverable)
- No long-term storage of AI prompts/responses
- No tracking cookies (only session/auth cookies)

### Performance Optimization

#### Indexing Strategy
- **Simple indexes**: email (unique), status, created_at
- **Composite indexes**: (user_id, created_at), (travel_plan_id, day_number)
- **Unique constraints**: (travel_plan_id, day_number), (plan_day_id, order_number)

#### Caching
- Redis cache for frequently accessed data (user preferences, AI limits)
- 1-hour TTL with invalidation on data change
- Laravel Cache facade: `Cache::remember()`

#### Queue System
- Redis-backed Laravel queue for async AI generation
- Separate `jobs` and `failed_jobs` tables for tracking
- Status tracking in `ai_generations` table

### Data Integrity

#### Constraints
- CHECK constraints for ranges (days: 1-30, people: 1-10)
- CHECK constraints for ENUM-like values (budget_currency)
- NOT NULL constraints on required fields
- UNIQUE constraints on natural keys

#### Foreign Keys
- Consistent ON DELETE CASCADE behavior
- Ensures referential integrity
- Automatic cleanup of orphaned records

#### Validation Layers
- Database-level: CHECK constraints, foreign keys
- Application-level: Laravel validation rules
- Double validation for critical data (dates, ranges)

### Scalability Considerations

#### MVP Scope
- No partitioning (unnecessary for 100-500 users)
- INT UNSIGNED for IDs (4.3B limit, sufficient for MVP)
- No metrics aggregation tables (on-demand queries)
- No full-text search (LIKE queries sufficient)

#### Future-Proofing
- Normalized schema allows easy addition of features
- JSON columns for flexible data (event_data, metadata)
- History tracking (ai_generations) enables trend analysis
- Soft deletes enable recovery features

### Email System
- Comprehensive tracking without external webhooks (MVP simplicity)
- Rate limiting support through indexed timestamps
- Email templates in code (Blade), not database
- Status tracking: queued → sent (MVP) with webhook expansion path

### Special Features

#### Multi-Provider OAuth
- Composite unique index on (provider, provider_id)
- Account merging when OAuth email matches existing account
- Graceful handling of NULL provider_id for email users

#### Regeneration Support
- New ai_generations record per regeneration attempt
- Cost and token tracking for each attempt
- History enables analysis of regeneration patterns

#### Soft Delete with GDPR
- travel_plans use soft delete (user convenience)
- user deletion triggers CASCADE hard delete (GDPR compliance)
- Balanced approach to data retention and privacy

---

## Unresolved Issues

### 1. OAuth Account Merging Details
**Issue**: The exact flow for merging accounts when a user registers via Google OAuth with an email that already exists in the system needs clarification.

**Questions**:
- Should we automatically merge and add provider='google' + provider_id to existing record?
- Or show error "Email already registered, please login with password"?
- How to handle case where user already has Google OAuth and tries to add email+password?

**Impact**: Affects user experience and `users` table unique constraints implementation.

**Recommendation for Resolution**: Define user flow in authentication controller before implementing OAuth.

### 2. Composite Unique Index on (provider, provider_id)
**Issue**: MySQL unique indexes with NULL values can be problematic. Multiple rows with `provider='email'` and `provider_id=NULL` would violate uniqueness.

**Questions**:
- Should we use a partial/filtered index (MySQL 8.0.13+ supports functional indexes)?
- Or create separate indexes: UNIQUE(provider, provider_id) WHERE provider_id IS NOT NULL?
- Or handle uniqueness in application layer?

**Impact**: Data integrity for OAuth accounts.

**Recommendation for Resolution**: Test MySQL behavior with NULL values in composite unique index before migration.

### 3. Plan Summary Field Usage
**Issue**: The `summary` field in `plan_days` is marked as optional for MVP, but unclear if AI should generate it.

**Questions**:
- Should AI generate day summaries in initial MVP?
- If not, when should this feature be added?
- Should summary be excluded from initial schema and added later?

**Impact**: AI prompt complexity and token costs.

**Recommendation for Resolution**: Decide during AI prompt engineering phase; include nullable field in schema for flexibility.

### 4. Return Date Calculation
**Issue**: Confirmed to calculate in application, but unclear if we need this frequently.

**Questions**:
- Should we add a generated/virtual column for performance?
- MySQL: `return_date DATE AS (DATE_ADD(departure_date, INTERVAL number_of_days DAY)) VIRTUAL`

**Impact**: Query performance if return_date is frequently filtered/sorted.

**Recommendation for Resolution**: Start with application-level calculation; add virtual column if performance issues arise.

### 5. Database Driver for Queue System
**Issue**: Tech stack mentions "Queue System + Redis" but Laravel can use database or Redis for queues.

**Questions**:
- Use Redis driver (no `jobs` table needed)?
- Or database driver with Redis cache (keep `jobs` table)?
- Does "Queue System + Redis" mean Redis for queue or just cache?

**Impact**: Whether to include `jobs` table in schema.

**Recommendation for Resolution**: Clarify with tech lead; database driver recommended for MVP simplicity (no Redis configuration required).

### 6. ID Type Consistency
**Issue**: Recommendation changed from BIGINT to INT UNSIGNED during conversation.

**Questions**:
- Should all IDs be INT UNSIGNED consistently?
- Or use BIGINT for high-volume tables (user_events, pdf_exports)?

**Impact**: Future scalability and migration complexity.

**Current Decision**: INT UNSIGNED for all tables (sufficient for MVP scale).

### 7. Email Verification Token Hashing Algorithm
**Issue**: Mentioned "SHA256 or bcrypt" but not specified which to use.

**Questions**:
- SHA256 (faster, sufficient for tokens)?
- Or bcrypt (slower, but more secure)?

**Impact**: Performance of verification endpoint.

**Recommendation for Resolution**: Use SHA256 for email tokens (bcrypt for passwords only) - standard Laravel approach.

### 8. Timestamp Consistency
**Issue**: Some tables use specific timestamp columns (exported_at, sent_at) instead of created_at.

**Questions**:
- Should we keep specialized timestamp names?
- Or standardize to created_at for consistency?

**Current Decision**: Use specialized names (exported_at, sent_at) where semantically meaningful, otherwise created_at.

---

## Next Steps

1. **Resolve OAuth merging flow** - document authentication controller behavior
2. **Test MySQL NULL handling** in composite unique indexes
3. **Decide on queue driver** (Redis vs database)
4. **Verify token hashing** implementation (SHA256 vs bcrypt)
5. **Create Laravel migrations** following the defined structure
6. **Implement seeders** for development data
7. **Set up Eloquent models** with relationships and scopes
8. **Configure database** charset (utf8mb4) and collation (utf8mb4_unicode_ci)
9. **Implement Global Scopes** for row-level security
10. **Write Laravel Policies** for authorization

---

## Technical Notes

### MySQL 8 Features Used
- JSON column type with validation
- CHECK constraints (8.0.16+)
- ENUM types for controlled vocabularies
- Composite indexes for query optimization

### Laravel Conventions Followed
- Snake_case table and column names
- Plural table names (users, travel_plans)
- Standard timestamps (created_at, updated_at, deleted_at)
- Foreign key naming: {table}_id
- Standard tables: password_resets, jobs, failed_jobs

### Performance Considerations
- Strategic indexing based on query patterns
- Redis caching for hot data
- Async processing for AI generation (queue)
- No premature optimization (no partitioning, no aggregation tables)

### Security Considerations
- No sensitive data in plain text
- Hashed tokens for verification
- CASCADE deletes for GDPR compliance
- Rate limiting support through indexed timestamps
- Session security through Laravel defaults
