<?php

namespace App\Http\Controllers;

use Google\Client;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    private function makeClient(): Client {
        $c = new Client();
        $c->setClientId(env('GOOGLE_OAUTH_CLIENT_ID'));
        $c->setClientSecret(env('GOOGLE_OAUTH_CLIENT_SECRET'));
        $c->setRedirectUri(env('GOOGLE_OAUTH_REDIRECT_URI'));
        $c->setAccessType('offline');
        $c->setPrompt('consent');
        $c->setScopes([
            \Google\Service\Drive::DRIVE,
            \Google\Service\Sheets::SPREADSHEETS,
        ]);
        return $c;
    }

    public function redirect() {
        $client = $this->makeClient();
        return redirect($client->createAuthUrl());
    }

    public function callback(Request $r) {
        $code = $r->query('code');
        abort_if(!$code, 400, 'Falta el code');

        $client = $this->makeClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) abort(500, 'Google OAuth error: '.$token['error']);

        @mkdir(storage_path('app/google'), 0777, true);
        file_put_contents(storage_path('app/google/token.json'), json_encode($token));

        return redirect()->route('home')->with('ok', 'Conectado a Google ✔');
    }

    public function logout() {
        @unlink(storage_path('app/google/token.json'));
        return redirect()->route('home')->with('ok', 'Desconectado de Google');
    }
}

