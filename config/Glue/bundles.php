<?php

declare(strict_types = 1);

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Spryker\ApiPlatform\SprykerApiPlatformBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    FrameworkBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    ApiPlatformBundle::class => ['all' => true],
    SprykerApiPlatformBundle::class => ['all' => true],
];
