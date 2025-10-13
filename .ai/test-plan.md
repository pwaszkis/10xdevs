Jako doświadczony inżynier QA, po dokładnej analizie dostarczonej dokumentacji i struktury kodu projektu VibeTravels, przygotowałem kompleksowy plan testów, który ma na celu zapewnienie najwyższej jakości aplikacji przed jej wdrożeniem.

***

# Plan Testów dla Aplikacji VibeTravels

## 1. Wprowadzenie i Cele Testowania

### 1.1. Wprowadzenie

Niniejszy dokument opisuje strategię, zakres, cele oraz proces testowania dla aplikacji **VibeTravels** w fazie MVP (Minimum Viable Product). VibeTravels to aplikacja internetowa oparta na Laravelu i Livewire, która wykorzystuje sztuczną inteligencję (OpenAI GPT-4o-mini) do generowania spersonalizowanych planów podróży.

Plan ten stanowi fundament dla wszystkich działań związanych z zapewnieniem jakości (QA) i ma na celu systematyczne weryfikowanie funkcjonalności, niezawodności, wydajności i bezpieczeństwa aplikacji.

### 1.2. Cele Testowania

Główne cele procesu testowego to:

*   **Weryfikacja zgodności z wymaganiami:** Upewnienie się, że wszystkie funkcje zdefiniowane w `Project Scope` działają zgodnie z dokumentacją `README.md`.
*   **Osiągnięcie kryteriów startu MVP:** Zapewnienie, że aplikacja spełnia kluczowe metryki sukcesu, takie jak:
    *   Wskaźnik ukończenia onboardingu > 70%.
    *   Wskaźnik sukcesu generowania planów przez AI > 90%.
    *   Średni czas generowania planu < 45 sekund.
    *   Wskaźnik satysfakcji z wygenerowanego planu > 60%.
    *   Brak krytycznych luk w zabezpieczeniach.
*   **Identyfikacja i eliminacja defektów:** Wykrycie, zaraportowanie i śledzenie błędów w celu ich naprawy przed wdrożeniem produkcyjnym.
*   **Zapewnienie stabilności i niezawodności:** Sprawdzenie, czy aplikacja działa stabilnie, zwłaszcza w obszarach kluczowych, takich jak procesy asynchroniczne i integracje z API.
*   **Weryfikacja doświadczenia użytkownika (UX):** Ocena, czy interfejs jest intuicyjny i przyjazny dla docelowej grupy odbiorców (Millenialsi i Pokolenie Z).

## 2. Zakres Testów

### 2.1. Funkcjonalności w Zakresie Testów

Testom poddane zostaną wszystkie funkcje zdefiniowane jako MVP, w tym:

1.  **Uwierzytelnianie i Autoryzacja:**
    *   Rejestracja przez e-mail i hasło.
    *   Logowanie przez e-mail i hasło.
    *   Integracja z Google OAuth (Logowanie przez Google).
    *   Proces weryfikacji adresu e-mail.
    *   Zarządzanie sesją i wylogowywanie.
    *   Usuwanie konta (zgodne z RODO).
2.  **Onboarding Użytkownika:**
    *   Obowiązkowy, wieloetapowy proces onboardingu.
    *   Zbieranie danych (nickname, miasto).
    *   Wybór preferencji podróżniczych (kategorie, tempo, budżet, transport, ograniczenia).
3.  **Zarządzanie Planami Podróży:**
    *   Tworzenie nowego planu (formularz).
    *   Zapisywanie planów jako szkice.
    *   Wyświetlanie listy planów z filtrowaniem i sortowaniem.
    *   Usuwanie planów.
4.  **Generowanie Planów przez AI:**
    *   Uruchomienie procesu generowania dla szkicu.
    *   Ponowne generowanie istniejącego planu.
    *   Obsługa limitu 10 darmowych generacji na miesiąc.
    *   Wyświetlanie stanu ładowania i obsługa błędów.
5.  **Wyświetlanie Planu:**
    *   Szczegółowy widok planu dzień po dniu.
    *   Wyświetlanie punktów podróży (atrakcje, opisy).
