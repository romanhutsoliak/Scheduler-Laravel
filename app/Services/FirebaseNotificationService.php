<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FirebaseNotificationService
{
    protected GoogleClient $googleClient;

    protected string $firebaseProjectId;

    public function __construct()
    {
        $this->firebaseProjectId = config('services.firebase.project_id');

        $this->googleClient = new GoogleClient;
        $this->googleClient->setAuthConfig(Storage::path('firebase/service-account.json')); // Path to your service account JSON
        $this->googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');
    }

    /**
     * Get OAuth2 access token from Google Cloud.
     */
    protected function getAccessToken()
    {
        $token = $this->googleClient->fetchAccessTokenWithAssertion();

        return $token['access_token'] ?? null;
    }

    public function sendNotification(string $deviceToken, string $title, string $body, array $data = [])
    {
        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            throw new \Exception('Failed to retrieve access token.');
        }

        $response = Http::baseUrl('https://fcm.googleapis.com/')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $accessToken",
            ])->post("v1/projects/{$this->firebaseProjectId}/messages:send", [
                // https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages/send
                'message' => [
                    'token' => $deviceToken,
                    'android' => [
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'priority' => 'HIGH',
                        'data' => $data,
                    ],
                    // IOS settings
                    //                    'apns' => [],
                ],
            ]);

        return $response->json();
    }
}
