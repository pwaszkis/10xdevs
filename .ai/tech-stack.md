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
**Laravel Mail + SendGrid**
- SendGrid: 100 emaili/dzień gratis (3,000/msc) - DARMOWE
- EU servers (GDPR)
- Wystarczające dla MVP (0 kosztów)
- Alternatywy: Resend (3k/msc), SMTP2GO (1k/msc)
- ⚠️ Mailgun usunął darmowy plan (najtańszy €14/msc)


### CI/CD & Production Infrastructure
- **GitHub Actions** do tworzenia pipeline'ów CI/CD
  - Automatyczny deployment przy push do `main`
  - Testy, PHPStan, Pint przed deployment
  - Manual deployment trigger
- **DigitalOcean** do hostowania aplikacji za pośrednictwem Docker
  - Droplet: Ubuntu 24.04 LTS (2GB RAM, Frankfurt datacenter)
  - Automated backups enabled ($2.40/mo)
- **Cloudflare** (Free tier)
  - DNS management
  - CDN i cache
  - DDoS protection
  - Free SSL certificates
- **Domain**: OVH (.com ~$12/rok, .pl ~$8/rok)

### Production Stack Details

**Docker Compose Services:**
- `app`: PHP 8.3 + Laravel 11 + Supervisor
- `nginx`: Nginx Alpine z SSL (Let's Encrypt)
- `mysql`: MySQL 8.0 z persistent storage
- `redis`: Redis 7 dla cache i queue
- `worker`: Laravel Queue worker (dedicated container)
- `scheduler`: Laravel Scheduler (cron)
- `certbot`: SSL certificate auto-renewal

**Security:**
- Wszystkie klucze API w GitHub Secrets (nie w repo)
- `.env` tylko na serwerze produkcyjnym
- Firewall (ufw): tylko porty 22, 80, 443
- Fail2ban dla ochrony przed brute force
- SSL/TLS z HSTS enabled
- Security headers (X-Frame-Options, CSP, etc.)

**Koszty miesięczne (MVP):**
| Usługa | Koszt |
|--------|-------|
| OVH Domain | ~$1 |
| DigitalOcean Droplet (2GB) | $12 |
| DigitalOcean Backups | $2.40 |
| Cloudflare DNS | $0 (free) |
| SendGrid | $0 (3k emails/msc free) |
| OpenAI API | $3-30 (usage-based) |
| **TOTAL** | **$18-47/msc** |

**Skalowanie (przyszłość):**
- Droplet 4GB RAM: $24/msc (500-2000 użytkowników)
- Managed MySQL: $15/msc (przy bottleneck DB)
- Managed Redis: $15/msc (high-traffic queue)
- Load Balancer: $12/msc (multi-instance)
