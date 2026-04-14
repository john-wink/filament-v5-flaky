<?php

declare(strict_types=1);

/**
 * Test-Bootstrap fuer Parallel-Modus.
 *
 * Setzt VIEW_COMPILED_PATH und APP_SERVICES_CACHE pro ParaTest-Worker
 * BEVOR die Laravel-App booted. Ohne das schreiben mehrere Worker
 * dieselben compiled-blade-views / service-cache Dateien -> partial-write
 * race -> 'Invalid Livewire snapshot structure', 'getDefaultTestingSchemaName()
 * on null', etc.
 *
 * In der echten ys-consulting Codebase reduziert dieser Bootstrap die
 * Failure-Rate von ~17 failures/run auf ~1-3 sporadic failures/run.
 * Vollstaendig 100%% gruen wird damit aber nicht erreicht — die letzten
 * race conditions stecken tiefer in Filament/Livewire internem State.
 */

require __DIR__.'/../vendor/autoload.php';

if ($token = getenv('TEST_TOKEN')) {
    $base = __DIR__.'/../storage/framework/testing/worker_'.$token;

    foreach (['views', 'cache', 'sessions'] as $sub) {
        $path = $base.'/'.$sub;
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    putenv('VIEW_COMPILED_PATH='.$base.'/views');
    $_ENV['VIEW_COMPILED_PATH'] = $base.'/views';
    $_SERVER['VIEW_COMPILED_PATH'] = $base.'/views';

    putenv('APP_SERVICES_CACHE='.$base.'/services.php');
    $_ENV['APP_SERVICES_CACHE'] = $base.'/services.php';
    $_SERVER['APP_SERVICES_CACHE'] = $base.'/services.php';

    putenv('APP_PACKAGES_CACHE='.$base.'/packages.php');
    $_ENV['APP_PACKAGES_CACHE'] = $base.'/packages.php';
    $_SERVER['APP_PACKAGES_CACHE'] = $base.'/packages.php';
}
