<?php

namespace App\Services;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Google_Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Client point 84: deleting an account from the customer app must also delete
 * its Firebase sign-in, or the email stays "already in use" for ever.
 *
 * Three things, for /api/delete-user:
 *   verifyIdToken()      who is asking - the signed-in user's Firebase ID
 *                        token (RS256, Google's securetoken certificates,
 *                        this project, not expired), as the admin's
 *                        App\Finance\IdTokens checks it
 *   deleteSignIn()       Firebase Authentication accounts:delete
 *   deleteProfile()      users/{uid} removed - the same hard delete the
 *                        admin makes since 30 Oct
 * Uses the service account in storage/app/firebase/credentials.json.
 */
class FirebaseAccounts
{
    private const CERTS = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    private $projectId;

    public function __construct($projectId = null)
    {
        $this->projectId = (string)($projectId ?: env('FIREBASE_PROJECT_ID'));
    }

    /** Returns the uid, or null when the token is missing, expired or not for this project. */
    public function verifyIdToken($idToken)
    {
        $idToken = (string)$idToken;
        if (substr_count($idToken, '.') !== 2) {
            return null;
        }
        $keys = [];
        foreach ($this->certs() as $kid => $pem) {
            $keys[$kid] = new Key($pem, 'RS256');
        }
        $previous = JWT::$leeway;
        JWT::$leeway = 60;
        try {
            $claims = (array)JWT::decode($idToken, $keys);
        } catch (ExpiredException $e) {
            return null;
        } catch (\Throwable $e) {
            return null;
        } finally {
            JWT::$leeway = $previous;
        }
        if (($claims['aud'] ?? null) !== $this->projectId
            || ($claims['iss'] ?? null) !== 'https://securetoken.google.com/' . $this->projectId
            || !is_string($claims['sub'] ?? null) || $claims['sub'] === '' || strlen($claims['sub']) > 128
            || (int)($claims['auth_time'] ?? PHP_INT_MAX) > time() + 60) {
            return null;
        }
        return $claims['sub'];
    }

    /** true, or 'missing' when there was no sign-in to delete. */
    public function deleteSignIn($uid)
    {
        $res = Http::withToken($this->accessToken())->timeout(15)->post(
            'https://identitytoolkit.googleapis.com/v1/projects/' . $this->projectId . '/accounts:delete',
            ['localId' => (string)$uid]);
        if ($res->successful()) {
            return true;
        }
        if (strpos((string)$res->json('error.message'), 'USER_NOT_FOUND') !== false) {
            return 'missing';
        }
        throw new \RuntimeException('Firebase Authentication refused the delete (' . $res->status() . ')');
    }

    /** Deletes a customer's profile. Returns false when there is none or it is not a customer's. */
    public function deleteProfile($uid)
    {
        $url = 'https://firestore.googleapis.com/v1/projects/' . $this->projectId . '/databases/(default)/documents/users/' . rawurlencode($uid);
        $res = Http::withToken($this->accessToken())->timeout(15)->get($url);
        if (!$res->successful()) {
            return false;
        }
        $fields = (array)$res->json('fields');
        if (($fields['role']['stringValue'] ?? '') !== 'customer') {
            return false;
        }
        $delete = Http::withToken($this->accessToken())->timeout(15)->delete($url);
        if (!$delete->successful()) {
            throw new \RuntimeException('The profile could not be deleted (' . $delete->status() . ')');
        }
        return true;
    }

    private function certs()
    {
        $cached = Cache::get('firebase_id_token_certs');
        if (is_array($cached) && $cached) {
            return $cached;
        }
        $res = Http::timeout(10)->get(self::CERTS);
        $certs = $res->successful() ? (array)$res->json() : [];
        if (!$certs) {
            throw new \RuntimeException('Google certificates unavailable');
        }
        preg_match('/max-age=(\d+)/', (string)$res->header('Cache-Control'), $m);
        Cache::put('firebase_id_token_certs', $certs, min(21600, max(60, (int)($m[1] ?? 3600))));
        return $certs;
    }

    private function accessToken()
    {
        return Cache::remember('firebase_admin_access_token', 3000, function () {
            $client = new Google_Client();
            $client->setAuthConfig(storage_path('app/firebase/credentials.json'));
            $client->addScope('https://www.googleapis.com/auth/cloud-platform');
            return $client->fetchAccessTokenWithAssertion()['access_token'] ?? '';
        });
    }
}
