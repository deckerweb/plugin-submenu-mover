# Plugin Submenu Mover

Move some plugin's single settings pages from top-level position to submenu under Tools, Settings or were appropriate. Result: cleaner admin screen, less confusing.

### Tested Compatibility
* **WordPress**: 6.7.2 / 6.8 Beta
* **PHP**: 8.0 – 8.3
* _(... used in production since 2022!)_

---

[Support Project](#support-the-project) | [Installation](#installation) | [How Plugin Works](#how-this-plugin-works) | [Custom Tweaks](#custom-tweaks) | [Changelog](#changelog--releases) | [Plugin Scope / Disclaimer](#plugin-scope--disclaimer)

---

## Support the Project

If you find this project helpful, consider showing your support by buying me a coffee! Your contribution helps me keep developing and improving this plugin.

Enjoying the plugin? Feel free to treat me to a cup of coffee ☕🙂 through the following options:

- [![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/W7W81BNTZE)
- [Buy me a coffee](https://buymeacoffee.com/daveshine)
- [PayPal donation](https://paypal.me/deckerweb)
- [Join my **newsletter** for DECKERWEB WordPress Plugins](https://eepurl.com/gbAUUn)

---

## Installation

**Quick Install – as Plugin**
1. **Download ZIP:** [**quick-edit-featured-image.zip**](https://github.com/deckerweb/plugin-submenu-mover/releases/latest/download/plugin-submenu-mover.zip)
2. Upload via WordPress Plugins > Add New > Upload Plugin
3. Once activated, you’ll should see the integrated plugins _under_ their defined new "home" :-)

#### **Alternative: Use as Code Snippet**
1. Below, download the appropriate snippet version
2. activate or deactivate in your snippets plugin

[**Download .json**](https://github.com/deckerweb/plugin-submenu-mover/releases/latest/download/ddw-plugin-submenu-mover.code-snippets.json) version for: _Code Snippets_ (free & Pro), _Advanced Scripts_ (Premium), _Scripts Organizer_ (Premium)
--> just use their elegant script import features
--> in _Scripts Organizer_ use the "Code Snippets Import"

For all other snippet manager plugins just use our plugin's main .php file [`plugin-submenu-mover.php`](https://github.com/deckerweb/plugin-submenu-mover/blob/master/plugin-submenu-mover.php) and use its content as snippet (bevor saving your snippet: please check for your plugin if the opening php tag needs to be removed or not!).

--> Please decide for one of both alternatives!

---

## How this Plugin Works

1. It takes the top-level admin menu items of _some_ plugins and moves these pages as _submenus_ to an already existing admin pages. For example the popular "The SEO Framework" plugin does not need a top-level item – it's just ONE admin page – and can reside under regular WordPress general settings without any issues. That's the main concept.
2. The currently "integrated" plugins were tested by myself if everything works. The functionaly of that specific plugin admin page must still work as submenu, if that is the case, moving makes total sense. I use this plugin/snippet for some years already without any issues and I developed it for myself.
3. Only plugins get integrated where it makes sense: It must be the only admin page that this specific plugin has, and there should be relevant stuff on it. Promotional upsell/marketing/dashboard pages could be "removed" (even via CSS), these do _need_ to be moved.

---

## Custom Tweaks

#### via Filter (for developers):

(Content upcoming ...)

---

## Changelog / Releases

### 🎉 v1.4.0 – 2025-03-??
* Initial _public_ release – _Yeah!_

### 🎉 v1.0.0 – 2022-07
* Initial _private_ release

---

## Plugin Scope / Disclaimer

This plugin comes as is.

_Disclaimer 1:_ So far I will support the plugin for breaking errors to keep it working. Otherwise support will be very limited. Also, it will NEVER be released to WordPress.org Plugin Repository for a lot of reasons (ah, thanks, Matt!).

_Disclaimer 2:_ All of the above might change. I do all this stuff only in my spare time.

_Most of all:_ Have fun building great sites!!! ;-)

---

Icon used in promo graphics: © Remix Icon

Copyright: © 2022-2025, David Decker – DECKERWEB.de