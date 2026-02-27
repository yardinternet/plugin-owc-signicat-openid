# Changelog

## [v2.1.6] - 2026-02-27

- Fix: make session timeout logout more robust and prevent race conditions

## [v2.1.5] - 2026-02-26

- Fix: rest logout

## [v2.1.4] - 2026-02-26

- Fix: add offline_access scope to login request to resolve missing refresh token

## [v2.1.3] - 2026-02-24

- Fix: implement proper logout flow and DigiD compliant error handling

## [v2.1.2] - 2026-02-04

- Fix: idps errors container definition with translatables

## [v2.1.1] - 2025-12-16

- Fix: save session after removing IdP token on logout

## [v2.1.0] - 2025-12-09

- Change: use nin attribute from signicat response before sub attribute
- Chore: CODEOWNERS

## [v2.0.3] - 2025-11-25

- Change: don't use beta version of facile-it/php-openid-client

## [v2.0.2] - 2025-11-14

- Refactor: replace get_site_url() with home_url()

## [v2.0.1] - 2025-11-04

- Fix: related CacheService issues after upgrading to php8

## [v2.0.0] - 2025-10-30

- Refactor: update codebase to PHP 8 standards
- Refactor: drop support for PHP 7
- Change: use Monolog v3 as opposed to Monolog MS-teams dependency

## [v1.3.2] - 2025-10-30

- Fix: IssuerBuilder in php-di config
- Fix: handle direct access to redirect endpoint

## [v1.3.1] - 2025-10-28

- Fix: uncaught Facile\OpenIDClient\Exception\RemoteException

## [v1.3.0] - 2025-09-29

- Add: update to new signicat IDP scoping mechanism

## [v1.2.1] - 2025-08-28

- Fix: redirectUrl and refererUrl can not be false
- Fix: return value of GravityFormsService::decrypt() must be of the type string, bool is returned

## [v1.2.0] - 2025-08-27

- Add: selectable scope via form field settings
- Change: start session when headers are not sent

## [v1.1.2] - 2025-07-18

- Fix: function \_load_textdomain_just_in_time was called incorrectly
- Chore: update license url
- Chore: update README.md
- Fix: bad translation in owc-signicat-openid-nl_NL.po

## [v1.1.1] - 2025-07-08

- Fix: handle incomplete submission pre save correctly

## [v1.1.0] - 2025-07-07

- Add: allow multiple identification fields in form

## [v1.0.0] - 2025-01-29

- Init: first version of the plugin
