=== Plugin Submenu Mover ===
Contributors: daveshine
Tags: admin, menu, navigation
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Organize plugin menus, collect tools in a Plugin Hub, and keep personal favorites within reach.

== Description ==
Plugin Submenu Mover started in 2022 with a frustration from my own WordPress work: every additional plugin made the admin menu busier and the setting I needed harder to find. I wanted quicker access to my tools and a clearer workspace. The first solution was a code snippet. In 2025, it became a plugin.

Version 2.0 expands that idea into a flexible workspace for everyday plugin management. Individual plugin pages can move into suitable submenus. Larger tools can come together in the optional Plugin Hub, with clear cards, quick search and a contextual menu that keeps their original pages within reach.

Personal favorites, short labels and persistent plugin groups make recurring tasks easier to access. Workspaces help organize different kinds of work. On client sites, individual users can keep their familiar admin while others use the full hub.

The goal has stayed the same since 2022: spend less time looking for things and make working with WordPress easier.

Less menu clutter. More clarity. Organize plugin menus and make room for everyday work.
Your plugins. One clear hub. Cards, page search and contextual navigation bring multi-page tools together.
Your tools stay within reach. An attached plugin group remains available across other admin pages.
Your key pages. Your direct route. Personal favorites with short labels in the hub, admin menu and toolbar.
Your workflow. A familiar admin for clients. Exclude individual users while others use the full hub.

== Installation ==
1. Install the plugin ZIP on a test site and activate it.
2. Open Tools → Plugin Submenu Mover, or Settings in the plugin list.
3. Review automatic moves. Change exceptions or targets, choose Preview changes, then Apply changes.
4. For the hub, enable Plugin Hub and choose Link from hub or Bundle in hub for the desired groups. Review and apply.
5. Open Plugin Hub. Use the cards, Find a page, stars and plugin switcher. Configure workspace templates on the settings page.

== Frequently Asked Questions ==
= Does a cleaner menu change access permissions? =
No. Each destination retains its original capability. Workspaces and menu visibility are navigation preferences, not access control.

= Why is a plugin missing or unchanged? =
The hub lists registered, permitted non-core top-level menus; core menus and post-type roots are excluded. A page may be unavailable to your account, externally linked or not registered. Existing automatic moves use their curated registry. Rules for unavailable integrations remain stored.

= Can I bundle ten plugins? =
The hub supports multiple selected groups, including ten or more. Cards and the context menu use the same registered destinations. The number of groups is not a guarantee of compatibility with arbitrary third-party versions.

= What happens after a target plugin update? =
A changed registered menu structure prevents hiding that original group until reviewed again. Version and identity are shown when the callback’s owning installed plugin can be identified. Menu discovery does not prove every plugin function was tested.

= Does it work with another menu editor? =
Let one tool own each group. Another editor can change registrations or visual selection. Use link mode or original view while diagnosing conflicts; universal compatibility is not promised.

= What about Multisite? =
Rules, hub selections and workspaces belong to each site; favorites and history belong to each user and site. Network Admin menus are untouched. Network activation uses each site’s own settings; newly created sites start with defaults.

= What data remains after deactivation or uninstall? =
Deactivation preserves configuration. Uninstall removes temporary preview, undo and original-view metadata. Site settings and personal shortcuts remain by default; the explicit deletion switch removes this plugin’s data for the site. On network uninstall each site’s choice is respected. Shared Library cleanup follows its last-host policy. Other plugins and their content remain untouched.

== Changelog ==
= 2.0.0 · 2026-10-19 =
* New: Optional Plugin Hub with cards, search and a persistent attached plugin group.
* New: Personal favorites and short labels, command palette and Site Health integration.
* New: Position settings, selection templates and searchable user exclusions.
* Improved: Navigation, settings and vector artwork.
* Fixed: Detection of ACF and JetFormBuilder.
* Misc: Requires PHP 8.3 or newer; WordPress 6.7 or newer is retained.











= 1.4.0 · 2025-03-28 =
* New: Initial public release with class-based architecture.
* Misc: Based on the privately used version since 2022.

= 1.0.0 · 2022-07 =
* New: Initial private release.
