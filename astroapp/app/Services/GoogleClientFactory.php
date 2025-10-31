<?php

namespace App\Services;

use Google\Client;

class GoogleClientFactory
{
    public static function make(): Client
    {
        $client = new Client();
        $client->setApplicationName(config('app.name'));

        // Scopes correctos (usar las clases de Google, no las tuyas)
        $client->setScopes([
            \Google\Service\Drive::DRIVE_FILE,
            \Google\Service\Sheets::SPREADSHEETS,
        ]);

        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setClientId(env('GOOGLE_OAUTH_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_OAUTH_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_OAUTH_REDIRECT_URI'));

        // Cargar token OAuth del usuario
        $tokenPath = storage_path('app/google/token.json');
        if (!file_exists($tokenPath)) {
            throw new \RuntimeException('GOOGLE_OAUTH_NOT_AUTHENTICATED');
        }

        $token = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                @mkdir(dirname($tokenPath), 0777, true);
                file_put_contents($tokenPath, json_encode($client->getAccessToken()));
            } else {
                throw new \RuntimeException('GOOGLE_OAUTH_NOT_AUTHENTICATED');
            }
        }

        return $client;
    }
}

