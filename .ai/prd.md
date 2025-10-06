# Dokument wymagań produktu (PRD) - VibeTravels

## 1. Przegląd produktu

VibeTravels to aplikacja webowa umożliwiająca użytkownikom tworzenie spersonalizowanych planów podróży przy wykorzystaniu sztucznej inteligencji. Aplikacja przekształca proste notatki i pomysły dotyczące wycieczek w szczegółowe, dzień po dniu harmonogramy zwiedzania, uwzględniające indywidualne preferencje turystyczne, budżet, czas i liczbę uczestników.

Produkt jest skierowany do millenialsów i przedstawicieli generacji Z (25-40 lat), którzy podróżują 2-4 razy w roku i poszukują narzędzia usprawniającego proces planowania wycieczek. MVP skupia się na kluczowych funkcjonalnościach: zarządzaniu notatkami, generowaniu planów AI, systemie preferencji użytkownika oraz eksporcie do PDF.

Platforma: Responsive web application
Timeline: 8-12 tygodni z zespołem 2-3 devs + 1 designer
Uruchomienie: Przed sezonem planowania wakacji (styczeń-marzec)
Skalowalność MVP: 100-500 early adopters, 5-20 generowań dziennie

## 2. Problem użytkownika

Planowanie angażujących i interesujących wycieczek jest trudne i czasochłonne. Użytkownicy napotykają następujące problemy:

- Trudność w przekształceniu luźnych pomysłów i notatek w konkretny, wykonalny plan
- Brak spersonalizowanych rekomendacji uwzględniających indywidualne zainteresowania i preferencje
- Konieczność przeszukiwania wielu źródeł informacji o atrakcjach, kolejności zwiedzania i logistyce
- Czasochłonny research miejsc do odwiedzenia i optymalizacja trasy
- Brak narzędzia, które łączyłoby kreatywność z praktycznym planowaniem

VibeTravels rozwiązuje te problemy poprzez wykorzystanie potencjału AI do automatycznego generowania szczegółowych planów podróży, które uwzględniają preferencje użytkownika, parametry praktyczne (tempo, budżet, transport) oraz konkretne cele i notatki dotyczące wycieczki.

## 3. Wymagania funkcjonalne

### 3.1 System autentykacji i autoryzacji

- Rejestracja użytkownika za pomocą email + hasło
- Logowanie za pomocą email + hasło
- Integracja z Google OAuth (Sign in with Google)
- Weryfikacja adresu email (obowiązkowa) z wysyłką emaila weryfikacyjnego
- Hashowanie haseł przy użyciu bcrypt
- Zarządzanie sesjami użytkownika
- Możliwość wylogowania
- Funkcja usunięcia konta (hard delete, zgodność z GDPR)
- HTTPS tylko (wymuszenie bezpiecznego połączenia)

### 3.2 Onboarding użytkownika

- Obowiązkowy proces onboardingu po pierwszej rejestracji
- Ekran powitalny po rejestracji
- Zbieranie danych podstawowych:
  - Nick użytkownika
  - Email (już zebrany podczas rejestracji)
  - Kraj/miasto domowe
- Wybór kategorii zainteresowań (multi-select, 5-7 opcji):
  - Historia i kultura
  - Przyroda i outdoor
  - Gastronomia
  - Nocne życie i rozrywka
  - Plaże i relaks
  - Sporty i aktywności
  - Sztuka i muzea
- Ustawienie parametrów praktycznych (3-5 opcji):
  - Tempo podróży: Spokojne / Umiarkowane / Intensywne
  - Budżet: Ekonomiczny / Standardowy / Premium
  - Transport: Pieszo i transport publiczny / Wynajem auta / Mix
  - Ograniczenia: Brak / Dieta (wegetariańska/wegańska) / Mobilność (dostępność)
- Flow: rejestracja → powitanie → dane podstawowe → kategorie zainteresowań → parametry praktyczne → dashboard
- Tracking completion rate onboardingu

### 3.3 Profil użytkownika

- Wyświetlanie i edycja danych profilu:
  - Nick
  - Email (tylko wyświetlanie, weryfikowany)
  - Kraj/miasto domowe
- Zarządzanie preferencjami turystycznymi:
  - Edycja kategorii zainteresowań
  - Edycja parametrów praktycznych
- Dostęp do ustawień profilu z dashboard (sidebar/topbar)
- Tracking wypełnienia profilu (czy wszystkie pola preferencji są uzupełnione)

### 3.4 Dashboard (główny ekran)

- Hero section z personalizowanym powitaniem: "Cześć [Nick]! Zaplanuj swoją kolejną przygodę"
- Call-to-action: "Stwórz nowy plan" (primary button)
- Lista istniejących planów użytkownika:
  - Sortowanie: najnowsze na górze (domyślnie)
  - Wyświetlanie podstawowych informacji dla każdego planu: tytuł, destynacja, daty, status
- Quick filters dla listy planów:
  - Wszystkie
  - Szkice
  - Zaplanowane
  - Zrealizowane
- Sidebar lub topbar z nawigacją:
  - Link do profilu
  - Link do ustawień
  - Licznik limitów generowań AI: "X/10 w tym miesiącu"
  - Przycisk wylogowania

### 3.5 Tworzenie notatki/planu

- Formularz tworzenia nowego planu z polami:
  - Tytuł planu (required, text input)
  - Destynacja (required, text input)
  - Data wyjazdu (required, date picker)
  - Liczba dni (required, number input, zakres 1-30)
  - Liczba osób (required, number input, zakres 1-10)
  - Szacunkowy budżet na osobę (optional, number input z wyborem waluty)
  - Twoje pomysły i notatki (optional, textarea - wolne pole tekstowe)
- Walidacja pól formularza
- Dwa przyciski akcji:
  - "Generuj plan" (primary) - tworzy plan i wysyła do AI
  - "Zapisz jako szkic" (secondary) - zapisuje dane bez generowania AI
- Zapisywanie metadanych:
  - Data utworzenia (created_at)
  - Data ostatniej modyfikacji (updated_at)
  - Status początkowy: szkic (jeśli zapisano bez generowania)

### 3.6 Generowanie planów AI

