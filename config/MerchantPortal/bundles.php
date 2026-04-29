<?php

declare(strict_types = 1);

use KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

return [
    FrameworkBundle::class => ['all' => true],
    KnpUOAuth2ClientBundle::class => ['all' => true],
];
