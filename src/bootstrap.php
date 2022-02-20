<?php

declare(strict_types=1);

namespace App;

use Amp\Loop;
use Amp\Parallel\Worker;
use Amp\Parallel\Worker\BootstrapWorkerFactory;
use App\Parallel\SelfProcessWorker;

require __DIR__ . '/../vendor/autoload.php';

(static function (): void {
    static $executed = false;
    if ($executed) {
        return;
    }
    $executed = true;

    \ini_set('display_errors', '0');

    $xdebugActive = \extension_loaded('xdebug') && \xdebug_is_debugger_active();
    if ($xdebugActive) {
        \defined('AMP_CONTEXT') or \define('AMP_CONTEXT', 'process');
        \defined('AMP_WORKER') or \define('AMP_WORKER', AMP_CONTEXT);
    }

    $isWorker = \defined('AMP_WORKER');

    if ($isWorker) {
        $pool = new SelfProcessWorker();
        Worker\factory($pool);
        Worker\pool($pool);
    } else {
        Worker\factory(new BootstrapWorkerFactory(__FILE__));
    }

    // It's important to catch SIGINT / SIGTERM, so destructors can run
    $shutdownHandler = static function (string $watcherId, int $signal): void {
        static $shuttingDown = false;

        $signalName = \SIGINT === $signal ? 'SIGINT' : 'SIGTERM';

        if ($shuttingDown) {
            \fwrite(\STDERR, "Caught {$signalName} ({$signal}), but shutdown is already in progress, please wait...\n");
            return;
        }

        $shuttingDown = true;
        \fwrite(\STDERR, "Caught {$signalName} ({$signal}), stopping the event loop...\n");

        // Worker\pool()->kill();
        Loop::stop();

        /** Trigger shutdown functions. @see \register_shutdown_function() */
        exit(1);
    };
    Loop::onSignal(\SIGINT, $shutdownHandler);
    Loop::onSignal(\SIGTERM, $shutdownHandler);
})();