- Integracja z AI Provider (OpenAI GPT-4 lub Anthropic Claude)
- System limitów:
  - 10 generowań miesięcznie za darmo
  - Reset limitu pierwszego dnia każdego miesiąca kalendarzowego
  - Wyświetlanie aktualnego stanu limitu (X/10)
- Przed generowaniem:
  - Sprawdzanie dostępnego limitu
  - Jeśli limit wyczerpany: komunikat informacyjny + informacja o waitlist na premium
- Loading state podczas generowania:
  - Komunikat "Generuję plan..." lub podobny
  - Wskaźnik postępu lub animacja
- Proces generowania:
  - Przesłanie danych z formularza + preferencji użytkownika do AI
  - Przetworzenie odpowiedzi AI
  - Zapisanie wygenerowanego planu w bazie danych
  - Zmiana statusu z "szkic" na "zaplanowane" (jeśli wcześniej był szkic)
- Tracking metadanych AI:
  - Zużyte tokeny
  - Koszt generowania
  - Data i czas generowania
- Obsługa błędów AI:
  - Timeout request
  - Błędy API
  - Niekompletne odpowiedzi
- Po generowaniu:
  - Przekierowanie do widoku wygenerowanego planu
  - Wyświetlenie formularza feedbacku

### 3.7 Struktura wygenerowanego planu

- Header planu:
  - Tytuł
  - Destynacja
  - Daty (od-do)
  - Liczba osób
  - Budżet (opcjonalnie)
- Sekcja "Twoje założenia" (collapsed by default):
  - Oryginalne notatki użytkownika
  - Preferencje użyte podczas generowania
- Plan dzień po dniu (karty dla każdego dnia):
  - Nagłówek: "Dzień X - DD.MM.YYYY"
  - Podział na pory dnia: rano, południe, popołudnie, wieczór
  - Każdy punkt zawiera:
    - Nazwa atrakcji/miejsca
    - Krótki opis (2-3 zdania)
    - Uzasadnienie dopasowania do preferencji użytkownika
    - Orientacyjny czas wizyty
    - Link do Google Maps (tekstowy URL)
- Footer:
  - Formularz feedbacku
  - Przycisk "Export do PDF"
  - Przycisk "Regeneruj plan" (z warning o zużyciu limitu)

### 3.8 Zarządzanie planami

- Widok listy wszystkich planów użytkownika
- Nieograniczona liczba planów
- Sortowanie:
  - Domyślnie: najnowsze na górze
  - Sortowanie po dacie utworzenia
  - Sortowanie po dacie modyfikacji
- Filtrowanie po statusie:
  - Wszystkie
  - Szkice (plany zapisane bez generowania lub niezakończone)
  - Zaplanowane (plany z wygenerowanym harmonogramem)
  - Zrealizowane (plany po dacie wyjazdu lub ręcznie oznaczone)
- Akcje dla każdego planu:
  - Wyświetlenie szczegółów
  - Usunięcie planu
  - Regeneracja planu (dla planów już wygenerowanych)
- Tracking statusu:
  - created_at
  - updated_at
  - status (szkic/zaplanowane/zrealizowane)

### 3.9 Regeneracja planu

- Dostępna dla planów już wygenerowanych
- Przycisk "Regeneruj plan" w widoku szczegółów planu
- Warning przed regeneracją:
  - "Spowoduje to wygenerowanie nowego planu (X/10 w tym miesiącu)"
  - Potwierdzenie akcji przez użytkownika
- Regeneracja zużywa dodatkowe generowanie z limitu miesięcznego
- Proces analogiczny do pierwszego generowania
- Nadpisanie poprzedniego planu nowym (brak wersjonowania w MVP)

### 3.10 System feedbacku

- Formularz wyświetlany po wygenerowaniu planu
- Pytanie podstawowe: "Czy plan spełnia Twoje oczekiwania?" (tak/nie)
- Przy odpowiedzi "nie": checkboxy z opcjami:
  - Za mało szczegółów
  - Nie pasuje do moich preferencji
  - Słaba kolejność zwiedzania
  - Inne (opcjonalne pole tekstowe)
- Zapisywanie feedbacku w bazie danych:
  - travel_id
  - satisfied (boolean)
  - issues (array)
  - created_at
- Feedback jest opcjonalny (możliwość pominięcia)

### 3.11 Export do PDF

- Przycisk "Export do PDF" w widoku szczegółów planu
- Server-side rendering (Puppeteer lub biblioteka PDF)
- Zawartość PDF:
  - Tytuł planu
  - Destynacja i daty
  - Plan dzień po dniu z pełnymi opisami
  - Tekstowe URL-e do Google Maps (nie embedded mapy)
  - Watermark "Generated by VibeTravels"
- Generowanie on-demand (bez cachowania w MVP)
- Download pliku PDF na urządzenie użytkownika
- Tracking eksportów (liczba eksportów na plan)

### 3.12 System powiadomień email

- Email weryfikacyjny (obowiązkowy):
  - Wysyłany automatycznie po rejestracji
  - Link weryfikacyjny z tokenem
  - Ważność linku: 24 godziny
- Welcome email:
  - Wysyłany po ukończeniu onboardingu
  - Podstawowe tips dotyczące korzystania z aplikacji
- Powiadomienie o zbliżającym się limicie:
  - Wysyłane przy 8/10 wykorzystanych generowań
  - Informacja o pozostałych generowaniach
- Powiadomienie o osiągnięciu limitu:
  - Wysyłane po wykorzystaniu 10/10 generowań
  - Informacja o resecie pierwszego dnia następnego miesiąca
  - Opcjonalnie: informacja o waitlist na premium
- Przypomnienie przed wycieczką (opcjonalne):
  - Wysyłane 3 dni przed datą wyjazdu
  - Podsumowanie planu

### 3.13 Security i Privacy

- Hashowanie haseł przy użyciu bcrypt
- HTTPS wymuszony na wszystkich endpointach
- Session management z secure cookies
- Hard delete przy usuwaniu konta (pełne usunięcie danych użytkownika)
- Podstawowa Privacy Policy i Terms of Service (do przygotowania)
- Brak tracking cookies (tylko session/auth cookies)
- Metadata AI bez długoterminowego przechowywania promptów i odpowiedzi
- Walidacja i sanitizacja wszystkich inputów użytkownika
- Rate limiting dla wrażliwych operacji (login, rejestracja, generowanie AI)

