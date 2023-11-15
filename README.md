# OWC Signicat OpenID

Log into the Signicat Broker with OpenID.

## 🚨 In progress

This repository is not ready for production.

## ✅ Features

1. OpenID implementation with Signicat
2. Gutenberg blocks for different IdP's to display info
3. GravityForm fields for different IdP's to insert info to a form
4. JS for showing a session expiration modal

You can use this plugin outside GravityForms as well but you should handle the "Gate" middleware yourself, you should be able to do this by reading the `OWC_Signicat_OpenID\Session`.

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
