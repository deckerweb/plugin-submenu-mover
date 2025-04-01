# Plugin Submenu Mover

Move some plugin's single settings pages from top-level position to submenu under Tools, Settings or were appropriate. Result: cleaner admin screen, less confusing.

![Multisite Toolbar Additions plugin banner](https://repository-images.githubusercontent.com/955324356/7cdf9dc9-d028-4732-a5d2-ae855d2855c3)

### Tested Compatibility
* **WordPress**: 6.7.2 / 6.8 Beta
* **PHP**: 8.0 – 8.3
* _(... used in production since 2022!)_

---

[Support Project](#support-the-project) | [Installation](#installation) | [How Plugin Works](#how-this-plugin-works) | [Custom Tweaks](#custom-tweaks) | [Changelog](#changelog--releases) | [Plugin's Backstory](#plugins-backstory) | [Plugin Scope / Disclaimer](#plugin-scope--disclaimer)

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
1. **Download ZIP:** [**plugin-submenu-mover.zip**](https://github.com/deckerweb/plugin-submenu-mover/releases/latest/download/plugin-submenu-mover.zip)
2. Upload via WordPress Plugins > Add New > Upload Plugin
3. Once activated, you’ll should see the integrated plugins _under_ their defined new "home" 😉

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

#### Explanation (Kind of)

1. It takes the top-level admin menu items of _some_ plugins and moves these pages as _submenus_ to an already existing admin pages. For example the popular "The SEO Framework" plugin does not need a top-level item – it's just ONE admin page – and can reside under regular WordPress general settings without any issues. That's the main concept.
2. The currently "integrated" plugins were tested by myself if everything works. The functionaly of that specific plugin admin page must still work as submenu, if that is the case, moving makes total sense. I use this plugin/snippet for some years already without any issues and I developed it for myself.
3. Only plugins get integrated where it makes sense: It must be the only admin page that this specific plugin has, and there should be relevant stuff on it. Promotional upsell/marketing/dashboard pages could be "removed" (even via CSS), these do _need_ to be moved.
4. The "moving" is just a simple WordPress default function, nothing special. There are no settings, not settings page itself, nothing of this stuff. It's all totally lightweight and simple and elegant. You can activate the plugin / snippet at any time.

#### Currently Supported/ Integrated Plugins (as of March 2025)

* _The SEO Framework_ (free - wordpress.org) ➞ now under Settings
* _Limit Login Attempts_ Reloaded (free - wordpress.org) ➞ now under Settings
* _PublishPress Future_ (free - wordpress.org) (formerly: Post Expirator) ➞ now under Settings
* _Labinator Minimal Maintenance Mode_ (free - wordpress.org) ➞ now under Tools
* _WP Toolbelt_ (free - wordpress.org) ➞ now under Tools
* _FluentSnippets_ (free - wordpress.org) ➞ now under Tools
* _Maintenance_ (free - wordpress.org) ➞ now under Tools
* _Related Posts Thumbnails_ (free - wordpress.org) ➞ now under Appearance
* _SearchWP Live Search_ (free - wordpress.org) ➞ now under Settings
* _Breakdance Reading Time Calculator_ (free - wordpress.org) ➞ now under "Breakdance"
* _Cookie Notice_ (free - wordpress.org) ➞ now under Settings
* _Real Cookie Banner_ (free - wordpress.org) ➞ now under Settings
* _Real Cookie Banner Pro_ (Premium) ➞ now under Settings
* _DevKit Pro/free_ (by DPlugins.com) ➞ now under Tools
* _WPCodeBox_ (Premium) ➞ now under Tools
* _OxyProps_ / _BricksProps_ (Premium) ➞ now under Tools
* _Revolution Slider_ (Premium) ➞ now under Appearance
* _Instant IDE_ (Premium) ➞ now under Tools
* _Schema Pro_ (Premium) ➞ now under Settings
* _Elements Hive Pro_ (Premium) ➞ now under "Breakdance"
* _Envato Market_ (API Plugin Envato Marketplaces like themeforest and codecanyon)

NOTE: An integration can no longer work at any time should any of these plugins change something. Please use at your own risk.

---

## Custom Tweaks

#### via Filter (for developers):

The filter `ddw-psm/plugins` allows you to tweak the array of plugins: add more plugins, remove some of the defaults, or change parameters of the defaults.

Typical usage:
```
add_filter( 'ddw-psm/plugins', 'prefix_your_custom_function' );
function prefix_your_custom_function( $plugins ) {

	// do your stuff
	
	return $plugins;
}
```

---

## Changelog / Releases

### 🎉 v1.5.0 – 2025-04-??
* New: Installable and updateable via [Git Updater plugin](https://git-updater.com/)

### 🎉 v1.4.0 – 2025-03-28
* Initial _public_ release – _Yeah!_
* New: Now with class-based approach
* Proven: Battle tested for 3 years already

. . .

### 👍 v1.0.0 – 2022-07
* Initial _private_ release

---

## Plugin's Backstory

_I wanted and needed the functionality of this plugin (Plugin Submenu Mover) myself so I developed it. For years alreay, I was annoyed by plugins putting their single admin pages on the top-level menu position in the WordPress Admin Area. This clutters your admin and takes away precious space, especially when you are on the smaller notebook on the go. This needed to be more streamlined. Formerly I mostly did specific code snippets for each of these plugins but I thought this could be done more elegant. So this code snippet was born and later on being made as plugin as well. This code already works in the installs I maintain for some years and is battle tested._

–– David Decker, plugin developer, in March of 2025

---

## Plugin Scope / Disclaimer

This plugin comes as is.

_Disclaimer 1:_ So far I will support the plugin for breaking errors to keep it working. Otherwise support will be very limited. Also, it will NEVER be released to WordPress.org Plugin Repository for a lot of reasons (ah, thanks, Matt!).

_Disclaimer 2:_ All of the above might change. I do all this stuff only in my spare time.

_Most of all:_ Have fun building great sites!!! ;-)

---

Icon used in promo graphics: © Remix Icon

Readme & Plugin Copyright: © 2022-2025, David Decker – DECKERWEB.de