### 3.14 Analytics i monitoring

- Tracking kluczowych metryk:
  - Completion rate onboardingu
  - Procent użytkowników z wypełnionymi preferencjami
  - Liczba planów per użytkownik
  - Liczba generowań AI dziennie/miesięcznie
  - Plan satisfaction rate (feedback)
  - Export rate (% planów eksportowanych do PDF)
  - Monthly active users
  - Retention (30 dni)
- Tracking kosztów AI:
  - Suma zużytych tokenów
  - Suma kosztów generowań
  - Średni koszt na plan
- Podstawowe error logging
- Monitoring dostępności systemu

## 4. Granice produktu

### 4.1 Funkcjonalności wykluczone z MVP

Następujące funkcjonalności NIE będą implementowane w wersji MVP:

- Edycja wygenerowanych planów (możliwa tylko regeneracja całości)
- Współdzielenie planów między kontami użytkowników
- Public links do planów (read-only sharing)
- Rezerwacje i booking atrakcji/hoteli/transportu
- Bogata obsługa multimediów (upload zdjęć, galerie, zdjęcia miejsc)
- Zaawansowane planowanie czasu i logistyki (integracje z rozkładami jazdy)
- Apple Sign-In (tylko email+hasło i Google OAuth)
- Integracje z zewnętrznymi API poza Google Maps (bez Booking.com, TripAdvisor, etc.)
- Płatne subskrypcje i processing płatności (waitlist zamiast płatności)
- Mobile native apps (iOS/Android)
- Progressive Web App (PWA) - tylko standardowa aplikacja webowa
- Autocomplete dla destynacji (free text input)
- Mapy interaktywne w planach (tylko tekstowe linki)
- Współpraca real-time nad planami
- Social features (komentarze, lajki, followers, aktywność znajomych)
- Wersjonowanie planów (historia zmian)
- Import planów z innych źródeł

### 4.2 Techniczne uproszczenia w MVP

- Brak zaawansowanej analityki user behavior
- Podstawowy design system (nie custom design library)
- Minimalistyczny email design (proste HTML templates)
- Brak A/B testingu interfejsu
- Brak zaawansowanego error trackingu (podstawowy logging zamiast Sentry)
- Brak cache'owania dla PDF (generowanie on-demand)
- Pojedyncza waluta dla budżetu (bez multi-currency)
- Brak automatycznych transitions statusów planów
- Brak embedded Google Maps (tylko linki tekstowe)

### 4.3 Ograniczenia biznesowe MVP

- Wsparcie językowe: tylko angielski (lub tylko polski - do ustalenia)
- Grupa docelowa: 100-500 early adopters
- Limit generowań: 10 miesięcznie bez możliwości zwiększenia (poza ręczną interwencją admina)
- Brak płatnych planów w MVP
- Podstawowy support użytkowników (email, bez live chat)
- Brak programu referral/affiliate

## 5. Historyjki użytkowników

### 5.1 Autentykacja i onboarding

US-001: Rejestracja za pomocą email i hasła
Jako nowy użytkownik chcę zarejestrować się w aplikacji za pomocą adresu email i hasła, aby móc korzystać z funkcji planowania wycieczek.

Kryteria akceptacji:
- Formularz rejestracji zawiera pola: email, hasło, potwierdzenie hasła
- Email jest walidowany pod kątem poprawnego formatu
- Hasło musi spełniać wymagania bezpieczeństwa (minimum 8 znaków)
- System sprawdza, czy email nie jest już zarejestrowany w bazie
- Po udanej rejestracji wysyłany jest email weryfikacyjny
- Użytkownik otrzymuje komunikat o konieczności weryfikacji emaila
- Hasło jest hashowane przy użyciu bcrypt przed zapisem do bazy
- System obsługuje błędy (email już istnieje, słabe hasło, niezgodność haseł)

US-002: Rejestracja za pomocą Google OAuth
Jako nowy użytkownik chcę zarejestrować się za pomocą konta Google, aby szybko utworzyć konto bez podawania hasła.

Kryteria akceptacji:
- Formularz rejestracji zawiera przycisk "Sign in with Google"
- Po kliknięciu następuje przekierowanie do strony autoryzacji Google
- System pobiera z Google: email, imię, zdjęcie profilowe (opcjonalnie)
- Konto jest automatycznie tworzone po autoryzacji
- Email z Google jest automatycznie oznaczony jako zweryfikowany
- Użytkownik jest przekierowywany do procesu onboardingu
- System obsługuje anulowanie autoryzacji przez użytkownika

US-003: Weryfikacja adresu email
Jako nowy użytkownik chcę zweryfikować swój adres email poprzez kliknięcie w link weryfikacyjny, aby potwierdzić swoją tożsamość.

Kryteria akceptacji:
- Po rejestracji system wysyła email z linkiem weryfikacyjnym
- Link zawiera unikalny token ważny przez 24 godziny
- Po kliknięciu w link użytkownik jest przekierowywany do aplikacji
- Status emaila zmienia się na "zweryfikowany"
- Wyświetlany jest komunikat potwierdzający weryfikację
- Przeterminowany link wyświetla odpowiedni komunikat błędu
- Użytkownik może poprosić o ponowne wysłanie linku weryfikacyjnego

US-004: Logowanie do aplikacji
Jako zarejestrowany użytkownik chcę zalogować się do aplikacji za pomocą email i hasła, aby uzyskać dostęp do moich planów podróży.

Kryteria akceptacji:
- Formularz logowania zawiera pola: email, hasło
- System weryfikuje poprawność danych logowania
- Po pomyślnym logowaniu użytkownik jest przekierowywany do dashboard
- Sesja użytkownika jest zapisywana (secure cookies)
- Błędne dane logowania wyświetlają odpowiedni komunikat
- System wymusza HTTPS dla operacji logowania
- Zaimplementowany rate limiting chroniący przed brute force

