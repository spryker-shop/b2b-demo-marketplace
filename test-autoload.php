<?php

/**
 * TEMPORARY WORKAROUND - remove once spryker/api-platform is released with the fix.
 *
 * spryker/api-platform declares its Codeception support namespaces in "autoload-dev" instead of
 * "autoload". Composer ignores a dependency's "autoload-dev", so nothing under
 * SprykerTest\\ApiPlatform\\* reaches vendor/composer/autoload_psr4.php and Codeception dies with
 * "could not be found and loaded" / "Class ... not found".
 *
 * Two roots are needed, mirroring the upstream fix:
 *   tests/SprykerTest/ApiPlatform/          -> Test\\, Test\\Security\\, DependencyInjection\\
 *   tests/SprykerTest/ApiPlatform/_support/ -> Helper\\, Fixture\\, ApiUnitTester
 */
spl_autoload_register(function (string $className): void {
    $prefix = 'SprykerTest\\ApiPlatform\\';

    if (!str_starts_with($className, $prefix)) {
        return;
    }

    $relativePath = str_replace('\\', '/', substr($className, strlen($prefix))) . '.php';
    $baseDir = __DIR__ . '/vendor/spryker/api-platform/tests/SprykerTest/ApiPlatform/';

    foreach ([$baseDir, $baseDir . '_support/'] as $directory) {
        if (file_exists($directory . $relativePath)) {
            require $directory . $relativePath;

            return;
        }
    }
});

$autoloader = function ($className) {
    $className = ltrim($className, '\\');
    $classNameParts = explode('\\', $className);

    $filePathPartsSupport = [];
    if ($classNameParts[0] === 'PyzTest') {
        array_shift($classNameParts);
        $application = array_shift($classNameParts);
        $bundle = array_shift($classNameParts);
        $className = implode(DIRECTORY_SEPARATOR, $classNameParts);
        $filePathPartsSupport = [
            __DIR__,
            'tests',
            'PyzTest',
            $application,
            $bundle,
            '_support',
            $className . '.php',
        ];
    }

    if ($classNameParts[0] === 'PhpStan') {
        array_shift($classNameParts);
        $className = implode(DIRECTORY_SEPARATOR, $classNameParts);
        $filePathPartsSupport = [
            __DIR__,
            'tests',
            'PhpStan',
            $className . '.php',
        ];
    }

    if ($filePathPartsSupport) {
        $filePath = implode(DIRECTORY_SEPARATOR, $filePathPartsSupport);
        if (file_exists($filePath)) {
            require $filePath;

            return true;
        }
    }

    return false;
};

spl_autoload_register($autoloader);
