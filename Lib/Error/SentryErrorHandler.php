<?php

use Sentry\SentrySdk;

App::uses('ErrorHandler', 'Error');

class SentryErrorHandler extends ErrorHandler
{
    /**
     * @var bool  Tracks if Sentry has been initialized
     */
    private static $initialized = false;

    /**
     * List of exception classes that should not be reported to Sentry.
     *
     * @var array
     */
    protected static $ignoredExceptions = [];

    /**
     * Initialize Sentry SDK and register PHP handlers.
     */
    public static function init()
    {
        if (self::$initialized) {
            return;
        }

        // Fetch DSN and other options from Configure
        $dsn = Configure::read('Sentry.dsn');
        $options = (array) Configure::read('Sentry.options', []);
        // Ensure DSN is passed
        $options['dsn'] = $dsn;

        // Initialize Sentry SDK with combined settings
        \Sentry\init($options);

        // Load ignored exceptions from configuration
        self::$ignoredExceptions = (array) Configure::read('Sentry.ignoredExceptions', []);

        // Register shutdown handler to catch fatal errors
        register_shutdown_function([__CLASS__, 'handleShutdown']);

        self::$initialized = true;
    }

    /**
     * Handle PHP runtime errors (warnings, notices, etc.).
     */
    public static function handleError($code, $description, $file = null, $line = null, $context = null): bool
    {
        self::init();

        // Delegate to CakePHP for logging/display
        parent::handleError($code, $description, $file, $line, $context);

        // Only report if error_reporting includes this level
        if (!(error_reporting() & $code)) {
            return true;
        }

        // Send to Sentry
        $exception = new ErrorException($description, 0, $code, $file, $line);
        SentrySdk::getCurrentHub()->captureException($exception);

        return true;
    }

    /**
     * Handle uncaught exceptions.
     */
    public static function handleException($exception): void
    {
        self::init();

        // Delegate to CakePHP
        parent::handleException($exception);

        // Skip reporting if exception is ignored
        if (self::shouldIgnore($exception)) {
            return;
        }

        // Send to Sentry
        SentrySdk::getCurrentHub()->captureException($exception);
    }

    /**
     * Handle fatal errors on shutdown (E_ERROR, E_PARSE, etc.).
     */
    public static function handleShutdown(): void
    {
        self::init();

        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            SentrySdk::getCurrentHub()->captureException($exception);
        }
    }

    /**
     * Check if an exception should be ignored.
     *
     * @param Exception|Throwable $exception
     *
     * @return bool
     */
    protected static function shouldIgnore($exception)
    {
        foreach (self::$ignoredExceptions as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