US-005: Logowanie przez Google OAuth
Jako zarejestrowany użytkownik chcę zalogować się za pomocą konta Google, aby szybko uzyskać dostęp bez wpisywania hasła.

Kryteria akceptacji:
- Formularz logowania zawiera przycisk "Sign in with Google"
- System rozpoznaje istniejące konto powiązane z Google
- Po autoryzacji użytkownik jest przekierowywany do dashboard
- Sesja jest tworzona analogicznie jak przy standardowym logowaniu
- System obsługuje przypadek, gdy konto Google nie jest zarejestrowane

US-006: Proces onboardingu - dane podstawowe
Jako nowy użytkownik po pierwszym zalogowaniu chcę podać swoje dane podstawowe (nick, miasto domowe), aby personalizować doświadczenie korzystania z aplikacji.

Kryteria akceptacji:
- Po pierwszym logowaniu użytkownik jest przekierowywany do onboardingu
- Ekran powitalny wyjaśnia cel onboardingu
- Formularz zawiera pola: nick, kraj/miasto domowe
- Wszystkie pola są wymagane
- Formularz zawiera przycisk "Dalej"
- Po wypełnieniu użytkownik przechodzi do następnego kroku (kategorie zainteresowań)
- Dane są zapisywane w profilu użytkownika
- Onboarding nie może być pominięty (brak opcji "skip")

US-007: Proces onboardingu - kategorie zainteresowań
Jako nowy użytkownik chcę wybrać swoje kategorie zainteresowań turystycznych (multi-select), aby AI generowało plany dopasowane do moich preferencji.

Kryteria akceptacji:
- Ekran wyświetla 7 kategorii zainteresowań z ikonami: Historia i kultura, Przyroda i outdoor, Gastronomia, Nocne życie i rozrywka, Plaże i relaks, Sporty i aktywności, Sztuka i muzea
- Użytkownik może wybrać wiele kategorii (multi-select)
- Minimum jedna kategoria musi być wybrana
- Przyciski "Wstecz" i "Dalej" umożliwiają nawigację
- Po kliknięciu "Dalej" użytkownik przechodzi do parametrów praktycznych
- Wybrane kategorie są zapisywane w profilu

US-008: Proces onboardingu - parametry praktyczne
Jako nowy użytkownik chcę ustawić parametry praktyczne (tempo, budżet, transport, ograniczenia), aby AI uwzględniało moje potrzeby przy planowaniu.

Kryteria akceptacji:
- Ekran wyświetla 4 parametry do wyboru: Tempo podróży (Spokojne/Umiarkowane/Intensywne), Budżet (Ekonomiczny/Standardowy/Premium), Transport (Pieszo i transport publiczny/Wynajem auta/Mix), Ograniczenia (Brak/Dieta/Mobilność)
- Dla każdego parametru użytkownik wybiera jedną opcję (single select)
- Wszystkie parametry są wymagane
- Przyciski "Wstecz" i "Zakończ" umożliwiają nawigację
- Po kliknięciu "Zakończ" użytkownik jest przekierowywany do dashboard
- Parametry są zapisywane w profilu
- Wysyłany jest welcome email
- Onboarding jest oznaczany jako ukończony

US-009: Wylogowanie z aplikacji
Jako zalogowany użytkownik chcę się wylogować z aplikacji, aby zakończyć sesję i zabezpieczyć swoje konto.

Kryteria akceptacji:
- W sidebar/topbar znajduje się przycisk "Wyloguj"
- Po kliknięciu sesja użytkownika jest usuwana
- Użytkownik jest przekierowywany do strony logowania
- Próba dostępu do chronionych stron wymaga ponownego logowania

US-010: Usunięcie konta
Jako użytkownik chcę mieć możliwość usunięcia swojego konta, aby trwale usunąć swoje dane z systemu.

Kryteria akceptacji:
- W ustawieniach profilu znajduje się opcja "Usuń konto"
- Po kliknięciu wyświetlane jest ostrzeżenie o trwałym usunięciu danych
- Użytkownik musi potwierdzić akcję
- Po potwierdzeniu wszystkie dane użytkownika są usuwane (hard delete): profil, plany, feedback, metadata AI
- Użytkownik jest wylogowywany i przekierowywany do strony głównej
- Operacja jest nieodwracalna

### 5.2 Profil użytkownika

US-011: Wyświetlanie profilu użytkownika
Jako zalogowany użytkownik chcę zobaczyć swój profil z danymi podstawowymi i preferencjami, aby sprawdzić moje aktualne ustawienia.

Kryteria akceptacji:
- Dostęp do profilu z sidebar/topbar dashboard
- Profil wyświetla: nick, email (z oznaczeniem weryfikacji), kraj/miasto domowe
- Profil wyświetla wybrane kategorie zainteresowań
- Profil wyświetla ustawione parametry praktyczne
- Interfejs jest czytelny i dobrze zorganizowany

US-012: Edycja danych profilu
Jako użytkownik chcę edytować swoje dane podstawowe (nick, miasto domowe), aby aktualizować informacje o sobie.

Kryteria akceptacji:
- W profilu znajduje się przycisk "Edytuj profil"
- Formularz edycji zawiera pola: nick, kraj/miasto domowe (email nie jest edytowalny)
- Walidacja pól analogiczna jak w onboardingu
- Przycisk "Zapisz" zapisuje zmiany
- Przycisk "Anuluj" cofa do widoku profilu bez zapisywania
- Po zapisaniu wyświetlany jest komunikat potwierdzający

US-013: Edycja preferencji turystycznych
Jako użytkownik chcę edytować swoje preferencje turystyczne (kategorie i parametry), aby dostosować generowane plany do zmieniających się zainteresowań.

Kryteria akceptacji:
- W profilu znajduje się sekcja "Preferencje turystyczne" z przyciskiem "Edytuj"
- Formularz edycji zawiera te same kategorie i parametry co w onboardingu
- Można zmienić wybrane kategorie zainteresowań (multi-select)
- Można zmienić parametry praktyczne (single select dla każdego)
- Przycisk "Zapisz" zapisuje zmiany
- Przycisk "Anuluj" cofa do widoku profilu
- Zmiany są uwzględniane przy kolejnych generowaniach planów

