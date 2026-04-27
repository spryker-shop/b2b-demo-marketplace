<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework, string $env): void {
    $framework->secret('spryker-merchant-portal-secret');

    $framework->test(in_array($env, ['dockerdev', 'dockerci'], true));
};