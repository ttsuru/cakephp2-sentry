# CakePHP2 Sentry Plugin

## Config

`Config/core.php`

```php
    Configure::write('Sentry', [
        'dsn' => 'SENTRY_DSN',
        'options', [
            'environment' => 'SENTRY_ENVIRONMENT',
            'release' => 'SENTRY_RELEASE',
        ],
        'ignoredExceptions', [
            NotFoundException::class,
            MissingControllerException::class,
            MissingActionException::class,
        ]
    ]);
    App::uses('SentryErrorHandler', 'Sentry.Lib/Error');
```

```php
    Configure::write('Error', [
        'handler' => 'SentryErrorHandler::handleError',
        'level' => E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED,
        'trace' => true,
    ]);
```

```php
    Configure::write('Exception', [
        'handler' => 'SentryErrorHandler::handleException',
        'renderer' => 'ExceptionRenderer',
        'log' => true,
    ]);
```
