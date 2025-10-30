# Changelog

## v2.0.0

- Refactor: update codebase to PHP 8 standards
- Refactor: drop support for PHP 7
- Change: use Monolog v3 as opposed to Monolog MS-teams dependency

## v1.3.2

- Fix: IssuerBuilder in php-di config
- Fix: handle direct access to redirect endpoint

## v1.3.1

- Fix: uncaught Facile\OpenIDClient\Exception\RemoteException

## v1.3.0

- Add: update to new signicat IDP scoping mechanism

## v1.2.1

- Fix: redirectUrl and refererUrl can not be false
- Fix: return value of GravityFormsService::decrypt() must be of the type string, bool is returned

## v1.2.0

- Add: selectable scope via form field settings
- Change: start session when headers are not sent

## v1.1.1

- Fix: handle incomplete submission pre save correctly

## v1.1.0

- Add: allow multiple identification fields in form

## v1.0.0

- Init: first version of the plugin
