<?php

namespace App\Services;

use Google\Client;

class GoogleClientFactory {
    public static function make(): Client {
        $client = new Client();
        $client->setApplicationName(config('app.name'));
        $client->setScopes([
            \Google\Service\Drive::DRIVE,
            \Google\Service\Sheets::SPREADSHEETS,
        ]);
        $client->setAuthConfig(config('services.google.credentials'));
        $client->setAccessType('offline');
        return $client;
    }
}
