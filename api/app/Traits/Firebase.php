<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

trait  Firebase
{
    use NotificationMessageTrait;

    public function sendNotification($iosTokens ,$androidTokens,$data = [],$lang ='ar'){
        // return false;
        $apiurl = 'https://fcm.googleapis.com/v1/projects/'.env('PROJECT_ID').'/messages:send';   //replace "your-project-id" with...your project ID

        $headers = [
            'Authorization: Bearer ' . $this->getToken(),
            'Content-Type: application/json'
        ];
        // you can modify this based on your needs
        $notification = [
            'title'             =>   $data['title_'.$lang],
            'body'              =>  $data['message_'.$lang],
        ];
        info($data['type']);
        $preparedData = $this->prepareData($data);
        $this->sendAndroidFcmNotifications($androidTokens, $preparedData,$apiurl, $headers,$notification,$lang);
        $this->sendIosFcmNotifications($iosTokens, $preparedData,$apiurl,$headers,$notification,$lang);
    }

    public function sendWebNotification($tokens , $data = [],$lang ='ar')
    {
        $apiurl = 'https://fcm.googleapis.com/v1/projects/'.env('PROJECT_ID').'/messages:send';   //replace "your-project-id" with...your project ID

        $headers = [
            'Authorization: Bearer ' . $this->getToken(),
            'Content-Type: application/json'
        ];
        // you can modify this based on your needs
        $notification = [
            'title'             =>   $data['title_'.$lang],
            'body'              =>  $data['message_'.$lang],
        ];
        $preparedData = $this->prepareData($data);
        $this->sendWebFcmNotifications($tokens, $preparedData,$apiurl, $headers,$notification,$lang);

    }

    private function prepareData($data){
        foreach($data as $key => $value){
            if(is_int($value)){
                $data[$key] = strval($value);
            } else if (is_bool($value)){
                $data[$key] = strval($value);
            } else if(is_array($value)){
                $data[$key] = json_encode($value);
            }
        }
        return $data;
    }

    private function sendAndroidFcmNotifications($tokens,$data,$url,$headers,$notification,$lang){

        foreach($tokens as $token){
            $message = $this->getAndroidMessageFormat($token,$data,$notification,$lang);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            $result = curl_exec($ch);
            if ($result === FALSE) {
                // Log and skip this token. Never die() here: killing the worker
                // mid-job leaves the job un-acked, so the database queue re-runs
                // it after retry_after and the user is notified twice.
                Log::warning('FCM curl failed: ' . curl_error($ch));
                curl_close($ch);
                continue;
            }
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            info('android');
            info($result);
            $this->pruneInvalidToken($token, $status, $result);
        }
    }

    /**
     * Delete a device whose FCM token FCM reports as no longer valid, so stale
     * tokens stop receiving (duplicate) pushes over time.
     */
    private function pruneInvalidToken($token, $status, $result): void
    {
        if ((int) $status === 404 || str_contains((string) $result, 'UNREGISTERED')) {
            \App\Models\Device::where('device_id', $token)->delete();
            Log::info('Pruned invalid FCM token', ['token' => $token]);
        }
    }

    private function getAndroidMessageFormat($token,$data,$notification,$lang){
        $message = [
            'token'   => $token,
            'android' => [
                'priority' => 'high',
            ],
            'data'    => $data,
        ];

        // Data-only mode: omit the notification block so the OS does not
        // auto-display a second notification on top of the app-local one.
        if (! config('fcm.data_only')) {
            $message['notification'] = $notification;
            $message['android']['notification'] = ['sound' => 'default'];
        }

        return ['message' => $message];
    }

    private function sendIosFcmNotifications($tokens,$data,$url,$headers,$notification,$lang){
        foreach($tokens as $token){
            $message = $this->getIosMessageFormat($token,$data,$notification,$lang);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            $result = curl_exec($ch);
            if ($result === FALSE) {
                // Log and skip this token. Never die() here: killing the worker
                // mid-job leaves the job un-acked, so the database queue re-runs
                // it after retry_after and the user is notified twice.
                Log::warning('FCM curl failed: ' . curl_error($ch));
                curl_close($ch);
                continue;
            }


            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            info('ios');
            info($result);
            $this->pruneInvalidToken($token, $status, $result);
            }

    }
    private function sendWebFcmNotifications($tokens,$data,$url,$headers,$notification,$lang){
        foreach($tokens as $token){
            $message = $this->getIosMessageFormat($token,$data,$notification,$lang);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            $result = curl_exec($ch);
            if ($result === FALSE) {
                // Log and skip this token. Never die() here: killing the worker
                // mid-job leaves the job un-acked, so the database queue re-runs
                // it after retry_after and the user is notified twice.
                Log::warning('FCM curl failed: ' . curl_error($ch));
                curl_close($ch);
                continue;
            }


            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            info('web');
            info($result);
            $this->pruneInvalidToken($token, $status, $result);
            }

    }

    private function getIosMessageFormat($token,$data,$notification,$lang){
        return [
            'message' => [
                'token'            => $token,
                'notification'     => $notification,
                'data'             => $data,
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10', // High priority for immediate delivery
                        'apns-push-type' => 'alert', // For alert notifications
                    ],
                    'payload' => [
                        'aps' => [
                            'mutable-content'=> 1,
                            'alert' => [
                                'title' =>  $data['title_'.$lang],
                                'body' =>   $data['message_'.$lang],
                            ],
                            'sound' => 'default',
                        ],
                    ],
                ],
                // 'sound'             => 'default',
            ],
        ];
    }


    private function getToken(){

        // Read private key from service account details
        $secret = openssl_get_privatekey(env('PRIVATE_KEY'));

        // $secret = openssl_get_privatekey(env('PRIVATE_KEY'));

        // Create the token header
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'RS256'
        ]);

        // Get seconds since 1 January 1970
        $time = time();

        $payload = json_encode([
            "iss" => env('CLIENT_EMAIL'),
            "scope" => "https://www.googleapis.com/auth/firebase.messaging",
            "aud" => "https://oauth2.googleapis.com/token",
            "exp" => $time + 3600,
            "iat" => $time
        ]);

        // Encode Header
        $base64UrlHeader = $this->base64UrlEncode($header);

        // Encode Payload
        $base64UrlPayload = $this->base64UrlEncode($payload);

        // Create Signature Hash
        $result = openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $secret, OPENSSL_ALGO_SHA256);

        // Encode Signature to Base64Url String
        $base64UrlSignature = $this->base64UrlEncode($signature);

        // Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        //-----Request token------
        $client = new Client();

        $response = $client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        $responseBody = json_decode($response->getBody());

        if (!isset($responseBody->access_token)) {
            throw new \Exception("Failed to get access token: " . json_encode($responseBody));
        }

        return $responseBody->access_token;
    }

    private function base64UrlEncode($text)
    {
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($text)
        );
    }
}