### 5.3 Dashboard i nawigacja

US-014: Wyświetlanie głównego dashboard
Jako zalogowany użytkownik chcę zobaczyć dashboard z moimi planami i możliwością utworzenia nowego, aby mieć centralny punkt zarządzania wycieczkami.

Kryteria akceptacji:
- Dashboard zawiera hero section z personalizowanym powitaniem "Cześć [Nick]! Zaplanuj swoją kolejną przygodę"
- Widoczny jest główny przycisk CTA "Stwórz nowy plan"
- Wyświetlana jest lista wszystkich planów użytkownika
- Dla każdego planu widoczne są: tytuł, destynacja, daty, status
- Plany są sortowane od najnowszych do najstarszych
- Dashboard jest dostępny jako strona główna po zalogowaniu

US-015: Filtrowanie planów według statusu
Jako użytkownik chcę filtrować swoje plany według statusu (wszystkie/szkice/zaplanowane/zrealizowane), aby łatwo znaleźć interesujące mnie wycieczki.

Kryteria akceptacji:
- Nad listą planów znajdują się quick filters: Wszystkie, Szkice, Zaplanowane, Zrealizowane
- Domyślnie wybrany jest filtr "Wszystkie"
- Po wybraniu filtra lista aktualizuje się, pokazując tylko plany o danym statusie
- Liczba wyświetlanych planów odpowiada zastosowanemu filtrowi
- Aktywny filtr jest wizualnie wyróżniony

US-016: Wyświetlanie licznika limitów generowań
Jako użytkownik chcę widzieć ile generowań AI pozostało mi w bieżącym miesiącu, aby planować wykorzystanie limitu.

Kryteria akceptacji:
- W sidebar/topbar dashboard wyświetlany jest licznik "X/10 w tym miesiącu"
- Licznik aktualizuje się po każdym użyciu generowania
- Po osiągnięciu limitu (10/10) licznik jest wizualnie wyróżniony
- Licznik resetuje się pierwszego dnia każdego miesiąca kalendarzowego

US-017: Nawigacja do profilu i ustawień
Jako użytkownik chcę mieć łatwy dostęp do profilu i ustawień z każdej strony aplikacji, aby szybko zarządzać swoim kontem.

Kryteria akceptacji:
- W sidebar lub topbar znajdują się linki: "Profil", "Ustawienia", "Wyloguj"
- Kliknięcie "Profil" przekierowuje do strony profilu
- Kliknięcie "Ustawienia" przekierowuje do ustawień
- Linki są dostępne ze wszystkich stron aplikacji po zalogowaniu

### 5.4 Tworzenie i zarządzanie planami

US-018: Utworzenie nowego planu/notatki
Jako użytkownik chcę utworzyć nowy plan wycieczki poprzez wypełnienie formularza z podstawowymi informacjami, aby rozpocząć proces planowania.

Kryteria akceptacji:
- Kliknięcie "Stwórz nowy plan" otwiera formularz
- Formularz zawiera pola: Tytuł planu (required), Destynacja (required), Data wyjazdu (required, date picker), Liczba dni (required, 1-30), Liczba osób (required, 1-10), Szacunkowy budżet na osobę (optional, z walutą), Twoje pomysły i notatki (optional, textarea)
- Wszystkie wymagane pola są walidowane
- Formularz zawiera dwa przyciski: "Generuj plan" (primary) i "Zapisz jako szkic" (secondary)
- Date picker nie pozwala wybrać daty z przeszłości
- Number inputy mają odpowiednie min/max constraints

US-019: Zapisanie planu jako szkic
Jako użytkownik chcę zapisać plan jako szkic bez generowania AI, aby dokończyć planowanie później lub zaoszczędzić limit generowań.

Kryteria akceptacji:
- Przycisk "Zapisz jako szkic" zapisuje dane z formularza
- Plan otrzymuje status "szkic"
- Użytkownik jest przekierowywany do dashboard
- Szkic pojawia się na liście planów z odpowiednim statusem
- Zapisywane są metadane: created_at, updated_at
- Wyświetlany jest komunikat potwierdzający zapisanie

US-020: Wyświetlenie szczegółów planu (szkic)
Jako użytkownik chcę zobaczyć szczegóły zapisanego szkicu planu, aby sprawdzić lub edytować wprowadzone informacje.

Kryteria akceptacji:
- Kliknięcie na szkic na liście otwiera widok szczegółów
- Wyświetlane są wszystkie zapisane informacje: tytuł, destynacja, daty, liczba dni/osób, budżet, notatki
- Dostępny jest przycisk "Generuj plan" (przekierowanie do generowania AI)
- Dostępny jest przycisk "Usuń plan"
- Brak możliwości bezpośredniej edycji w MVP (trzeba usunąć i utworzyć od nowa)

US-021: Usunięcie planu
Jako użytkownik chcę usunąć plan (szkic lub wygenerowany), aby pozbyć się niepotrzebnych wycieczkowych pomysłów.

Kryteria akceptacji:
- W widoku szczegółów planu znajduje się przycisk "Usuń plan"
- Po kliknięciu wyświetlane jest potwierdzenie akcji
- Po potwierdzeniu plan jest trwale usuwany z bazy danych
- Użytkownik jest przekierowywany do dashboard
- Plan znika z listy planów
- Usunięcie planu nie przywraca wykorzystanego limitu generowań

### 5.5 Generowanie planów AI

US-022: Generowanie planu z formularza
Jako użytkownik chcę wygenerować szczegółowy plan wycieczki z moich notatek jednym kliknięciem, aby otrzymać spersonalizowany harmonogram zwiedzania.

Kryteria akceptacji:
- Przycisk "Generuj plan" w formularzu tworzenia planu uruchamia proces generowania
- System sprawdza dostępność limitu (czy nie przekroczono 10/10)
- Wyświetlany jest loading state z komunikatem "Generuję plan..."
- Dane z formularza + preferencje użytkownika są wysyłane do AI (GPT-4 lub Claude)
- Po otrzymaniu odpowiedzi plan jest zapisywany w bazie
- Status planu zmienia się na "zaplanowane"
- Licznik limitów jest aktualizowany (X+1/10)
- Użytkownik jest przekierowywany do widoku wygenerowanego planu
- Zapisywane są metadane AI: tokens used, cost, timestamp

