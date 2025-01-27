<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsHelper
{
    static function send($message, $number)
    {
        $number = preg_replace('/\D/', '', $number);

        if(mb_strlen($number) < 5) {
            Log::error("Number short " . $number);
            return false;
        }

        if (substr($number, 0, 3) == '375' || substr($number, 0, 1) == '7') {
            $curl = curl_init();

            $acc = env('ROCKET_SMS_ACC');
            $pass = env('ROCKET_SMS_PASS');
            $sender = env('ROCKET_SMS_SENDER');

            $message=str_replace('.com','.ru',$message);

            curl_setopt($curl, CURLOPT_URL, 'http://api.rocketsms.by/json/send');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, "username={$acc}&password={$pass}&phone=" . $number . "&text=" . $message . "&sender=$sender&priority=true");

            $result = @json_decode(curl_exec($curl), true);

            if ($result && isset($result['id'])) {
                return $result['id'];
            } elseif ($result && isset($result['error'])) {
                Log::error("Error occurred while sending message. ErrorID=" . $result['error']);
                return false;
            } else {
                Log::error("RocketSMS: service error");
                return false;
            }
        } else {
            try {
                $twilioAccountSid = getenv("TWILIO_ACCOUNT_SID");
                $twilioApiKey = getenv("TWILIO_API_KEY");
                $twilioApiSecret = getenv("TWILIO_AUTH_SECRET");

                $twilio = new Client($twilioApiKey, $twilioApiSecret, $twilioAccountSid);

                $twilio->messages
                    ->create("+" . $number,
                        [
                            "body" => __($message),
                            "from" => getenv("TWILIO_FROM_PHONE")
                        ]
                    );
            } catch (\Exception $error) {
                Log::error("Twillio number error" . $number);
            }


        }
    }
}
