# OWC Signicat OpenID

Log into the Signicat Broker with OpenID.

## ✅ Features

1. Configure Signicat with WordPress
2. Gutenberg block to insert a Signicat OpenID login button
3. GravityForm fields to insert a Signicat OpenID login button
4. JavaScript to show a session expiration modal

You can use this plugin outside GravityForms as well, but you should handle the "Gate" middleware yourself, you should be able to do this by reading the `OWC_Signicat_OpenID\Session`.

## 🚧 TODO

This plugin is a first draft, there is a lot [TODO](./TODO.md) still.

## Getting started

```sh
# install node deps
npm i

# install composer deps
composer i
```

### 📄 Wiki

You can find more implementation information on this repo's [Wiki](https://github.com/yardinternet/plugin-owc-signicat-openid/wiki).

## ⚠️ Caveats

1. Make sure whatever URL you use as the redirect URL does not really exist within WordPress, else WP's routing might try to handle it instead.

## About us

[![banner](https://raw.githubusercontent.com/yardinternet/.github/refs/heads/main/profile/assets/small-banner-github.svg)](https://www.yard.nl/werken-bij/)