US-023: Blokada generowania przy wyczerpaniu limitu
Jako użytkownik, który wykorzystał wszystkie generowania w miesiącu, chcę otrzymać jasny komunikat o wyczerpaniu limitu, aby zrozumieć dlaczego nie mogę wygenerować planu.

Kryteria akceptacji:
- Gdy limit osiągnął 10/10, przycisk "Generuj plan" jest nieaktywny (disabled)
- Po kliknięciu wyświetlany jest komunikat: "Osiągnąłeś limit 10 generowań w tym miesiącu. Limit odnowi się 1. [nazwa następnego miesiąca]."
- Komunikat zawiera informację o możliwości dołączenia do waitlist na premium (bez płatności w MVP)
- Użytkownik może nadal zapisywać szkice

US-024: Obsługa błędów podczas generowania AI
Jako użytkownik chcę otrzymać zrozumiały komunikat, gdy generowanie planu AI nie powiedzie się, aby wiedzieć co zrobić dalej.

Kryteria akceptacji:
- Przy timeout API wyświetlany jest komunikat: "Generowanie trwa zbyt długo. Spróbuj ponownie."
- Przy błędzie API wyświetlany jest komunikat: "Wystąpił problem z generowaniem planu. Spróbuj ponownie."
- Przy niekompletnej odpowiedzi AI wyświetlany jest komunikat: "Nie udało się wygenerować pełnego planu. Spróbuj ponownie."
- Nieudane generowanie NIE zużywa limitu (rollback)
- Użytkownik może spróbować ponownie bez ponownego wypełniania formularza

US-025: Wyświetlenie wygenerowanego planu
Jako użytkownik chcę zobaczyć wygenerowany plan wycieczki w przejrzystej strukturze dzień po dniu, aby poznać szczegóły proponowanego zwiedzania.

Kryteria akceptacji:
- Widok planu zawiera header z podstawowymi informacjami: tytuł, destynacja, daty (od-do), liczba osób, budżet
- Sekcja "Twoje założenia" (collapsed by default) zawiera oryginalne notatki i preferencje
- Plan dzień po dniu wyświetlany w formie kart dla każdego dnia
- Każdy dzień ma nagłówek "Dzień X - DD.MM.YYYY"
- W każdym dniu punkty podzielone na pory: rano, południe, popołudnie, wieczór
- Każdy punkt zawiera: nazwę, opis (2-3 zdania), uzasadnienie, czas wizyty, link Google Maps (klikalny)
- Footer zawiera: formularz feedbacku, przycisk "Export do PDF", przycisk "Regeneruj plan"

US-026: Regeneracja planu
Jako użytkownik chcę móc zregenerować plan, jeśli obecny mnie nie satysfakcjonuje, aby otrzymać lepszy wynik.

Kryteria akceptacji:
- W widoku wygenerowanego planu znajduje się przycisk "Regeneruj plan"
- Po kliknięciu wyświetlany jest warning: "Spowoduje to wygenerowanie nowego planu (X/10 w tym miesiącu). Kontynuować?"
- Użytkownik musi potwierdzić akcję
- Po potwierdzeniu następuje proces generowania analogiczny do pierwszego
- Regeneracja zużywa dodatkowe generowanie z limitu
- Poprzedni plan jest nadpisywany nowym (brak wersjonowania w MVP)
- System sprawdza dostępność limitu przed regeneracją
- Jeśli limit wyczerpany (10/10), regeneracja jest niemożliwa

### 5.6 Feedback i eksport

US-027: Udzielenie feedbacku o planie
Jako użytkownik chcę dać feedback o jakości wygenerowanego planu, aby pomóc w ulepszeniu systemu.

Kryteria akceptacji:
- W footer wygenerowanego planu znajduje się formularz feedbacku
- Pytanie: "Czy plan spełnia Twoje oczekiwania?" z przyciskami "Tak" / "Nie"
- Przy wyborze "Nie" pojawiają się checkboxy: "Za mało szczegółów", "Nie pasuje do moich preferencji", "Słaba kolejność zwiedzania", "Inne" (z opcjonalnym polem tekstowym)
- Użytkownik może wybrać wiele checkboxów
- Przycisk "Wyślij feedback" zapisuje odpowiedzi w bazie
- Feedback jest opcjonalny (możliwość pominięcia)
- Po wysłaniu wyświetlany jest komunikat potwierdzający

US-028: Eksport planu do PDF
Jako użytkownik chcę wyeksportować plan do PDF, aby móc udostępnić go znajomym lub mieć dostęp offline.

Kryteria akceptacji:
- W footer wygenerowanego planu znajduje się przycisk "Export do PDF"
- Po kliknięciu PDF jest generowany server-side
- PDF zawiera: tytuł planu, destynację i daty, pełen plan dzień po dniu z opisami, tekstowe URL-e do Google Maps, watermark "Generated by VibeTravels"
- Użytkownik otrzymuje plik PDF do pobrania
- Nazwa pliku: "[Tytuł planu]_[Destynacja].pdf"
- PDF jest czytelny i dobrze sformatowany
- Eksport jest trackowany w bazie (liczba eksportów dla danego planu)

### 5.7 Powiadomienia email

US-029: Otrzymanie emaila weryfikacyjnego
Jako nowy użytkownik chcę otrzymać email weryfikacyjny zaraz po rejestracji, aby potwierdzić swój adres email.

Kryteria akceptacji:
- Email jest wysyłany automatycznie po rejestracji (email+hasło)
- Email zawiera unikalny link weryfikacyjny ważny 24 godziny
- Temat emaila: "Zweryfikuj swój adres email w VibeTravels"
- Email zawiera jasne instrukcje i CTA button
- Po kliknięciu w link użytkownik jest przekierowywany do aplikacji
- Wyświetlany jest komunikat: "Email zweryfikowany pomyślnie"

US-030: Ponowne wysłanie emaila weryfikacyjnego
Jako użytkownik z niezweryfikowanym emailem chcę móc poprosić o ponowne wysłanie linku weryfikacyjnego, jeśli nie otrzymałem lub link wygasł.

