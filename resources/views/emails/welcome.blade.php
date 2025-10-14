<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4F46E5;">Witaj w VibeTravels, {{ $user->nickname ?? $user->name }}! 👋</h1>

        <p>Cieszymy się, że do nas dołączyłeś! VibeTravels pomoże Ci zaplanować niezapomniane podróże dzięki sztucznej inteligencji.</p>

        <h2 style="color: #4F46E5;">Zacznij planować swoją przygodę</h2>

        <p><strong>Jak to działa?</strong></p>
        <ul>
            <li>Podaj miejsce i datę wyjazdu</li>
            <li>AI wygeneruje dla Ciebie spersonalizowany plan dnia po dniu</li>
            <li>Eksportuj plan do PDF i zabierz ze sobą w podróż</li>
        </ul>

        <p><strong>Twój limit:</strong> 10 generowań planów miesięcznie</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('dashboard') }}" style="background-color: #4F46E5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Stwórz swój pierwszy plan
            </a>
        </div>

        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            Miłej podróży!<br>
            Zespół VibeTravels
        </p>
    </div>
</body>
</html>
