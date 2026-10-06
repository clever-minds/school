<?php

use App\Models\User;
use App\Models\Students;
use App\Models\Notification;
use App\Models\NotificationUser;
use App\Services\CachingService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Google\Client;

function send_notification_all($notificationModel, $userIds, $type, $customData = [], $saveToDb = true)
{
    // ✅ Wrap context in array
    Log::info('Custom Data Received', ['customData' => $customData]);

    /* ===============================
     | SAFETY: SCHOOL ID
     ===============================*/
    $schoolId = $customData['school_id'] ?? null;

    if (!$schoolId) {
        Log::error('School ID missing in send_notification_all', [
            'customData' => $customData
        ]);
        return false;
    }

    /* ===============================
     | SESSION YEAR (SAFE)
     ===============================*/
    $cache = app(CachingService::class);
    $sessionYear = $cache->getDefaultSessionYear($schoolId);

    /* ===============================
     | 1️⃣ CREATE MASTER NOTIFICATION
     ===============================*/
    if ($saveToDb) {
        $notification = Notification::create([
            'school_id'       => $schoolId,
            'title'           => $notificationModel->title,
            'message'         => $notificationModel->message,
            'send_to'         => 'multiple',
            'event_date'      => $customData['event_date'] ?? null,
            'image'           => $customData['image'] ?? null,
            'session_year_id' => $sessionYear?->id,
        ]);
    } else {
        $notification = $notificationModel;
    }

    /* ===============================
     | 2️⃣ DUPLICATE PREVENT
     ===============================*/
    $processedReceivers = [];

    foreach ((array)$userIds as $uid) {

        $user = User::find((int)$uid);
        if (!$user) continue;

        $receiver   = null;
        $studentId  = null;
        $userRole   = null;
        $finalTitle = $notification->title;

        if ($user->hasRole('Student')) {

            $student = Students::where('user_id', $user->id)->first();
            if (!$student || !$student->guardian_id) continue;

            if (in_array($student->guardian_id, $processedReceivers)) continue;

            $receiver = User::find($student->guardian_id);
            if (!$receiver) continue;

            $processedReceivers[] = $receiver->id;

            $finalTitle .= ' - ' . trim($user->first_name . ' ' . $user->last_name);
            $studentId  = $student->id;
            $userRole   = 'Guardian';

        } else {

            if (in_array($user->id, $processedReceivers)) continue;

            $receiver = $user;
            $processedReceivers[] = $receiver->id;
            $userRole = $user->getRoleNames()->first();
        }

        /* ===============================
         | 3️⃣ SAVE notification_users
         ===============================*/
        if ($saveToDb) {
            NotificationUser::create([
                'notification_id' => $notification->id,
                'user_id'         => $receiver->id,
                'user_role'       => $userRole,
                'student_id'      => $studentId,
                'sent_at'         => now(),
            ]);
        }

        /* ===============================
         | 4️⃣ PUSH (FCM)
         ===============================*/
        if (!$receiver->fcm_id) continue;

        $projectId   = $cache->getSystemSettings('firebase_project_id');
        $payload = [
            "message" => [
                "token" => $receiver->fcm_id,
                "notification" => [
                    "title" => $finalTitle,
                    "body"  => $notification->message,
                ],
                "data" => [
                    "type"            => $type,
                    "notification_id" => (string)$notification->id,
                    "student_id"      => (string)$studentId,
                    "send_to"         => $userRole,
                ],
                "android" => [
                    "priority" => "high",
                    "notification" => [
                        "sound" => "default",
                        "click_action" => "FLUTTER_NOTIFICATION_CLICK"
                    ]
                ],
                "apns" => [
                    "headers" => [
                        "apns-priority" => "10"
                    ],
                    "payload" => [
                        "aps" => [
                            "sound" => "default",
                            "mutable-content" => 1,
                            "content-available" => 1
                        ]
                    ]
                ]
            ],
        ];

        \App\Jobs\SendFcmPushJob::dispatch($projectId, $payload);
    }

    return true;
}

/* =====================================
 | 5️⃣ FIREBASE ACCESS TOKEN
 =====================================*/
function getAccessToken_all()
{
    $cache = app(CachingService::class);

    $file = $cache->getSystemSettings('firebase_service_file');
    $file = explode("storage/", $file ?? '');
    $file = end($file);

    $path = base_path('public/storage/' . $file);

    $client = new Client();
    $client->setAuthConfig($path);
    $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);

    return $client->fetchAccessTokenWithAssertion()['access_token'];
}