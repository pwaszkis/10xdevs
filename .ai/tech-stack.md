# Tech Stack - VibeTravels MVP

## Przegląd

VibeTravels to aplikacja webowa do planowania wycieczek z wykorzystaniem AI. Stack dobrany pod:
- Szybkie dostarczenie MVP (8-12 tygodni)
- Kompetencje zespołu (PHP++, frontend słabo)
- Minimalne koszty w fazie testowej i produkcyjnej
- Skalę MVP: 100-500 użytkowników, 5-20 generowań AI dziennie

---

## Stack Technologiczny

### Frontend
**Laravel 11 + Livewire 3 + Alpine.js**
- **Livewire 3**: Reaktywność bez JavaScript, komponenty w PHP
- **Alpine.js**: Minimalne UI interactions (dropdowns, modals)
- **Blade**: Templating engine Laravel
- **Tailwind CSS 4**: Utility-first styling
- **Wire UI**: Gotowe komponenty Livewire

**Uzasadnienie**: Zespół zna PHP, Livewire pozwala na 3x szybszy development niż React/Vue.

### Backend
**Laravel 11**
- **Laravel Breeze**: Autentykacja (email + Google OAuth)
- **Eloquent ORM**: Obsługa bazy danych
- **Queue System + Redis**: Async AI generation
- **Laravel Socialite**: Google OAuth

### Baza Danych
**MySQL 8**
- Znajomość zespołu
- JSON column support dla preferencji użytkownika
- Dostępność na większości hostingów

### AI Integration
**OpenAI API (GPT-4o-mini)**
- Dostęp do GPT-4o oraz GPT-4o-mini
- Stabilny provider z dobrą dokumentacją
- Package: `openai-php/laravel`

**Strategia zerowych kosztów w testach:**
```php
// Development: Mock AI service
AI_USE_REAL_API=false

// Production: Real API
AI_USE_REAL_API=true
OPENAI_MODEL=gpt-4o-mini
```

**Expected Costs:**
- GPT-4o-mini: ~$0.02-0.05 USD/plan
- ~5-20 generowań/dzień = $3-30/miesiąc MVP

### PDF Export
**Spatie Laravel PDF**
- Server-side rendering (Chromium)
- Blade templates → PDF
- Watermarks, headers, footers

### Email System
**Laravel Mail + Mailgun**
- Mailgun: 5,000 emaili gratis/msc
- EU servers (GDPR)
- ~$5-10/miesiąc dla MVP


### CI/CD
- Github Actions do tworzenia pipeline’ów CI/CD
- DigitalOcean do hostowania aplikacji za pośrednictwem obrazu docker
