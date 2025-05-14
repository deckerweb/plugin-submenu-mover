# Plugin Submenu Mover

Move some plugin's single settings pages from top-level position to submenu under Tools, Settings or were appropriate. Result: cleaner admin screen, less confusing.

![Multisite Toolbar Additions plugin banner](https://repository-images.githubusercontent.com/955324356/7cdf9dc9-d028-4732-a5d2-ae855d2855c3)

* Contributors: [David Decker](https://github.com/deckerweb), [contributors](https://github.com/deckerweb/plugin-submenu-mover/graphs/contributors)
* Tags: plugins, submenu, move, admin menu, wp-admin
* Requires at least: 6.7
* Requires PHP: 7.4
* Stable tag: [main](https://github.com/deckerweb/plugin-submenu-mover/releases/latest)
* Donate link: https://paypal.me/deckerweb
* License: GPL v2 or later

---

[Support Project](#support-the-project) | [Installation](#installation) | [Updates](#updates) | [Description](#description) | [Custom Tweaks](#custom-tweaks) | [FAQ](#frequently-asked-questions) | [Changelog](#changelog) | [Plugin's Backstory](#plugins-backstory) | [Plugin Scope / Disclaimer](#plugin-scope--disclaimer)

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

#### **Quick Install – as Plugin**
[![Download Plugin](https://raw.githubusercontent.com/deckerweb/plugin-submenu-mover/refs/heads/main/assets/button-download-plugin.png)](https://github.com/deckerweb/plugin-submenu-mover/releases/latest/download/plugin-submenu-mover.zip)

1. **Download ZIP:** [**plugin-submenu-mover.zip**](https://github.com/deckerweb/plugin-submenu-mover/releases/latest/download/plugin-submenu-mover.zip)
2. Upload via WordPress Plugins > Add New > Upload Plugin
3. Once activated, you’ll should see the integrated plugins _under_ their defined new "home" 😉

#### **Alternative: Use as Code Snippet**
[![Download Code Snippet](https://raw.githubusercontent.com/deckerweb/plugin-submenu-mover/refs/heads/main/assets/button-download-snippet.png)](https://github.com/deckerweb/plugin-submenu-mover/releases/latest/download/ddw-plugin-submenu-mover.code-snippets.json)  
1. Below, download the appropriate snippet version
2. Activate or deactivate in your snippets plugin

[**Download .json**](https://github.com/deckerweb/plugin-submenu-mover/releases/latest/download/ddw-plugin-submenu-mover.code-snippets.json) version for: _Code Snippets_ (free & Pro), _Advanced Scripts_ (Premium), _Scripts Organizer_ (Premium)  
➔ just use their elegant script import features 
➔ in _Scripts Organizer_ use the "Code Snippets Import" 

For all other snippet manager plugins just use our plugin's main .php file [`plugin-submenu-mover.php`](https://github.com/deckerweb/plugin-submenu-mover/blob/master/plugin-submenu-mover.php) and use its content as snippet (bevor saving your snippet: please check for your plugin if the opening php tag needs to be removed or not!).

➔ Please decide for one of both alternatives!

### Tested Compatibility
* **WordPress**: 6.7.2 ... 6.8.1
* **PHP**: 8.0 – 8.3
* _(... used in production since 2022!)_

---

## Updates 

#### For Plugin Version:

1) Alternative 1: Just download a new [ZIP file](https://github.com/deckerweb/plugin-submenu-mover/releases/latest/download/plugin-submenu-mover.zip) (see above), upload and override existing version. Done.

2) Alternative 2: Use the (free) [**_Git Updater_ plugin**](https://git-updater.com/) and get updates automatically.

3) Alternative 3: Upcoming! – In future I will built-in our own deckerweb updater. This is currently being worked on for my plugins. Stay tuned!

#### For Code Snippet Version:

Just manually: Download the latest Snippet version (see above) and import it in your favorite snippets manager plugin. – You can delete the old snippet; then just activate the new one. Done.

---

## Description 

### How this Plugin Works

#### Explanation (Kind of)

1. It takes the top-level admin menu items of _some_ plugins and moves these pages as _submenus_ to an already existing admin pages. For example the popular "The SEO Framework" plugin does not need a top-level item – it's just ONE admin page – and can reside under regular WordPress general settings without any issues. That's the main concept.
2. The currently "integrated" plugins were tested by myself if everything works. The functionaly of that specific plugin admin page must still work as submenu, if that is the case, moving makes total sense. I use this plugin/snippet for some years already without any issues and I developed it for myself.
3. Only plugins get integrated where it makes sense: It must be the only admin page that this specific plugin has, and there should be relevant stuff on it. Promotional upsell/marketing/dashboard pages could be "removed" (even via CSS), these do _need_ to be moved.
4. The "moving" is just a simple WordPress default function, nothing special. There are no settings, not settings page itself, nothing of this stuff. It's all totally lightweight and simple and elegant. You can activate the plugin / snippet at any time.

### Currently Supported/ Integrated Plugins (as of March 2025)

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

## Frequently Asked Questions 

### Why did you create this plugin?
Because I needed (and wanted!) it myself for the sites I maintain. [Read the backstory here ...](#plugins-backstory)

### Why is this plugin not on wordpress.org plugin repository?
Because the restrictions there for plugin authors are becoming more and more. It would be possible but I don't want that anymore. The same for limited support forums for plugin authors on .org. I have decided to leave this whole thing behind me.


---

## Changelog 

#### The Releases

### 🎉 v1.5.0 – 2025-05-?? 
* New: Support for "Bricks Remote Template Sync" plugin (free, via github.com)
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

Icon used in promo graphics: [© Remix Icon](https://remixicon.com/)

Readme & Plugin Copyright: © 2022-2025, David Decker – DECKERWEB.de