6.  **Eksport do PDF:**
    *   Generowanie i pobieranie pliku PDF z planem podróży.
7.  **System Feedbacku:**
    *   Możliwość oceny wygenerowanego planu (tak/nie).
    *   Wybór powodów negatywnej oceny.
8.  **Powiadomienia E-mail:**
    *   E-mail weryfikacyjny, powitalny, ostrzeżenia o limicie i wyczerpaniu limitu.

### 2.2. Funkcjonalności Poza Zakresem Testów

Wszystkie funkcje wymienione w sekcji `Out of Scope for MVP` w pliku `README.md` (np. edycja wygenerowanych planów, udostępnianie, subskrypcje płatne, wsparcie dla wielu języków) nie będą formalnie testowane w tej fazie.

## 3. Typy Testów

W projekcie VibeTravels zostaną przeprowadzone następujące rodzaje testów:

| Typ Testu | Opis i Cel | Narzędzia |
| :--- | :--- | :--- |
| **Testy Jednostkowe** | Weryfikacja pojedynczych klas i metod w izolacji (np. `Services`, `Actions`, `DTOs`). Celem jest zapewnienie poprawności logiki biznesowej. | PHPUnit |
| **Testy Funkcjonalne (Feature)** | Testowanie pełnych przepływów funkcjonalnych z perspektywy użytkownika, symulując żądania HTTP. **Główny ciężar testów automatycznych spocznie tutaj**, zwłaszcza na testowaniu komponentów Livewire. | PHPUnit, Laravel Test Helpers, Livewire Test Helpers |
| **Testy Integracyjne** | Weryfikacja współpracy między komponentami systemu, np. kontroler -> serwis -> model -> baza danych, a także interakcji z mockowanymi serwisami zewnętrznymi (OpenAI, Mailgun). | PHPUnit, `Queue::fake()`, `Mail::fake()`, Mock-i API |
| **Testy Statycznej Analizy Kodu** | Automatyczne wykrywanie potencjalnych błędów, niezgodności ze standardami i "code smells" bez uruchamiania kodu. | PHPStan, Laravel Pint |
| **Testy Manualne i Eksploracyjne** | Ręczne testowanie aplikacji w celu weryfikacji UX/UI, znalezienia błędów nieuchwytnych dla automatyzacji oraz oceny ogólnej jakości produktu. | Przeglądarki (Chrome, Firefox, Safari), MailHog |
| **Testy Wydajnościowe (lekkie)** | Skoncentrowane na pomiarze czasu generowania planu przez AI, aby upewnić się, że mieści się w kryterium akceptacji (< 45s). | Logowanie czasu wykonania zadania w kolejce. |
| **Testy Bezpieczeństwa (podstawowe)** | Weryfikacja podstawowych mechanizmów bezpieczeństwa, takich jak ochrona przed CSRF/XSS, autoryzacja dostępu do zasobów oraz RODO (twarde usuwanie danych). | Przegląd kodu, standardowe narzędzia Laravel. |

## 4. Scenariusze Testowe (Przykłady Wysokopoziomowe)

Poniżej przedstawiono kluczowe scenariusze testowe. Każdy z nich zostanie rozwinięty w szczegółowe przypadki testowe.

### 4.1. Uwierzytelnianie i Onboarding

*   **TC-AUTH-01:** Użytkownik pomyślnie rejestruje się za pomocą e-maila, otrzymuje i klika link weryfikacyjny, a następnie przechodzi przez wszystkie 4 kroki onboardingu.
*   **TC-AUTH-02:** Użytkownik próbuje zarejestrować się z już istniejącym adresem e-mail i otrzymuje błąd.
*   **TC-AUTH-03:** Użytkownik loguje się przez Google, zostaje poprawnie uwierzytelniony i przekierowany do onboardingu (jeśli nowy) lub dashboardu (jeśli powracający).
*   **TC-ONB-01:** Użytkownik próbuje pominąć krok w onboardingu i widzi błędy walidacji.
*   **TC-GDPR-01:** Użytkownik usuwa swoje konto; jego dane zostają trwale usunięte z bazy danych.

