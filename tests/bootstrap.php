<?php

declare(strict_types=1);

use App\Tests\DatabaseCachingUtils;
use App\Tests\TestBootstrapHelper;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$cacheFilePath = __DIR__ . TestBootstrapHelper::DATABASE_CACHE_FILE;
$currentDatabaseHash = DatabaseCachingUtils::calculateDirectoriesHash(
    __DIR__ . TestBootstrapHelper::MIGRATIONS_PATH,
    ...TestBootstrapHelper::getAllFixturesPaths(__DIR__),
);

if (!DatabaseCachingUtils::isCacheUpToDate($cacheFilePath, $currentDatabaseHash)) {
    $application = TestBootstrapHelper::createApplication();
    TestBootstrapHelper::databaseSetup($application);
    $application->getKernel()->shutdown();

    $filesystem = new Filesystem();
    $filesystem->dumpFile($cacheFilePath, $currentDatabaseHash);
}

// executes the "php bin/console cache:clear" command
passthru(
    sprintf(
        'APP_ENV=%s php "%s/../bin/console" cache:clear --no-warmup',
        $_ENV['APP_ENV'],
        __DIR__
    )
);

passthru(
    sprintf(
        'php "%s/../bin/console" doctrine:database:create --if-not-exists --env=test',
        __DIR__
    )
);

$schemaValidateResult = null;
passthru(
    sprintf(
        'php "%s/../bin/console" doctrine:schema:validate --env=test',
        __DIR__
    ),
    $schemaValidateResult
);
if (0 !== $schemaValidateResult) {
    passthru(
        sprintf(
            'php "%s/../bin/console" doctrine:schema:create --env=test',
            __DIR__
        )
    );
}

