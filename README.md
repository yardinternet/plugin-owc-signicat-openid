# OWC Signicat OpenID

Log into the Signicat Broker with OpenID.

## 🚨 In progress

This repository is not ready for production.

## Logging

Warnings are logged when WP_DEBUG is enabled.
The messages are logged using PHP's `error_log()` you can override this and use a custom logger:

```php
use CMDISP\MonologMicrosoftTeams\TeamsFormatter;
use CMDISP\MonologMicrosoftTeams\TeamsLogHandler;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Processor\PsrLogMessageProcessor;

add_action( 'owc_signicat_openid_compose', function( $plugin, $container ) {
  $logger = new Logger( 'microsoft-teams-logger' );
  $logger->pushHandler(
   new TeamsLogHandler( $_ENV( 'MS_TEAMS_WEBHOOK' ), LOGGER::WARNING, true,  new TeamsFormatter() )
  );
  $logger->pushProcessor( new PsrLogMessageProcessor );

  return $logger;
}, 10, 2 );
```