### 4.2. Zarządzanie i Generowanie Planu

*   **TC-PLAN-01:** Użytkownik tworzy i zapisuje plan jako "szkic". Dane w formularzu są poprawnie zapisane.
*   **TC-PLAN-02:** Użytkownik otwiera szkic i uruchamia generowanie przez AI. Po zakończeniu (poniżej 45s) status planu zmienia się na "zaplanowany", a na stronie pojawia się wygenerowana treść.
*   **TC-PLAN-03:** Użytkownik posiadający wygenerowany plan uruchamia ponowne generowanie. Limit generacji zostaje zmniejszony, a plan jest nadpisywany nową treścią.
*   **TC-LIMIT-01:** Użytkownik próbuje wygenerować 11. plan w miesiącu i otrzymuje komunikat o wyczerpaniu limitu.
*   **TC-GEN-FAIL-01:** Symulacja błędu API OpenAI podczas generowania. Zadanie w kolejce kończy się niepowodzeniem, status planu pozostaje "szkic", a limit generacji nie jest zużywany.

### 4.3. Interakcja z Planem

*   **TC-VIEW-01:** Użytkownik poprawnie widzi wygenerowany plan, w tym wszystkie dni i punkty podróży (atrakcje).
*   **TC-PDF-01:** Użytkownik klika "Eksportuj do PDF" dla wygenerowanego planu i pobiera poprawnie sformatowany plik PDF.
*   **TC-PDF-02:** Przycisk eksportu jest nieaktywny dla planu w statusie "szkic".
*   **TC-FEEDBACK-01:** Użytkownik przesyła pozytywny, a następnie negatywny feedback (z uzasadnieniem) dla wygenerowanego planu. Dane są poprawnie zapisywane.

## 5. Środowisko Testowe

*   **Środowisko Uruchomieniowe:** Aplikacja uruchamiana w kontenerach Docker zgodnie z plikami `docker-compose.yml` i `Dockerfile`.
*   **Baza Danych:** Dedykowana baza danych do testów automatycznych (np. `vibetravels_test` lub SQLite in-memory, zgodnie z `phpunit.xml`). Baza danych będzie czyszczona przed każdym testem (`RefreshDatabase` trait).
*   **Serwer E-mail:** MailHog (`http://localhost:8025`) do przechwytywania i wizualnej weryfikacji wysyłanych e-maili w środowisku deweloperskim i testowym.
*   **API Zewnętrzne:** Integracje z API (OpenAI, Google OAuth) będą mockowane na potrzeby testów automatycznych, aby zapewnić szybkość, niezawodność i zerowe koszty. W `phpunit.xml` ustawiona jest zmienna `AI_USE_REAL_API=false`.

## 6. Narzędzia do Testowania

| Kategoria | Narzędzie | Zastosowanie |
| :--- | :--- | :--- |
| **Framework Testowy** | PHPUnit | Uruchamianie testów jednostkowych i funkcjonalnych. |
| **Testowanie Komponentów**| Laravel Livewire Test Helpers | Testowanie interakcji z komponentami Livewire. |
| **Mockowanie** | Mockery, `Mail::fake()`, `Queue::fake()` | Izolowanie testowanych komponentów, symulowanie zależności. |
| **Analiza Kodu** | PHPStan, Laravel Pint | Zapewnienie jakości i spójności kodu. |
| **CI/CD** | GitHub Actions | Automatyczne uruchamianie testów po każdym pushu do repozytorium. |
| **Testowanie Manualne** | Google Chrome, Mozilla Firefox, Safari, MailHog | Weryfikacja wizualna, UX i testy eksploracyjne. |
| **Zarządzanie Błędami**| GitHub Issues | Rejestrowanie, śledzenie i zarządzanie zgłoszonymi defektami. |

## 7. Harmonogram Testów

Testowanie będzie procesem ciągłym, zintegrowanym z cyklem rozwoju (8-12 tygodni).

