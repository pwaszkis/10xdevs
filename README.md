# VibeTravels

> AI-powered travel itinerary planning application

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)](https://www.php.net/)

## Table of Contents

- [About](#about)
- [Tech Stack](#tech-stack)
- [Getting Started](#getting-started)
- [Available Scripts](#available-scripts)
- [Project Scope](#project-scope)
- [Project Status](#project-status)
- [Features](#features)
- [Development](#development)
- [Testing](#testing)
- [Code Quality](#code-quality)
- [Contributing](#contributing)
- [License](#license)

## About

VibeTravels is a web application that transforms simple travel notes and ideas into detailed, personalized day-by-day itineraries using artificial intelligence. The platform helps users create engaging travel plans tailored to their individual preferences, budget, timeframe, and group size.

**Target Audience**: Millennials and Gen Z travelers (25-40 years) who travel 2-4 times per year and seek a streamlined trip planning experience.

**MVP Scale**: Designed for 100-500 early adopters with 5-20 AI generations per day.

### Key Highlights

- **AI-Powered Planning**: Generates comprehensive travel itineraries using OpenAI GPT-4o-mini
- **Personalized Recommendations**: Tailored suggestions based on user preferences and interests
- **User-Friendly Interface**: Built with Livewire 3 for reactive, seamless user experience
- **PDF Export**: Download and share trip plans offline
- **Free Tier**: 10 AI-generated itineraries per month
- **Privacy-First**: GDPR compliant with secure data handling

## Tech Stack

### Frontend
- **Framework**: Laravel 11 with Blade templating
- **Reactivity**: Livewire 3 (PHP-based reactivity without heavy JavaScript)
- **UI Interactions**: Alpine.js
- **Styling**: Tailwind CSS 4 (utility-first CSS)
- **Components**: Wire UI (pre-built Livewire components)
- **Build Tool**: Vite 6

### Backend
- **Framework**: Laravel 11
- **Authentication**: Laravel Breeze + Laravel Socialite (Google OAuth)
- **ORM**: Eloquent
- **Queue System**: Redis-backed Laravel Queues
- **Background Jobs**: Async AI generation processing

### Database & Cache
- **Database**: MySQL 8 (with JSON column support)
- **Cache/Queue**: Redis

### Third-Party Integrations
- **AI Provider**: OpenAI API (GPT-4o-mini model)
  - Package: `openai-php/laravel`
  - Cost: ~$0.02-0.05 USD per plan
- **PDF Generation**: Spatie Laravel PDF (Chromium-based server-side rendering)
- **Email Service**: Laravel Mail + Mailgun
  - 5,000 emails free/month
  - EU servers (GDPR compliant)

### Development & Deployment
- **Containerization**: Docker + Docker Compose
- **Runtime**: PHP 8.3
- **Asset Bundling**: Vite
- **CI/CD**: GitHub Actions
- **Hosting**: DigitalOcean (Docker-based deployment)
- **Email Testing**: MailHog (development environment)

### Code Quality Tools
- **Static Analysis**: PHPStan
- **Code Formatting**: Laravel Pint, PHP CS Fixer
- **Testing**: PHPUnit

## Getting Started

### Requirements

- Docker and Docker Compose (v2+)
- Make (optional, for convenience commands)
- Git

### Quick Start

```bash
# Clone the repository
git clone <repository-url> vibetravels
cd vibetravels

# Copy environment file
cp .env.example .env

# Run automated setup (if Make is installed)
make setup

# Or manually with Docker
docker compose build
docker compose up -d
docker compose exec app composer install
docker compose exec app npm install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate

# Build frontend assets
docker compose exec app npm run build

# Access the application
open http://localhost
```

### Available Services

Once running, you can access:

- **Application**: http://localhost
- **MailHog UI** (email testing): http://localhost:8025
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

For detailed installation and configuration instructions, see **[SETUP.md](SETUP.md)**.

## Available Scripts

### NPM Scripts

```bash
# Start Vite development server
npm run dev

# Build assets for production
npm run build
```

### Make Commands

```bash
# Start development environment
make up

# Stop development environment
make down

# Access application container shell
make shell

# Run all tests
make test

# Run tests with coverage
make test-coverage

# Run static analysis
make phpstan

# Check code style
make cs-check

# Fix code style automatically
make cs-fix

# Run all quality checks
make quality

# View logs
make logs

# Full setup (build, install dependencies, migrate)
make setup
```

### Artisan Commands

```bash
# Run migrations
docker compose exec app php artisan migrate

# Run tests
docker compose exec app php artisan test

# Generate application key
docker compose exec app php artisan key:generate

# Clear cache
docker compose exec app php artisan cache:clear
```

For a complete command reference, see **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)**.

## Project Scope

### MVP Features

#### User Authentication & Authorization
- Email + password registration and login
- Google OAuth integration (Sign in with Google)
- Email verification (mandatory)
- Session management with secure cookies
- Account deletion (GDPR compliant hard delete)

#### User Onboarding
- Mandatory onboarding flow after registration
- Basic user data collection (nickname, home city/country)
- Travel interest categories selection (multi-select):
  - History & Culture
  - Nature & Outdoor
  - Gastronomy
  - Nightlife & Entertainment
  - Beaches & Relaxation
  - Sports & Activities
  - Art & Museums
- Practical parameters (single-select):
  - Travel pace: Relaxed / Moderate / Intensive
  - Budget: Budget / Standard / Premium
  - Transportation: Walking & Public / Car Rental / Mix
  - Restrictions: None / Diet (vegetarian/vegan) / Mobility

#### User Profile
- View and edit profile information
- Manage travel preferences
- Profile completion tracking

#### Travel Plan Management
- Create new trip plans with form inputs:
  - Trip title, destination, departure date
  - Number of days (1-30), number of people (1-10)
  - Estimated budget per person (optional)
  - Notes and ideas (free text)
- Save plans as drafts (without AI generation)
- View all user plans with filtering:
  - All / Drafts / Planned / Completed
- Sort by creation or modification date
- Delete plans
- Unlimited plan storage

#### AI-Powered Itinerary Generation
- Generate detailed day-by-day itineraries
- Monthly limit: 10 free generations
- Reset on 1st of each month
- Input: User form data + preferences
- Output: Structured daily schedule with:
  - Activities by time of day (morning, afternoon, evening)
  - Attraction descriptions and justifications
  - Estimated visit duration
  - Google Maps links (text URLs)
- Plan regeneration (consumes additional generation quota)
- Loading states and error handling
- AI metadata tracking (tokens, cost, timestamp)

#### Feedback System
- Post-generation satisfaction survey
- Binary satisfaction question (yes/no)
- Issue categorization for negative feedback:
  - Insufficient details
  - Doesn't match preferences
  - Poor visit sequence
  - Other (with optional text)

#### PDF Export
- Server-side PDF generation
- Includes full itinerary with descriptions
- Google Maps text URLs
- "Generated by VibeTravels" watermark
- Download to user device
- Export tracking

#### Email Notifications
- Verification email (mandatory, 24h validity)
- Welcome email (after onboarding completion)
- Usage limit warnings (at 8/10 generations)
- Limit exhausted notification (at 10/10)
- Optional: Trip reminder (3 days before departure)

#### Analytics & Monitoring
- Onboarding completion rate tracking
- User preference completion percentage
- Plans per user metrics
- AI generation volume (daily/monthly)
- Plan satisfaction rate
- PDF export rate
- Monthly active users (MAU)
- 30-day retention
- AI cost tracking (tokens, costs per plan)

### Out of Scope for MVP

The following features are **NOT** included in the MVP:

- ❌ Editing generated plans (regeneration only)
- ❌ Plan sharing between users
- ❌ Public read-only plan links
- ❌ Booking integrations (hotels, attractions, transport)
- ❌ Rich media support (photo uploads, galleries)
- ❌ Advanced logistics planning (schedule integrations)
- ❌ Apple Sign-In
- ❌ External API integrations (Booking.com, TripAdvisor, etc.)
- ❌ Paid subscriptions and payment processing
- ❌ Mobile native apps (iOS/Android)
- ❌ Progressive Web App (PWA) features
- ❌ Destination autocomplete
- ❌ Interactive embedded maps
- ❌ Real-time collaboration
- ❌ Social features (comments, likes, followers)
- ❌ Plan versioning/history
- ❌ Import from other sources
- ❌ Multi-language support (English or Polish only)

## Project Status

- **Phase**: MVP Development
- **Timeline**: 8-12 weeks (2-3 developers + 1 designer)
- **Target Launch**: Q1 2025 (before vacation planning season: January-March)
- **Current Status**: 🚧 In Development

### Success Metrics

**Primary Business Metrics:**
- 90% of users have completed travel preferences
- 75% of users generate 3+ plans per year

**MVP Launch Criteria:**
- Onboarding completion rate >70%
- AI generation success rate >90%
- Average generation time <45 seconds
- Plan satisfaction rate >60%
- Zero critical security vulnerabilities

**3-Month Goals:**
- 100-500 registered users
- 80%+ users with completed preferences
- 40%+ 30-day retention
- 65%+ plan satisfaction rate
- 50%+ monthly active users

## Features

### For Users

✅ **Smart Travel Planning**
- Transform rough ideas into detailed itineraries
- AI considers your interests, pace, budget, and transportation preferences
- Day-by-day schedules with time-of-day breakdowns

✅ **Personalization**
- Select from 7 interest categories
- Configure 4 practical parameters
- AI generates plans matching your profile

✅ **Easy Management**
- Centralized dashboard for all trips
- Filter by status (drafts, planned, completed)
- Save drafts without using AI quota

✅ **Export & Share**
- Download plans as professional PDFs
- Offline access to itineraries
- Shareable documents with watermark

✅ **Free Tier**
- 10 AI generations per month
- Unlimited plan storage
- Unlimited exports

### For Developers

🔧 **Modern Stack**
- Laravel 11 with latest features
- Livewire 3 for reactive components
- Dockerized development environment
- Vite for fast asset building

🔧 **Quality Assurance**
- PHPStan for static analysis
- Laravel Pint for code formatting
- Automated testing suite
- GitHub Actions CI/CD

🔧 **Developer Experience**
- Make commands for common tasks
- Hot module replacement with Vite
- MailHog for email testing
- Comprehensive documentation

## Development

### Common Commands

```bash
# Start development environment
make up

# Access application container shell
make shell

# Run tests
make test

# Run code quality checks
make quality

# View logs
make logs

# Stop environment
make down
```

### Project Structure

```
.
├── app/                    # Application code
│   ├── Http/              # Controllers, Middleware, Requests
│   ├── Livewire/          # Livewire components
│   ├── Models/            # Eloquent models
│   ├── Services/          # Business logic services
│   └── Mail/              # Email templates
├── database/              # Migrations, seeders, factories
│   ├── migrations/        # Database migrations
│   ├── seeders/           # Database seeders
│   └── factories/         # Model factories
├── resources/             # Views, CSS, JS
│   ├── views/             # Blade templates
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
├── routes/                # Application routes
│   ├── web.php            # Web routes
│   └── api.php            # API routes
├── tests/                 # Test suite
│   ├── Feature/           # Feature tests
│   └── Unit/              # Unit tests
├── docker/                # Docker configuration
│   ├── php/               # PHP/app container config
│   ├── nginx/             # Nginx config
│   └── mysql/             # MySQL config
├── public/                # Public assets
├── storage/               # Application storage
└── config/                # Configuration files
```

### Environment Configuration

Key environment variables to configure:

```env
# Application
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=vibetravels
DB_USERNAME=vibetravels
DB_PASSWORD=root

# Redis
REDIS_HOST=redis

# Mail (Development)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

# AI Configuration (Development - Mock)
AI_USE_REAL_API=false

# AI Configuration (Production)
AI_USE_REAL_API=true
OPENAI_API_KEY=your-api-key-here
OPENAI_MODEL=gpt-4o-mini

# Google OAuth
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
```

For production configuration, see **[DEPLOYMENT.md](DEPLOYMENT.md)**.

## Testing

### Running Tests

```bash
# Run all tests
docker compose exec app php artisan test

# Run with coverage
make test-coverage

# Run specific test file
docker compose exec app php artisan test tests/Feature/AuthTest.php

# Run specific test method
docker compose exec app php artisan test --filter=test_user_can_register
```

### Test Structure

- **Unit Tests**: Located in `tests/Unit/`
- **Feature Tests**: Located in `tests/Feature/`
- **Coverage Reports**: Generated in `coverage/` directory

## Code Quality

### Static Analysis

```bash
# Run PHPStan
make phpstan

# Or directly
docker compose exec app ./vendor/bin/phpstan analyse
```

### Code Style

```bash
# Check code style with Laravel Pint
make cs-check

# Fix code style automatically
make cs-fix

# Or use PHP CS Fixer
docker compose exec app ./vendor/bin/php-cs-fixer fix
```

### Run All Quality Checks

```bash
# Run static analysis + code style checks + tests
make quality
```

## Troubleshooting

### Port Already in Use

```bash
# Check what's using port 80
sudo lsof -i :80

# Or change the port in docker-compose.yml
# Map to different port, e.g., 8080:80
```

### Permission Issues

```bash
# Fix storage and cache permissions
docker compose exec app chmod -R 777 storage bootstrap/cache
```

### Database Connection Failed

```bash
# Restart MySQL container
docker compose restart mysql

# Check MySQL logs
docker compose logs mysql

# Verify database credentials in .env
```

### Vite Not Loading Assets

```bash
# Rebuild assets
docker compose exec app npm run build

# For development with HMR
docker compose exec app npm run dev
```

### Docker Build Fails

```bash
# Clean rebuild
docker compose down -v
docker compose build --no-cache
docker compose up -d
```

For more troubleshooting tips, see **[SETUP.md](SETUP.md#troubleshooting)**.

## Contributing

We welcome contributions! Please follow these guidelines:

### Getting Started

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run quality checks (`make quality`)
5. Commit your changes (see commit conventions below)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Commit Convention

We follow [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` - New feature
- `fix:` - Bug fix
- `refactor:` - Code refactoring
- `test:` - Adding or updating tests
- `docs:` - Documentation updates
- `style:` - Code formatting (no functional changes)
- `chore:` - Maintenance tasks
- `perf:` - Performance improvements

**Example:**
```bash
git commit -m "feat: add PDF export functionality"
git commit -m "fix: resolve email verification bug"
git commit -m "docs: update setup instructions"
```

### Pull Request Guidelines

- Ensure all tests pass
- Update documentation if needed
- Add tests for new features
- Keep PRs focused on a single feature/fix
- Provide clear description of changes

## Documentation

- **[CLAUDE.md](CLAUDE.md)** - 🤖 Claude Code AI agent reference (Docker commands, project structure)
- **[SETUP.md](SETUP.md)** - Detailed installation and configuration guide
- **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - Command reference card
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Production deployment guide
- **[Laravel Documentation](https://laravel.com/docs/11.x)** - Official Laravel framework documentation
- **[Livewire Documentation](https://livewire.laravel.com/docs)** - Livewire component documentation

## License

This project is open-sourced software licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/vibetravels/issues)
- **Documentation**: Check the project documentation files
- **Laravel Help**: [Laravel Documentation](https://laravel.com/docs)
- **Livewire Help**: [Livewire Documentation](https://livewire.laravel.com)

---

**Built with ❤️ using Laravel 11, Livewire 3, and AI**
