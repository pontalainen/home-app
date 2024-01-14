Litet hemma-projekt där jag tänkte se om jag kan göra någon form av live-chatt. Återstår att se!

Installation:

 - PHP och Node skall vara installerat.
 - Klona projektet.
 - I roten på projektet, kör `composer install` och `npm install`.
 - Kopiera .env.example och döp om den till .env. I den fyller du i nycklarna från din Pusher Dashboard.
 - Kör `php artisan migrate` (svara ja på om du vill skapa upp sqlite-databasen) och i nytt terminal-fönster `npm run dev`.

 Besök `127.0.0.1:8000` och generera en nyckel. Efter detta, ladda om sidan.

 Detta borde vara allt, tror jag.