Kryteria akceptacji:
- W aplikacji wyświetlany jest banner dla niezweryfikowanych użytkowników: "Twój email nie jest zweryfikowany"
- Banner zawiera link "Wyślij ponownie email weryfikacyjny"
- Po kliknięciu nowy email jest wysyłany
- Wyświetlany jest komunikat: "Email weryfikacyjny został wysłany ponownie"
- Rate limiting: maksymalnie 1 email na 5 minut

US-031: Otrzymanie welcome emaila
Jako użytkownik, który ukończył onboarding, chcę otrzymać welcome email z podstawowymi wskazówkami, aby dowiedzieć się jak najlepiej korzystać z aplikacji.

Kryteria akceptacji:
- Email jest wysyłany automatycznie po ukończeniu onboardingu
- Temat: "Witaj w VibeTravels! Zacznij planować swoją przygodę"
- Email zawiera: powitanie z imieniem/nickiem, podstawowe tips (jak utworzyć plan, jak wykorzystać limity), CTA do dashboard
- Email ma przyjazny, zachęcający ton

US-032: Otrzymanie powiadomienia o zbliżającym się limicie
Jako użytkownik, który wykorzystał 8 z 10 generowań, chcę otrzymać email przypominający o pozostałych generowaniach, aby mądrze je wykorzystać.

Kryteria akceptacji:
- Email jest wysyłany automatycznie po wykorzystaniu 8. generowania
- Temat: "Pozostały Ci 2 generowania w tym miesiącu"
- Email zawiera: informację o wykorzystanych generowaniach (8/10), przypomnienie o dacie resetu (1. następnego miesiąca), zachętę do stworzenia kolejnych planów
- Email jest wysyłany tylko raz w miesiącu (przy pierwszym osiągnięciu 8/10)

US-033: Otrzymanie powiadomienia o wyczerpaniu limitu
Jako użytkownik, który wykorzystał wszystkie 10 generowań, chcę otrzymać email informujący o wyczerpaniu limitu i dacie odnowienia, aby wiedzieć kiedy znów będę mógł generować plany.

Kryteria akceptacji:
- Email jest wysyłany automatycznie po wykorzystaniu 10. generowania
- Temat: "Wykorzystałeś limit generowań w tym miesiącu"
- Email zawiera: informację o wykorzystaniu limitu, datę odnowienia (1. następnego miesiąca), opcjonalnie informację o waitlist na premium
- Email jest wysyłany tylko raz w miesiącu

US-034: Otrzymanie przypomnienia przed wycieczką (opcjonalne)
Jako użytkownik z zaplanowaną wycieczką chcę otrzymać przypomnienie 3 dni przed datą wyjazdu, aby przypomnieć sobie szczegóły planu.

Kryteria akceptacji:
- Email jest wysyłany automatycznie 3 dni przed datą wyjazdu
- Temat: "Twoja wycieczka do [Destynacja] już za 3 dni!"
- Email zawiera: tytuł planu, destynację, daty, link do pełnego planu w aplikacji, zachętę do pobrania PDF
- Email jest wysyłany tylko dla planów o statusie "zaplanowane"
- Funkcja opcjonalna do zaimplementowania w MVP (nice to have)

### 5.8 Analytics i metryki

US-035: Tracking wypełnienia profilu
Jako admin/product owner chcę widzieć procent użytkowników z w pełni wypełnionymi preferencjami, aby mierzyć sukces onboardingu.

Kryteria akceptacji:
- System trackuje czy użytkownik wypełnił: nick, miasto domowe, kategorie zainteresowań (min. 1), wszystkie 4 parametry praktyczne
- Dashboard analytics pokazuje: % użytkowników z w pełni wypełnionym profilem, liczba użytkowników z wypełnionym profilem / łączna liczba użytkowników
- Metryka aktualizuje się w czasie rzeczywistym

US-036: Tracking liczby planów per użytkownik
Jako admin/product owner chcę widzieć ile planów generuje każdy użytkownik rocznie, aby mierzyć engagement.

Kryteria akceptacji:
- System trackuje liczbę unikalnych, wygenerowanych planów per użytkownik
- Nie liczą się: surowe szkice (bez generowania AI), regeneracje tego samego planu
- Dashboard analytics pokazuje: średnią liczbę planów per użytkownik, % użytkowników z ≥3 planami w ciągu ostatnich 12 miesięcy, rozkład liczby planów (histogram)

US-037: Tracking satisfaction rate
Jako admin/product owner chcę widzieć procent pozytywnych feedbacków, aby ocenić jakość generowanych planów.

Kryteria akceptacji:
- System zlicza odpowiedzi "Tak" i "Nie" z formularza feedbacku
- Dashboard analytics pokazuje: % pozytywnych feedbacków (Tak / wszystkie), liczba feedbacków "Nie" z podziałem na kategorie problemów
- Analiza trendów w czasie (czy satisfaction rośnie/spada)

US-038: Tracking export rate
Jako admin/product owner chcę wiedzieć ile procent planów jest eksportowanych do PDF, aby ocenić użyteczność funkcji.

Kryteria akceptacji:
- System trackuje liczbę eksportów per plan
- Dashboard analytics pokazuje: % planów eksportowanych przynajmniej raz, średnią liczbę eksportów per plan, łączną liczbę eksportów
- Możliwość filtrowania po okresie czasu

US-039: Tracking kosztów AI
Jako admin/product owner chcę monitorować koszty generowania AI, aby kontrolować budżet i planować skalowanie.

Kryteria akceptacji:
- System zapisuje metadata dla każdego generowania: tokens used, cost (w USD), timestamp
- Dashboard analytics pokazuje: łączny koszt generowań (dzienny/miesięczny/całkowity), średni koszt na plan, liczba generowań dziennie/miesięcznie, rozkład kosztów w czasie
- Możliwość ustawienia alertów przy przekroczeniu budżetu

US-040: Tracking retention i active users
Jako admin/product owner chcę wiedzieć ile użytkowników wraca do aplikacji, aby ocenić retention.

