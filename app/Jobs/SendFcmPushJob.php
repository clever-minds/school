<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\CachingService;
use Google\Client;

class SendFcmPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $projectId;
    public $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($projectId, $payload)
    {
        $this->projectId = $projectId;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $cache = app(CachingService::class);
            $file = $cache->getSystemSettings('firebase_service_file');
            $file = explode("storage/", $file ?? '');
            $file = end($file);
            $path = base_path('public/storage/' . $file);

            $client = new Client();
            $client->setAuthConfig($path);
            $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
            
            $tokenData = $client->fetchAccessTokenWithAssertion();
            $accessToken = $tokenData['access_token'];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POSTFIELDS => json_encode($this->payload),
            ]);

            $response = curl_exec($ch);
            
            if(curl_errno($ch)) {
                Log::error('FCM Push Job cURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);

        } catch (\Exception $e) {
            Log::error('FCM Push Job Exception: ' . $e->getMessage());
        }
    }
}
