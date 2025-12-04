<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Mailgun extends BaseConfig
{
    public $apiKey = '8845d1b1-0c88548c'; // Replace with your Mailgun API key
    public $domain = 'mg.pcdl4kids.com'; // Replace with your Mailgun domain
    public $endpoint = 'https://api.mailgun.net/v3'; // API endpoint
}
