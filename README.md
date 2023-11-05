# OWC Signicat OpenID

Log into the Signicat Broker with OpenID.

## 🚨 In progress

This repository is not ready for production.

## Features

1. Provide a OpenID implementation with Signicat
2. Provide Gutenberg blocks for different IdP's
3. Provide GravityForm fields for different IdP's
4. Handles the accessing logic within GravityForms

You can use this plugin outside GravityForms as well but you should handle the "Gate" middleware yourself, you should be able to do this by reading the `OWC_Signicat_OpenID\Session`.

## TODO

This plugin is a first draft, there is a lot [TODO](./TODO.md) still.

## Getting started

```sh
# install node deps
npm i

# install composer deps
composer i
```

## Caveats

1. Make sure whatever URL you use as the redirect URL does not really exist within WordPress, else WP's routing might try to handle it instead.

### Logging

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