Kryteria akceptacji:
- System trackuje logowania użytkowników z timestampem
- Dashboard analytics pokazuje: Monthly Active Users (MAU) - użytkownicy z logowaniem w ostatnim miesiącu, 30-day retention - % użytkowników, którzy wrócili po 30 dniach od rejestracji, kohortowa analiza retention
- Możliwość filtrowania po kohortach rejestracji

## 6. Metryki sukcesu

### 6.1 Cele biznesowe (primary metrics)

Metryka 1: Engagement użytkowników z preferencjami
- Cel: 90% użytkowników posiada wypełnione preferencje turystyczne w profilu
- Sposób mierzenia:
  - Tracking completion rate onboardingu
  - % użytkowników z wypełnionymi wszystkimi polami preferencji (kategorie + parametry)
  - Dashboard analytics pokazujący completion rate
- Definicja "wypełnione preferencje":
  - Nick i miasto domowe wprowadzone
  - Minimum jedna kategoria zainteresowań wybrana
  - Wszystkie 4 parametry praktyczne ustawione
- Timeline: Pomiar po 3 miesiącach od launch

Metryka 2: Aktywność generowania planów
- Cel: 75% użytkowników generuje 3 lub więcej planów na rok
- Sposób mierzenia:
  - Tracking liczby zapisanych planów per użytkownik
  - Kohortowa analiza: % użytkowników z ≥3 planami w ciągu 12 miesięcy
  - Wyłączenie z liczenia: surowe szkice (tylko unikalne, wygenerowane AI plany)
  - Regeneracje tego samego planu liczą się jako jeden plan
- Timeline: Pomiar kwartalny z projekcją roczną po 6 miesiącach

### 6.2 Metryki operacyjne MVP (supporting metrics)

Metryka 3: Onboarding completion rate
- Cel: >80% użytkowników kończy proces onboardingu
- Definicja: % użytkowników, którzy po rejestracji ukończyli wszystkie kroki onboardingu (dane podstawowe → kategorie → parametry)
- Drop-off tracking: Śledzenie na którym kroku użytkownicy rezygnują
- Znaczenie: Wysoki completion rate zapewnia jakość danych dla AI

Metryka 4: Plan satisfaction rate
- Cel: >70% pozytywnych feedbacków
- Definicja: % odpowiedzi "Tak" na pytanie "Czy plan spełnia Twoje oczekiwania?"
- Analiza negatywnych feedbacków: Podział na kategorie problemów (za mało szczegółów, nie pasuje do preferencji, słaba kolejność, inne)
- Znaczenie: Wskaźnik jakości generowanych planów AI

Metryka 5: Export rate
- Cel: >40% wygenerowanych planów jest eksportowanych do PDF
- Definicja: % planów, które zostały wyeksportowane przynajmniej raz
- Znaczenie: Wskaźnik użyteczności i praktycznej wartości planów

Metryka 6: Monthly active users
- Cel: >60% zarejestrowanych użytkowników loguje się minimum raz w miesiącu
- Definicja: MAU / Total registered users
- Znaczenie: Wskaźnik zaangażowania i wartości produktu

Metryka 7: 30-day retention
- Cel: >50% użytkowników wraca po 30 dniach
- Definicja: % użytkowników, którzy zalogowali się ponownie między 28. a 32. dniem od rejestracji
- Znaczenie: Wczesny wskaźnik product-market fit

### 6.3 Metryki techniczne

Metryka 8: AI generation success rate
- Cel: >95% generowań kończy się sukcesem (bez błędów)
- Tracking: Liczba udanych generowań / wszystkie próby
- Błędy do śledzenia: timeout, API errors, incomplete responses
- Znaczenie: Wskaźnik niezawodności systemu

Metryka 9: Średni czas generowania planu
- Cel: <30 sekund od kliknięcia "Generuj" do wyświetlenia planu
- Tracking: Timestamp rozpoczęcia i zakończenia generowania
- Znaczenie: User experience i koszty infrastruktury

Metryka 10: Średni koszt na plan
- Cel: $0.10-$0.50 USD na plan (zgodnie z założeniami)
- Tracking: Suma kosztów AI / liczba wygenerowanych planów
- Monitoring: Alert przy przekroczeniu $0.60 średniego kosztu
- Znaczenie: Kontrola kosztów operacyjnych i przyszłe pricing

### 6.4 Metodologia mierzenia

Implementacja analytics:
- Narzędzie: Google Analytics, Plausible lub własne rozwiązanie (do ustalenia)
- Dashboard wewnętrzny dla product team z kluczowymi metrykami
- Real-time tracking dla metryk operacyjnych
- Tygodniowe raporty dla metryk biznesowych

Database tracking:
- Wszystkie kluczowe akcje zapisywane jako eventy z timestampem
- Tabele analytics: user_events, plan_events, ai_generation_metadata, feedback_data
- ETL process dla agregacji metryk dziennych/tygodniowych/miesięcznych

Privacy compliance:
- Tracking zgodny z GDPR
- Brak identyfikowalnych danych osobowych w analytics poza systemem
- Anonimizacja danych przy eksporcie

### 6.5 Kryteria sukcesu MVP (launch criteria)

Przed wyjściem z MVP do public beta:
1. Onboarding completion rate >70% (w testach beta)
2. AI generation success rate >90%
3. Średni czas generowania <45 sekund
4. Plan satisfaction rate >60% (w testach beta)
5. Zero critical security vulnerabilities
6. Podstawowy monitoring i error tracking działają poprawnie
7. Email delivery rate >95%

Kryteria sukcesu po 3 miesiącach:
1. 100-500 zarejestrowanych użytkowników
2. Metryka 1 (preferencje): >80% (cel 90%)
3. 30-day retention: >40% (cel 50%)
4. Plan satisfaction rate: >65% (cel 70%)
5. MAU: >50% (cel 60%)

Decyzja o kontynuacji/pivotowaniu:
- Jeśli po 6 miesiącach nie osiągnięto minimum 70% celu dla Metryki 1 i 2: analiza przyczyn i potencjalny pivot
- Jeśli po 6 miesiącach osiągnięto 80%+ celów dla obu metryk biznesowych: rozwój MVP w stronę płatnej wersji i dodatkowych funkcji