*   **Sprint 1-2 (Tygodnie 1-4):**
    *   Konfiguracja środowiska testowego i CI/CD.
    *   Testowanie podstawowych funkcjonalności: Rejestracja, Logowanie, Onboarding, Tworzenie szkiców.
*   **Sprint 3-4 (Tygodnie 5-8):**
    *   Intensywne testowanie integracji z AI, generowania planów i obsługi limitów.
    *   Testowanie widoku planu, eksportu PDF i systemu feedbacku.
*   **Faza Stabilizacji (Tygodnie 9-11):**
    *   Pełne testy regresji.
    *   Testy eksploracyjne i weryfikacja UX.
    *   Testy wydajnościowe (pomiar czasu generacji).
*   **Przed wdrożeniem (Tydzień 12):**
    *   Finalna runda testów dymnych (smoke tests) na środowisku stagingowym.
    *   Weryfikacja wszystkich naprawionych błędów krytycznych i głównych.

## 8. Kryteria Akceptacji Testów

### 8.1. Kryteria Wejścia

*   Kod został pomyślnie zintegrowany z główną gałęzią deweloperską.
*   Aplikacja jest możliwa do uruchomienia na środowisku testowym.
*   Wszystkie testy jednostkowe i statycznej analizy przechodzą pomyślnie w pipeline CI.

### 8.2. Kryteria Wyjścia (Zakończenia Testów)

*   Osiągnięto co najmniej 90% pokrycia kodu testami dla kluczowych ścieżek użytkownika.
*   Wszystkie zaplanowane przypadki testowe zostały wykonane.
*   Brak błędów o priorytecie krytycznym (Blocker) i wysokim (Critical).
*   Wszystkie zdefiniowane w `README.md` "MVP Launch Criteria" zostały spełnione i zweryfikowane.
*   Dokumentacja testowa jest kompletna.

## 9. Role i Odpowiedzialności

*   **Inżynier QA (Test Lead):**
    *   Tworzenie i aktualizacja planu testów.
    *   Projektowanie i wykonywanie manualnych przypadków testowych.
    *   Zarządzanie procesem zgłaszania błędów.
    *   Przeprowadzanie testów regresji i eksploracyjnych.
    *   Raportowanie o stanie jakości oprogramowania.
*   **Deweloperzy (2-3):**
    *   Pisanie testów jednostkowych i funkcjonalnych dla tworzonych przez siebie funkcjonalności.
    *   Naprawianie błędów zgłoszonych przez zespół QA.
    *   Utrzymywanie zielonego buildu w CI.
*   **Projektant (Designer):**
    *   Wsparcie w testach manualnych w zakresie weryfikacji zgodności UI/UX z projektami.

## 10. Procedury Raportowania Błędów

Wszystkie wykryte defekty będą raportowane jako **Issues** w repozytorium GitHub projektu.

### 10.1. Szablon Zgłoszenia Błędu

*   **Tytuł:** Krótki, zwięzły opis problemu (np. `[BUG] Przycisk "Zapisz szkic" nie działa po wypełnieniu formularza`).
*   **Opis:** Szczegółowy opis błędu.
*   **Kroki do odtworzenia:** Ponumerowana lista kroków potrzebnych do wywołania błędu.
*   **Oczekiwany rezultat:** Co powinno się wydarzyć.
*   **Rzeczywisty rezultat:** Co faktycznie się wydarzyło.
*   **Środowisko:** Wersja przeglądarki, system operacyjny.
*   **Priorytet:**
    *   **Blocker:** Uniemożliwia dalsze testowanie lub korzystanie z kluczowej funkcji.
    *   **Critical:** Powoduje awarię kluczowej funkcji, brak obejścia.
    *   **Major:** Poważny błąd funkcjonalny, ale istnieje obejście.
    *   **Minor:** Drobny błąd funkcjonalny lub UI.
    *   **Trivial:** Błąd kosmetyczny, literówka.
*   **Załączniki:** Zrzuty ekranu, nagrania wideo, logi konsoli.
