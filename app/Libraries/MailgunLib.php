<?php

namespace App\Libraries;

use Config\Mailgun as MailgunConfig;

class MailgunLib
{
    protected $apiKey;
    protected $domain;
    protected $endpoint;

    public function __construct()
    {
        $config = new MailgunConfig();
        $this->apiKey = $config->apiKey;
        $this->domain = $config->domain;
        $this->endpoint = $config->endpoint;
    }

    public function sendEmail($to, $subject, $body, $from = null)
    {
        $from = $from ?? 'noreply@' . $this->domain; // Default from email
        $url = "{$this->endpoint}/{$this->domain}/messages";

        $postData = [
            'from'    => $from,
            'to'      => $to,
            'subject' => $subject,
            'html'    => $body, // HTML content
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Mailgun API Error: ' . $result);
        }

        return json_decode($result, true);
    }
}
