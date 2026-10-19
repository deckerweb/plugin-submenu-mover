<?php
/**
 * Plugin Name: Plugin Submenu Mover
 * Plugin URI: https://github.com/deckerweb/plugin-submenu-mover
 * Description: Organize plugin settings with safe submenu moves, an optional Plugin Hub, personal favorites and reviewable rules.
 * Version: 2.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.3
 * Author: David Decker – DECKERWEB
 * Author URI: https://github.com/deckerweb
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: plugin-submenu-mover
 * Domain Path: /languages/
 * Update URI: https://github.com/deckerweb/plugin-submenu-mover
 * GitHub Plugin URI: https://github.com/deckerweb/plugin-submenu-mover
 *
 * Copyright © 2022–2026 David Decker – DECKERWEB.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;
if ( version_compare( PHP_VERSION, '8.3', '<' ) ) { return; }
// The plugin and generated standalone snippet are alternatives.
if ( class_exists( 'DDW_Plugin_Submenu_Mover', false ) ) { return; }
if ( ! defined( 'PSM_SNIPPET_MODE' ) ) {
	define( 'PSM_PLUGIN_FILE', __FILE__ );
	require_once __DIR__ . '/includes/deckerweb-changelog-v1.php';
	require_once __DIR__ . '/includes/deckerweb-plugin-library/bootstrap.php';
	deckerweb_library_register_v2( __FILE__, [], __DIR__ . '/includes/deckerweb-plugin-library' );
}

/** Coordinates curated single-page moves, site rules and optional contextual plugin navigation. */
if ( ! class_exists( 'DDW_Plugin_Submenu_Mover', false ) ) {
class DDW_Plugin_Submenu_Mover {
	public const VERSION = '2.0.0';
	private const OPTION = 'ddw_psm_rules';
	private const PREVIEW = 'ddw_psm_original_until_';
	private $results = [];
	private $parents = [];
	private $page_hook = '';
	private $ran = false;
	public $navigation;

	/**
	 * Register the mover lifecycle and optional admin navigation controller.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'apply_user_exclusion' ], 1 );
		if ( ! defined( 'PSM_SNIPPET_MODE' ) && is_admin() ) {
			require_once __DIR__ . '/includes/class-psm-navigation.php';
			$this->navigation = new DDW_PSM_Navigation( $this );
		}
		add_action( 'admin_menu', [ $this, 'register_page' ], 90 );
		add_action( 'admin_menu', [ $this, 'move_toplevel_to_submenu' ], 999 );
		add_action( 'admin_init', [ $this, 'handle_post' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		if ( ! defined( 'PSM_SNIPPET_MODE' ) ) {
			add_action( 'init', [ $this, 'translations' ], 0 );
			add_action( 'init', [ $this, 'updates' ] );
			add_filter( 'plugin_action_links_' . plugin_basename( PSM_PLUGIN_FILE ), [ $this, 'action_links' ] );
			add_filter( 'plugin_row_meta', 'ddw_psm_pluginrow_meta', 10, 2 );
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'psm rules', [ $this, 'cli_rules' ] );
		}
	}
	/** Check this site's local user exclusions without changing roles or preferences. @return bool Whether this account may use PSM. */
	public function user_allowed() {
		$value = get_option( 'ddw_psm_navigation', [] );
		$ids = is_array( $value ) && is_array( $value['excluded_users'] ?? null ) ? array_slice( $value['excluded_users'], 0, 200 ) : [];
		return ! in_array( get_current_user_id(), $ids, true );
	}

	/** Restore ordinary navigation for excluded accounts before admin hooks run. @return void */
	public function apply_user_exclusion() {
		if ( ! is_admin() || $this->user_allowed() ) { return; }
		global $wp_filter;
		foreach ( $wp_filter as $name => $hook ) {
			if ( ! $hook instanceof WP_Hook ) { continue; }
			foreach ( $hook->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $entry ) {
					$callback = $entry['function'];
					if ( is_array( $callback ) && isset( $callback[0], $callback[1] ) && ( $callback[0] === $this || ( $this->navigation && $callback[0] === $this->navigation ) ) && ! in_array( $callback[1], [ 'translations', 'updates' ], true ) ) { remove_filter( $name, $callback, $priority ); }
				}
			}
		}
		remove_filter( 'plugin_row_meta', 'ddw_psm_pluginrow_meta', 10 );
		if ( is_admin() && ( in_array( $_GET['page'] ?? '', [ 'plugin-submenu-mover', 'psm-plugin-hub' ], true ) || 'psm_navigation' === ( $_REQUEST['action'] ?? '' ) || isset( $_POST['psm_action'] ) ) ) { wp_die( esc_html( $this->tr( 'Plugin navigation is disabled for this account on this site.' ) ), '', [ 'response' => 403 ] ); }
	}

	/**
	 * Load host language resources.
	 * @return void No return value.
	 */
	public function translations() {
		load_plugin_textdomain( 'plugin-submenu-mover', false, dirname( plugin_basename( PSM_PLUGIN_FILE ) ) . '/languages' );
	}
	/**
	 * Boot the pinned shared release updater.
	 * @return void No return value.
	 */
	public function updates() {
		require_once __DIR__ . '/includes/class-psm-github-updates.php';
		( new \Deckerweb\PluginSubmenuMover\GitHubUpdates() )->boot();
	}
	/**
	 * Determine whether the current administrator uses German.
	 * @return bool Validated result.
	 */
	private function german() { return 1 === preg_match( '/^de(?:_|$)/i', is_locale_switched() ? get_locale() : determine_locale() ); }
	/**
	 * Return the site-specific rule settings URL.
	 * @return string Validated result.
	 */
	private function url() { return admin_url( 'tools.php?page=plugin-submenu-mover' ); }
	/**
	 * Translate host interface text.
	 * @param string $text English source text.
	 * @return string Validated result.
	 */
	private function tr( $text ) { return __( $text, 'plugin-submenu-mover' ); }
	/**
	 * Register the site-level overview under Tools.
	 * @return void No return value.
	 */
	public function register_page() {
		$this->page_hook = add_management_page( 'Plugin Submenu Mover', 'Plugin Submenu Mover', 'manage_options', 'plugin-submenu-mover', [ $this, 'render' ] );
	}
	/**
	 * Prepend the primary settings link when permitted.
	 * @param array $links Existing plugin action links.
	 * @return array Validated result.
	 */
	public function action_links( $links ) {
		if ( current_user_can( 'manage_options' ) && ! is_network_admin() ) { array_unshift( $links, '<a href="' . esc_url( $this->url() ) . '">' . esc_html( $this->tr( 'Settings' ) ) . '</a>' ); }
		return $links;
	}
	/**
	 * Validate a portable menu identifier without rewriting it.
	 * @param mixed $value Untrusted candidate.
	 * @return bool Validated result.
	 */
	private function identifier( $value ) {
		return is_string( $value ) && strlen( $value ) <= 200 && 1 === preg_match( '~^[a-zA-Z0-9_./?=&%:-]+$~D', $value ) && false === strpos( $value, '://' ) && false === strpos( $value, '..' );
	}
	/**
	 * Validate an integration configuration before inspecting menus.
	 * @param mixed $config Registry entry.
	 * @return bool Validated result.
	 */
	private function valid_config( $config ) {
		if ( ! is_array( $config ) || ! isset( $config['is-active'] ) || ! is_bool( $config['is-active'] ) ) { return false; }
		foreach ( [ 'admin-slug', 'move-to-menu', 'capability' ] as $key ) {
			if ( ! isset( $config[$key] ) || ! $this->identifier( $config[$key] ) ) { return false; }
		}
		if ( $config['admin-slug'] === $config['move-to-menu'] || ! isset( $config['label'] ) || ! is_string( $config['label'] ) || '' === trim( $config['label'] ) || strlen( $config['label'] ) > 300 ) { return false; }
		if ( isset( $config['remove-submenus'] ) ) {
			if ( ! is_array( $config['remove-submenus'] ) || count( $config['remove-submenus'] ) > 20 ) { return false; }
			foreach ( $config['remove-submenus'] as $pair ) {
				if ( ! is_array( $pair ) || count( $pair ) !== 2 || ! isset( $pair[0], $pair[1] ) || ! $this->identifier( $pair[0] ) || ! is_string( $pair[1] ) || strlen( $pair[1] ) > 1000 ) { return false; }
			}
		}
		return true;
	}
	/**
	 * Validate portable exceptions while retaining unknown integration IDs.
	 * @param mixed $raw Untrusted portable rules.
	 * @return array|WP_Error Validated result.
	 */
	public function validate_rules( $raw ) {
		if ( ! is_array( $raw ) || count( $raw ) > 200 ) { return new WP_Error( 'psm_rules', $this->tr( 'Invalid rules. Use the exported configuration format.' ) ); }
		$result = [];
		foreach ( $raw as $id => $rule ) {
			if ( ! is_string( $id ) || ! preg_match( '/^[a-z0-9_-]{1,100}$/D', $id ) || ! is_array( $rule ) || array_diff( array_keys( $rule ), [ 'disabled', 'parent' ] ) || ! isset( $rule['disabled'] ) || ! is_bool( $rule['disabled'] ) ) { return new WP_Error( 'psm_rules', $this->tr( 'Invalid rules. Use the exported configuration format.' ) ); }
			$parent = $rule['parent'] ?? '';
			if ( '' !== $parent && ! $this->identifier( $parent ) ) { return new WP_Error( 'psm_rules', $this->tr( 'Invalid target menu.' ) ); }
			if ( ! is_string( $parent ) ) { return new WP_Error( 'psm_rules', $this->tr( 'Invalid target menu.' ) ); }
			$result[$id] = [ 'disabled' => $rule['disabled'], 'parent' => $parent ];
		}
		ksort( $result );
		return $result;
	}
	/**
	 * Read valid stored site rules without deleting unavailable integrations.
	 * @return array Validated result.
	 */
	public function rules() {
		$rules = $this->validate_rules( get_option( self::OPTION, [] ) );
		return is_wp_error( $rules ) ? [] : $rules;
	}
	/**
	 * Check the current user’s bounded original-menu preference.
	 * @return bool Validated result.
	 */
	public function original_view() {
		$expires = (int) get_user_meta( get_current_user_id(), self::PREVIEW . get_current_blog_id(), true );
		return current_user_can( 'manage_options' ) && $expires > time() && $expires <= time() + HOUR_IN_SECONDS;
	}
	/**
	 * Find a top-level menu by its original slug.
	 * @param string $slug Original menu slug.
	 * @return array|false Validated result.
	 */
	private function menu_item( $slug ) {
		global $menu;
		foreach ( (array) $menu as $item ) { if ( isset( $item[2] ) && $slug === $item[2] ) { return $item; } }
		return false;
	}
	/**
	 * Compare identifiers including HTML-encoded query separators.
	 * @param string $a First slug.
	 * @param string $b Second slug.
	 * @return bool Validated result.
	 */
	private function same_slug( $a, $b ) { return html_entity_decode( $a, ENT_QUOTES, 'UTF-8' ) === html_entity_decode( $b, ENT_QUOTES, 'UTF-8' ); }
	/**
	 * Check whether a duplicate link belongs to the integration’s explicit cleanup list.
	 * @param string $parent Original parent slug.
	 * @param string $slug Candidate submenu slug.
	 * @param array $config Validated integration.
	 * @return bool Validated result.
	 */
	private function permitted_cleanup( $parent, $slug, $config ) {
		foreach ( $config['remove-submenus'] ?? [] as $pair ) { if ( $parent === $pair[0] && $this->same_slug( $slug, $pair[1] ) ) { return true; } }
		return false;
	}
	/**
	 * Move one safe single-page integration while retaining its original hooks.
	 * @param array $config Validated integration.
	 * @return string Validated result.
	 */
	private function move_plugin_menu( $config ) {
		global $submenu, $_parent_pages, $_registered_pages, $_wp_submenu_nopriv;
		$slug = $config['admin-slug']; $parent = $config['move-to-menu'];
		$original = $this->menu_item( $slug );
		if ( ! $original ) { return 'source-missing'; }
		$destination = $this->menu_item( $parent );
		if ( $this->navigation && $this->navigation->bundles( $parent ) ) { return 'hub-target'; }
		if ( ! $destination ) { return 'parent-missing'; }
		// Keep the original capability. A mapping must never grant wider access.
		if ( ! current_user_can( $original[1] ) || ! current_user_can( $config['capability'] ) || ! current_user_can( $destination[1] ) ) { return 'permission'; }
		foreach ( $submenu[$slug] ?? [] as $child ) {
			if ( isset( $child[2] ) && $child[2] !== $slug && ! $this->permitted_cleanup( $slug, $child[2], $config ) ) { return 'children'; }
		}
		// WordPress keeps the original toplevel hook in admin_page_hooks. Verify it,
		// rather than guessing or forwarding callbacks and load hooks twice.
		$old_hook = get_plugin_page_hookname( $slug, '' );
		$new_hook = get_plugin_page_hookname( $slug, $parent );
		if ( $old_hook !== $new_hook || empty( $_registered_pages[$old_hook] ) || ! has_action( $old_hook ) ) { return 'registration'; }
		foreach ( $submenu[$parent] ?? [] as $child ) {
			if ( isset( $child[2] ) && $child[2] === $slug && ! $this->permitted_cleanup( $parent, $slug, $config ) ) { return 'duplicate'; }
		}
		// Snapshot only the WordPress arrays this operation can mutate.
		$snapshot = [ $submenu, $_parent_pages, $_registered_pages, $_wp_submenu_nopriv ];
		foreach ( $config['remove-submenus'] ?? [] as $pair ) {
			foreach ( $submenu[$pair[0]] ?? [] as $child ) {
				if ( isset( $child[2] ) && $this->same_slug( $child[2], $pair[1] ) ) { remove_submenu_page( $pair[0], $child[2] ); }
			}
		}
		$hook = add_submenu_page( $parent, $original[3] ?? $config['label'], $config['label'], $original[1], $slug );
		if ( false === $hook || $hook !== $old_hook ) {
			list( $submenu, $_parent_pages, $_registered_pages, $_wp_submenu_nopriv ) = $snapshot;
			return 'failed';
		}
		if ( false === remove_menu_page( $slug ) ) {
			list( $submenu, $_parent_pages, $_registered_pages, $_wp_submenu_nopriv ) = $snapshot;
			return 'failed';
		}
		// No child pages remain by the preflight above; remove the orphan self-link.
		unset( $submenu[$slug] );
		return 'moved';
	}
	/**
	 * Apply eligible single-page moves once and collect their outcomes.
	 * @return void No return value.
	 */
	public function move_toplevel_to_submenu() {
		if ( $this->ran ) { return; } $this->ran = true;
		global $menu;
		$configs = $this->get_plugins(); $rules = $this->rules();
		if ( ! is_array( $configs ) || count( $configs ) > 200 ) { $this->results['registry'] = [ 'label' => 'Registry', 'status' => 'invalid', 'parent' => '', 'slug' => '', 'enabled' => false, 'default-parent' => '' ]; return; }
		foreach ( $configs as $id => $config ) {
			$valid = is_string( $id ) && 1 === preg_match( '/^[a-z0-9_-]{1,100}$/D', $id ) && $this->valid_config( $config );
			if ( ! $valid ) { $this->results['invalid-' . count( $this->results )] = [ 'label' => is_string( $id ) ? $id : 'Integration', 'status' => 'invalid', 'parent' => '', 'slug' => '', 'enabled' => false, 'default-parent' => '' ]; continue; }
			$default = $config['move-to-menu'];
			$source = $this->menu_item( $config['admin-slug'] );
			$source_label = $source ? trim( wp_strip_all_tags( $source[0] ) ) : $config['label'];
			if ( ! empty( $rules[$id]['parent'] ) ) { $config['move-to-menu'] = $rules[$id]['parent']; }
			$enabled = empty( $rules[$id]['disabled'] );
			if ( ! $config['is-active'] ) { $status = 'inactive'; }
			elseif ( $this->navigation && $this->navigation->owns( $config['admin-slug'] ) ) { $status = 'hub'; }
			elseif ( ! $enabled ) { $status = 'disabled'; }
			elseif ( $this->original_view() ) { $status = 'original'; }
			elseif ( ! $this->valid_config( $config ) ) { $status = 'invalid'; }
			else { $status = $this->move_plugin_menu( $config ); }
			$this->results[$id] = [ 'label' => $config['label'], 'status' => $status, 'parent' => $config['move-to-menu'], 'default-parent' => $default, 'slug' => $config['admin-slug'], 'enabled' => $enabled, 'original' => $source_label ];
		}
		// Offer only top-level targets that remain available after all moves.
		foreach ( (array) $menu as $item ) {
			$label = isset( $item[0] ) ? trim( wp_strip_all_tags( $item[0] ) ) : '';
			if ( '' !== $label && isset( $item[1], $item[2] ) && $this->identifier( $item[2] ) && current_user_can( $item[1] ) && ! ( $this->navigation && $this->navigation->bundles( $item[2] ) ) ) { $this->parents[$item[2]] = $label; }
		}
	}
	/**
	 * Return English outcome messages for translation at output time.
	 * @return array Validated result.
	 */
	private function statuses() {
		return [ 'hub-target' => 'Target belongs to a bundled group; original menu kept', 'hub' => 'Available in Plugin Hub; original registration preserved', 'moved' => 'Moved', 'inactive' => 'Not detected or disabled by filter', 'disabled' => 'Kept at original position', 'original' => 'Original menu view is active', 'source-missing' => 'Original menu not found', 'parent-missing' => 'Target menu not found', 'permission' => 'Menu access is unavailable for this user', 'children' => 'Additional plugin pages found; left unchanged', 'registration' => 'Page registration could not be preserved', 'duplicate' => 'Target already contains this page; left unchanged', 'failed' => 'Move failed; original menu kept', 'invalid' => 'Invalid integration configuration' ];
	}
	/**
	 * Return bounded support metadata without credentials or site URLs.
	 * @return array Validated result.
	 */
	public function diagnostic_data() {
		return [ 'plugin' => 'plugin-submenu-mover', 'version' => self::VERSION, 'wordpress' => get_bloginfo( 'version' ), 'php' => PHP_VERSION, 'original_view' => $this->original_view(), 'integrations' => $this->results ];
	}
	/**
	 * Authorize rule actions and stage saves/imports for review.
	 * @return void No return value.
	 */
	public function handle_post() {
		if ( ! $this->user_allowed() ) { wp_die( esc_html( $this->tr( 'Access denied.' ) ), '', [ 'response' => 403 ] ); }
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['psm_action'] ) || ! is_string( $_POST['psm_action'] ) ) { return; }
		if ( ! current_user_can( 'manage_options' ) || is_network_admin() ) { wp_die( esc_html( $this->tr( 'You cannot change these rules.' ) ), '', [ 'response' => 403 ] ); }
		check_admin_referer( 'psm_rules' );
		$action = wp_unslash( $_POST['psm_action'] ); $notice = 'saved';
		if ( 'original' === $action ) {
			update_user_meta( get_current_user_id(), self::PREVIEW . get_current_blog_id(), time() + HOUR_IN_SECONDS ); $notice = 'original';
		} elseif ( 'resume' === $action ) {
			delete_user_meta( get_current_user_id(), self::PREVIEW . get_current_blog_id() ); $notice = 'resume';
		} elseif ( 'export' === $action ) {
			nocache_headers(); header( 'Content-Type: application/json; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="plugin-submenu-mover-rules.json"' );
			echo wp_json_encode( [ 'format' => 'ddw-psm-rules', 'schema' => 2, 'rules' => $this->rules(), 'navigation' => $this->navigation ? $this->navigation->portable() : [] ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); exit;
		} elseif ( 'import' === $action ) {
			$raw = isset( $_POST['psm_import'] ) && is_string( $_POST['psm_import'] ) ? wp_unslash( $_POST['psm_import'] ) : '';
			if ( strlen( $raw ) > 1048576 ) { wp_die( esc_html( $this->tr( 'The configuration is too large.' ) ), '', [ 'response' => 400 ] ); }
			$document = json_decode( $raw, true );
			if ( ! is_array( $document ) || ( $document['format'] ?? '' ) !== 'ddw-psm-rules' || ! in_array( $document['schema'] ?? null, [ 1, 2 ], true ) ) { wp_die( esc_html( $this->tr( 'Invalid rules. Use the exported configuration format.' ) ), '', [ 'response' => 400 ] ); }
			$rules = $this->validate_rules( $document['rules'] ?? null );
			if ( is_wp_error( $rules ) ) { wp_die( esc_html( $rules->get_error_message() ), '', [ 'response' => 400 ] ); }
			// Merge intentionally: configurations do not erase unrelated site exceptions.
			if ( $this->navigation ) {
				$selected = isset( $_POST['psm_import_ids'] ) && is_array( $_POST['psm_import_ids'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['psm_import_ids'] ) ) : [];
				if ( $selected ) { $rules = array_intersect_key( $rules, array_fill_keys( $selected, true ) ); }

				$navigation = $this->navigation->config();
				if ( 2 === $document['schema'] && ! empty( $document['navigation'] ) ) {
					$imported = $this->navigation->validate( $document['navigation'] );
					if ( is_wp_error( $imported ) ) { wp_die( esc_html( $imported->get_error_message() ), '', [ 'response' => 400 ] ); }
					foreach ( $imported['groups'] as &$mode ) { if ( 'bundle' === $mode ) { $mode = 'link'; } } unset( $mode );
					$navigation['groups'] = array_replace( $navigation['groups'], $imported['groups'] );
					$navigation['profiles'] = array_replace( $navigation['profiles'], $imported['profiles'] );
					$navigation['enabled'] = $imported['enabled'];
					foreach ( [ 'menu_position', 'toolbar_position' ] as $position ) { if ( isset( $document['navigation'][ $position ] ) ) { $navigation[ $position ] = $imported[ $position ]; } }
					if ( isset( $document['navigation']['toolbar_icon'] ) ) { $navigation['toolbar_icon'] = $imported['toolbar_icon']; }
				}
				$this->navigation->stage( array_replace( $this->rules(), $rules ), $navigation );
			} else { update_option( self::OPTION, array_replace( $this->rules(), $rules ), false ); }
		} elseif ( 'save' === $action ) {
			$configs = $this->get_plugins(); $rules = $this->rules(); $posted = isset( $_POST['psm_rules'] ) && is_array( $_POST['psm_rules'] ) ? wp_unslash( $_POST['psm_rules'] ) : [];
			foreach ( is_array( $configs ) ? $configs : [] as $id => $config ) {
				if ( ! is_string( $id ) || ! preg_match( '/^[a-z0-9_-]{1,100}$/D', $id ) || ! $this->valid_config( $config ) ) { continue; }
				$row = $posted[$id] ?? []; if ( ! is_array( $row ) ) { continue; }
				$rules[$id] = [ 'disabled' => ! isset( $row['enabled'] ) || '1' !== $row['enabled'], 'parent' => $row['parent'] ?? '' ];
			}
			$rules = $this->validate_rules( $rules );
			if ( is_wp_error( $rules ) ) { wp_die( esc_html( $rules->get_error_message() ), '', [ 'response' => 400 ] ); }
			if ( $this->navigation ) { $this->navigation->stage( $rules, $this->navigation->config() ); } else { update_option( self::OPTION, $rules, false ); }
		} else { return; }
		if ( $this->navigation && in_array( $action, [ 'save', 'import' ], true ) ) { wp_safe_redirect( $this->url() ); exit; }
		wp_safe_redirect( add_query_arg( 'psm_notice', $notice, $this->url() ) ); exit;
	}
	/**
	 * Print portable shared rules for explicit CLI deployments.
	 * @param array $args Positional command arguments.
	 * @param array $assoc_args Named command arguments.
	 * @return void No return value.
	 */
	public function cli_rules( $args, $assoc_args ) {
		WP_CLI::line( wp_json_encode( [ 'format' => 'ddw-psm-rules', 'schema' => 2, 'rules' => $this->rules(), 'navigation' => $this->navigation ? $this->navigation->portable() : [] ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	}
	/**
	 * Build the curated integration registry and apply the established customization filter.
	 * @return array Validated result.
	 */
	private function get_plugins() {

		/** Slugs for admin pages */
		$tools    = 'tools.php';
		$settings = 'options-general.php';
		$themes   = 'themes.php';

		$oxyprops_label = ! defined( 'CT_VERSION' ) && defined( 'BRICKS_VERSION' ) ? 'BricksProps' : 'OxyProps';

		/** DevKit Plugin check for Admin slug */
		$devkit_admin = defined( 'DPDEV_ADMIN_SLUG' ) ? DPDEV_ADMIN_SLUG : 'devkit';

		/** Collecting the plugin data */
		$do_plugins = [

			/** Plugin: The SEO Framework (free - wordpress.org) */
			'the-seo-framework' => [
				'is-active'    => defined( 'THE_SEO_FRAMEWORK_VERSION' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'theseoframework-settings',
				'move-to-menu' => $settings,
				'label'        => 'The SEO Framework',
			],

			/** Plugin: Limit Login Attempts Reloaded (free - wordpress.org) */
			'limit-login-attempts-reloaded' => [
				'is-active'    => defined( 'LLA_PLUGIN_URL' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'limit-login-attempts',
				'move-to-menu' => $settings,
				'label'        => 'Limit Login Attempts',
			],

			/** Plugin: PublishPress Future (free - wordpress.org) (formerly: Post Expirator) */
			'publishpress-future' => [
				'is-active'    => defined( 'POSTEXPIRATOR_VERSION' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'publishpress-future',
				'move-to-menu' => $settings,
				'label'        => 'PublishPress Future',
			],

			/** Plugin: Labinator Minimal Maintenance Mode (free - wordpress.org) */
			'labinator-minimal-maintenance-mode' => [
				'is-active'    => defined( 'LMM_VERSION' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'lmm-maintenance',
				'move-to-menu' => $tools,
				'label'        => 'Labinator Minimal Maintenance Mode',
			],

			/** Plugin: WP Toolbelt (free - wordpress.org) */
			'wp-toolbelt' => [
				'is-active'    => defined( 'TOOLBELT_VERSION' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'toolbelt-modules',
				'move-to-menu' => $tools,
				'label'        => 'Toolbelt Modules',
			],

			/** Plugin: DevKit Pro/free (by DPlugins.com) */
			'devkit-pro' => [
				'is-active'    => defined( 'DPDEVKIT_URL' ),
				'capability'   => 'activate_plugins',
				'admin-slug'   => $devkit_admin,
				'move-to-menu' => $tools,
				'label'        => 'DevKit',
			],

			/** Plugin: WPCodeBox (Premium) */
			'wpcodebox' => [
				'is-active'    => defined( 'WPCODEBOX_VERSION' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'wpcb_menu_page_php',
				'move-to-menu' => $tools,
				'label'        => 'WPCodeBox',
			],

			/** Plugin: FluentSnippets (free - wordpress.org) */
			'fluentsnippets' => [
				'is-active'    => defined( 'FLUENT_SNIPPETS_PLUGIN_VERSION' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'fluent-snippets',
				'move-to-menu' => $tools,
				'label'        => 'FluentSnippets',
			],

			/** Plugin: OxyProps / BricksProps (Premium) */
			'obprops-plugin' => [
				'is-active'    => defined( 'OXYPROPS_AUTOLOAD_VERSION' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'oxyprops',
				'move-to-menu' => $tools,
				'label'        => $oxyprops_label,
			],

			/** Plugin: Real Cookie Banner (free - wordpress.org) */
			'real-cookie-banner' => [
				'is-active'    => class_exists( '\DevOwl\RealCookieBanner\lite\FomoCoupon' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'real-cookie-banner-component',
				'move-to-menu' => $settings,
				'label'        => 'Cookies (RCB free)',
			],

			/** Plugin: Real Cookie Banner Pro (Premium) */
			'real-cookie-banner-pro' => [
				'is-active'    => class_exists( '\DevOwl\RealCookieBanner\Vendor\DevOwl\Freemium\CorePro' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'real-cookie-banner-pro-component',
				'move-to-menu' => $settings,
				'label'        => 'Cookies (RCB Pro)',
			],

			/** Plugin: Cookie Notice (free - wordpress.org) */
			'cookie-notice' => [
				'is-active'    => class_exists( 'Cookie_Notice' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'cookie-notice',
				'move-to-menu' => $settings,
				'label'        => 'Cookies',
			],

			/** Plugin: Maintenance (free - wordpress.org) */
			'maintenance' => [
				'is-active'    => class_exists( 'MTNC' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'maintenance',
				'move-to-menu' => $tools,
				'label'        => 'Maintenance',
			],

			/** Plugin: Envato Market (API Plugin Envato Marketplaces like themeforest and codecanyon) */
			'envato-market' => [
				'is-active'    => defined( 'ENVATO_MARKET_VERSION' ),
				'capability'   => 'activate_plugins',
				'admin-slug'   => 'envato-market',
				'move-to-menu' => $tools,
				'label'        => 'Envato Market',
			],

			/** Plugin: Related Posts Thumbnails (free - wordpress.org)  */
			'related-posts-thumbnails' => [
				'is-active'    => class_exists( 'RelatedPostsThumbnails' ),
				'capability'   => 'edit_theme_options',
				'admin-slug'   => 'related-posts-thumbnails',
				'move-to-menu' => $themes,
				'label'        => 'Related Posts',
			],

			/** Plugin: Revolution Slider (Premium)  */
			'revolution-slider' => [
				'is-active'    => defined( 'RS_REVISION' ),
				'capability'   => 'edit_theme_options',
				'admin-slug'   => 'revslider',
				'move-to-menu' => $themes,
				'label'        => 'Slider Revolution',
			],

			/** Plugin: Schema Pro (Premium) */
			'schema-pro' => [
				'is-active'    => defined( 'BSF_AIOSRS_PRO_VER' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'aiosrs_pro_admin_menu_page',
				'move-to-menu' => $settings,
				'label'        => 'Schema Pro',
			],

			/** Plugin: SearchWP Live Search (free - wordpress.org)  */
			'searchwp-live-search' => [
				'is-active'    => defined( 'SEARCHWP_LIVE_SEARCH_VERSION' ),
				'capability'   => 'edit_theme_options',
				'admin-slug'   => 'searchwp-live-search',
				'move-to-menu' => $settings,
				'label'        => 'SearchWP Live Search',
			],

			/** Plugin: Elements Hive Pro (Premium) */
			'elements-hive-pro' => [
				'is-active'    => class_exists( 'ElementsHivePro' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'elements-hive-pro',
				'move-to-menu' => 'breakdance',
				'label'        => 'Elements Hive Pro',
			],

			/** Plugin: Breakdance Reading Time Calculator (free - wordpress.org) */
			'bd-reading-time-calculator' => [
				'is-active'    => function_exists( 'bd_reading_time_menu' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'bd-reading-time',
				'move-to-menu' => 'breakdance',
				'label'        => 'Reading Time Calculator',
			],

			/** Plugin: Bricks Remote Template Sync (free - github.com) */
			'bricks-remote-template-sync' => [
				'is-active'    => defined( 'BRICKS_REMOTE_SYNC_VERSION' ),
				'capability'   => 'manage_options',
				'admin-slug'   => 'bricks-remote-template-sync',
				'move-to-menu' => 'bricks',
				'label'        => 'Bricks Remote Template Sync',
			],

		];  // end array

		// Conflicting links belong to their integration, never run for disabled rules.
		$do_plugins['limit-login-attempts-reloaded']['remove-submenus'] = [ [ 'options-general.php', 'limit-login-attempts' ] ];
		$do_plugins['searchwp-live-search']['remove-submenus'] = [ [ 'searchwp-live-search', 'https://searchwp.com/?utm_source=WordPress&utm_medium=Admin+Menu+Upgrade+Link&utm_campaign=Live+Ajax+Search&utm_content=Upgrade+to+Pro' ] ];
		/**
		 * Customize supported integrations while retaining existing registry field names.
		 * @since 1.0.0
		 * @param array $do_plugins Integration definitions; return the modified registry.
		 */
		return apply_filters( 'ddw-psm/plugins', $do_plugins );
	}
		/**
		 * Read a bounded local language-specific document.
		 * @param string $type Local document basename.
		 * @return string Validated result.
		 */
		private function document( $type ) {
		if ( defined( 'PSM_SNIPPET_MODE' ) ) { return constant( 'PSM_SNIPPET_' . strtoupper( $type ) . ( $this->german() ? '_DE' : '_EN' ) ); }
		$file = __DIR__ . '/docs/' . $type . ( $this->german() ? '-de' : '' ) . '.txt';
		return is_readable( $file ) && filesize( $file ) <= 262144 ? file_get_contents( $file ) : '';
	}
	/**
	 * Enqueue local overview assets only on its registered screen.
	 * @param string $hook Current admin screen hook.
	 * @return void No return value.
	 */
	public function assets( $hook ) {
		if ( $hook !== $this->page_hook ) { return; }
		if ( defined( 'PSM_SNIPPET_MODE' ) ) {
			wp_register_style( 'psm-admin', false, [], self::VERSION ); wp_enqueue_style( 'psm-admin' ); wp_add_inline_style( 'psm-admin', PSM_SNIPPET_CSS );
			wp_register_script( 'psm-admin', false, [], self::VERSION, true ); wp_enqueue_script( 'psm-admin' ); wp_add_inline_script( 'psm-admin', PSM_SNIPPET_JS );
		} else {
			wp_enqueue_style( 'psm-admin', plugins_url( 'assets/admin.css', PSM_PLUGIN_FILE ), [], self::VERSION );
			wp_enqueue_script( 'psm-admin', plugins_url( 'assets/admin.js', PSM_PLUGIN_FILE ), [], self::VERSION, true );
		}
	}
	/**
	 * Render an authorized action’s POST form.
	 * @param string $action Requested operation.
	 * @param string $label English button text.
	 * @return void No return value.
	 */
	private function button_form( $action, $label ) {
		echo '<form method="post" action="' . esc_url( $this->url() ) . '" class="psm-inline">'; wp_nonce_field( 'psm_rules' );
		echo '<input type="hidden" name="psm_action" value="' . esc_attr( $action ) . '"><button class="button" type="submit">' . esc_html( $this->tr( $label ) ) . '</button></form>';
	}
	/**
	 * Render site rules, review workflow, diagnostics and plugin information.
	 * @return void No return value.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$rules = $this->rules(); $statuses = $this->statuses();
		$hub_url = $this->navigation ? menu_page_url( DDW_PSM_Navigation::HUB, false ) : '';
		echo '<div class="wrap psm-root"><header class="psm-header">' . ( defined( 'PSM_SNIPPET_MODE' ) ? '<span class="dashicons dashicons-menu-alt3" aria-hidden="true"></span>' : '<img src="' . esc_url( plugins_url( 'assets/icon-56.png', PSM_PLUGIN_FILE ) ) . '" srcset="' . esc_url( plugins_url( 'assets/icon-112.png', PSM_PLUGIN_FILE ) ) . ' 2x" width="56" height="56" alt="">' ) . '<div><h1>Plugin Submenu Mover</h1><p>' . esc_html( $this->tr( 'Less clutter. Familiar menus. Keep moving.' ) ) . '</p></div>' . ( $hub_url ? '<a class="button psm-open-hub" href="' . esc_url( $hub_url ) . '">' . esc_html( $this->tr( 'Open Plugin Hub' ) ) . '</a>' : '' ) . '</header><hr class="wp-header-end">';
		$notices = [ 'apply' => 'Changes applied.', 'undo' => 'Last change undone.', 'cancel' => 'Changes discarded.', 'saved' => 'Rules saved.', 'original' => 'Original menu view is active for you for one hour.', 'resume' => 'Automatic menu moves are active again.' ];
		$notice = isset( $_GET['psm_notice'] ) && is_string( $_GET['psm_notice'] ) ? $_GET['psm_notice'] : '';
		if ( isset( $notices[$notice] ) ) { echo '<div class="notice notice-success inline"><p>' . esc_html( $this->tr( $notices[$notice] ) ) . '</p></div>'; }
		if ( $this->navigation ) {
			$this->navigation->render_context_bar();
			$this->navigation->bar_preferences();
			$this->navigation->settings();
		}
		echo '<section class="psm-card"><h2>' . esc_html( $this->tr( 'Your plugin menus' ) ) . '</h2><p>' . esc_html( $this->tr( 'Known integrations are applied automatically. Keep individual pages at their original position or choose another existing menu. These rules apply to this site.' ) ) . '</p>';
		if ( $this->original_view() ) { echo '<p class="psm-preview"><strong>' . esc_html( $this->tr( 'Original menu view is active for you for one hour.' ) ) . '</strong></p>'; $this->button_form( 'resume', 'Resume automatic moves' ); }
		else { $this->button_form( 'original', 'Show original menu for one hour' ); }
		echo '<form method="post" action="' . esc_url( $this->url() ) . '">'; wp_nonce_field( 'psm_rules' );
		echo '<input type="hidden" name="psm_action" value="save"><p><label><input type="checkbox" data-psm-show-inactive> ' . esc_html( $this->tr( 'Show undetected integrations' ) ) . '</label></p><p data-psm-empty hidden>' . esc_html( $this->tr( 'No supported integrations detected. Enable the list above to review all available integrations.' ) ) . '</p><div class="psm-table-scroll"><table class="widefat striped psm-table"><thead><tr>';
		foreach ( [ 'Plugin', 'Automatic move', 'Target menu', 'Status', 'Page' ] as $label ) { echo '<th scope="col">' . esc_html( $this->tr( $label ) ) . '</th>'; }
		echo '</tr></thead><tbody>';
		foreach ( $this->results as $id => $row ) {
			echo '<tr' . ( 'inactive' === $row['status'] ? ' data-psm-inactive' : '' ) . '><th scope="row">' . esc_html( $row['label'] ) . '<br><small>' . esc_html( ( $row['original'] ?? $row['label'] ) . ' → ' . ( $this->parents[$row['parent']] ?? $row['parent'] ) ) . '</small></th>';
			if ( 'invalid' === $row['status'] ) { echo '<td colspan="2">—</td>'; }
			else {
				echo '<td><label><input type="checkbox" name="psm_rules[' . esc_attr( $id ) . '][enabled]" value="1" ' . checked( $row['enabled'], true, false ) . '> <span class="screen-reader-text">' . esc_html( sprintf( $this->tr( 'Automatically move %s' ), $row['label'] ) ) . '</span>' . esc_html( $this->tr( 'Enabled' ) ) . '</label></td>';
				echo '<td><label class="screen-reader-text" for="psm-parent-' . esc_attr( $id ) . '">' . esc_html( sprintf( $this->tr( 'Target for %s' ), $row['label'] ) ) . '</label><select id="psm-parent-' . esc_attr( $id ) . '" name="psm_rules[' . esc_attr( $id ) . '][parent]"><option value="">' . esc_html( $this->tr( 'Recommended' ) . ' · ' . ( $this->parents[$row['default-parent']] ?? $row['default-parent'] ) ) . '</option>';
				$override = $rules[$id]['parent'] ?? ''; $parents = $this->parents;
				if ( '' !== $override && ! isset( $parents[$override] ) ) { $parents[$override] = $override . ' · ' . $this->tr( 'Not currently available' ); }
				foreach ( $parents as $slug => $label ) {
					if ( $slug === $row['slug'] ) { continue; }
					echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $override, $slug, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select></td>';
			}
			echo '<td><span class="psm-status psm-status-' . esc_attr( $row['status'] ) . '">' . esc_html( $this->tr( $statuses[$row['status']] ) ) . '</span></td><td>';
			if ( 'moved' === $row['status'] || ( $this->menu_item( $row['slug'] ) && 'inactive' !== $row['status'] ) ) {
				$page = menu_page_url( $row['slug'], false );
				if ( $page ) { echo '<a class="button" href="' . esc_url( $page ) . '" aria-label="' . esc_attr( sprintf( $this->tr( 'Open %s' ), $row['label'] ) ) . '">' . esc_html( $this->tr( 'Open' ) ) . '</a>'; }
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div>'; submit_button( $this->tr( 'Preview changes' ) );
		echo '</form><p class="description">' . esc_html( $this->tr( 'Pages with additional subpages are left unchanged. Existing page hooks and capabilities are preserved. An integration status is not a full compatibility test.' ) ) . '</p></section>';
		echo '<section class="psm-card"><h2>' . esc_html( $this->tr( 'Reuse your rules' ) ) . '</h2><p>' . esc_html( $this->tr( 'Export exceptions and target menus for another site. Import merges rules with this site; matching integration rules are replaced. Unknown integration IDs are retained for future use.' ) ) . '</p>';
		$this->button_form( 'export', 'Download rules' );
		echo '<details><summary>' . esc_html( $this->tr( 'Import rules' ) ) . '</summary><form method="post" action="' . esc_url( $this->url() ) . '">'; wp_nonce_field( 'psm_rules' );
		echo '<input type="hidden" name="psm_action" value="import"><p><label for="psm-import">' . esc_html( $this->tr( 'Paste the exported JSON configuration' ) ) . '</label></p><textarea id="psm-import" name="psm_import" rows="6" maxlength="1048576" required></textarea>';
		submit_button( $this->tr( 'Preview import' ), 'secondary' ); echo '</form></details></section>';
		echo '<section class="psm-card"><details><summary>' . esc_html( $this->tr( 'Support diagnostics' ) ) . '</summary><p>' . esc_html( $this->tr( 'This report contains versions, integration identifiers and menu status. It contains no site URL, user details or credentials.' ) ) . '</p><label class="screen-reader-text" for="psm-diagnostics">' . esc_html( $this->tr( 'Diagnostics report' ) ) . '</label><textarea id="psm-diagnostics" rows="8" readonly>' . esc_textarea( wp_json_encode( $this->diagnostic_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) . '</textarea><p><button class="button" type="button" data-psm-copy>' . esc_html( $this->tr( 'Select diagnostics to copy' ) ) . '</button></p></details></section>';
		$this->footer();
		echo '</div>';
	}
	/**
	 * Render shared plugin information and accessible local changelog.
	 * @return void No return value.
	 */
	public function footer() {
		$locale = is_locale_switched() ? get_locale() : determine_locale();
		$document = $this->german() ? ( 'de_DE_formal' === $locale ? 'Deutsch-Sie' : 'Deutsch' ) : 'English';
		$docs = defined( 'PSM_SNIPPET_MODE' ) ? 'https://github.com/deckerweb/plugin-submenu-mover' . ( $this->german() ? '/blob/main/README.de.md' : '#readme' ) : plugins_url( 'docs/wiki/' . $document . '.html', PSM_PLUGIN_FILE );
		echo '<footer class="psm-footer" aria-label="' . esc_attr( $this->tr( 'Plugin information' ) ) . '"><div><strong>Plugin Submenu Mover</strong> ' . esc_html( self::VERSION ) . ' · <a class="button-link" href="' . esc_url( $docs ) . '" data-psm-dialog="psm-changelog">' . esc_html( $this->tr( 'Changelog' ) ) . '</a> · <a href="' . esc_url( $docs ) . '">' . esc_html( $this->tr( 'Documentation and FAQ' ) ) . '</a><p>' . esc_html( $this->tr( 'Less clutter. Familiar menus. Keep moving.' ) ) . '</p></div><div>© 2022–2026 <a href="https://github.com/deckerweb" target="_blank" rel="noopener noreferrer">David Decker – DECKERWEB</a><p><a href="https://github.com/deckerweb/plugin-submenu-mover" target="_blank" rel="noopener noreferrer">' . esc_html( $this->tr( 'Plugin website' ) ) . '</a> · <a href="https://ko-fi.com/deckerweb" target="_blank" rel="noopener noreferrer">' . esc_html( $this->tr( 'Support the project' ) ) . '</a></p></div></footer>';
		echo '<dialog hidden id="psm-changelog" class="psm-dialog" aria-labelledby="psm-changelog-title"><header><h2 id="psm-changelog-title">Plugin Submenu Mover · ' . esc_html( $this->tr( 'Changelog' ) ) . '</h2><form method="dialog"><button class="button" data-psm-close autofocus>' . esc_html( $this->tr( 'Close' ) ) . '</button></form></header><div class="psm-dialog-content" tabindex="0">';
		echo Deckerweb_Changelog_Renderer_V1::render( $this->document( 'changelog' ) ); // Shared renderer escapes every source text node.
		echo '</div></dialog>';
	}

}

}

/** Retain the existing public meta function/filter; prepare links only for this plugin. */
if ( ! function_exists( 'ddw_psm_pluginrow_meta' ) ) {
	/**
	 * Append plugin-specific support links without embedded personal data.
	 * @param array $links Existing plugin metadata links.
	 * @param string $file Current plugin basename.
	 * @return array Validated result.
	 */
	function ddw_psm_pluginrow_meta( $links, $file ) {
		if ( ! defined( 'PSM_PLUGIN_FILE' ) || $file !== plugin_basename( PSM_PLUGIN_FILE ) || ! current_user_can( 'install_plugins' ) ) { return $links; }
		$links[] = '<a href="https://ko-fi.com/deckerweb" target="_blank" rel="nofollow noopener noreferrer">' . esc_html_x( 'Donate', 'Plugins page listing', 'plugin-submenu-mover' ) . '</a>';
		// A plain subscription link avoids embedding administrator personal data.
		$links[] = '<a href="https://eepurl.com/gbAUUn" target="_blank" rel="nofollow noopener noreferrer">' . esc_html_x( 'Join our Newsletter', 'Plugins page listing', 'plugin-submenu-mover' ) . '</a>';
		/**
		 * Customize metadata links for this plugin only.
		 * @since 1.0.0
		 * @param array $links Prepared HTML links; return the updated links.
		 */
		return apply_filters( 'ddw-psm/pluginrow-meta', $links );
	}
}
new DDW_Plugin_Submenu_Mover();
