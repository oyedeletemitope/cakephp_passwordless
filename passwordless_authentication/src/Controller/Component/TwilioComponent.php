<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Twilio\Rest\Client;
use Cake\Log\Log;
use Cake\Core\Configure;

class TwilioComponent extends Component
{
    private $client;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $sid = env('TWILIO_ACCOUNT_SID');
        $token = env('TWILIO_AUTH_TOKEN');

        if (!$sid || !$token) {
            Log::error('Twilio credentials are not set in the environment variables.');
            throw new \RuntimeException('Twilio credentials are not set in the environment variables.');
        }

        $this->client = new Client($sid, $token);
    }

    public function sendVerificationCode($to, $code)
    {
        try {
            if (!$this->client) {
                Log::error('Twilio client is not initialized.');
                throw new \RuntimeException('Twilio client is not initialized.');
            }

            $fromNumber = env('TWILIO_PHONE_NUMBER');
            if (!$fromNumber) {
                Log::error('TWILIO_PHONE_NUMBER is not set in the environment variables.');
                throw new \RuntimeException('TWILIO_PHONE_NUMBER is not set in the environment variables.');
            }

            Log::info("Attempting to send verification code to: $to");

            $message = $this->client->messages->create(
                $to,
                [
                    'from' => $fromNumber,
                    'body' => "Your verification code is: $code. It will expire in 30 minutes."
                ]
            );

            Log::info("Verification code sent successfully. SID: {$message->sid}");
            return true;
        } catch (\Twilio\Exceptions\RestException $e) {
            Log::error("Twilio RestException: {$e->getCode()} - {$e->getMessage()}");
            return false;
        } catch (\Exception $e) {
            Log::error("Unexpected error in TwilioComponent: {$e->getCode()} - {$e->getMessage()}");
            return false;
        }
    }
}
