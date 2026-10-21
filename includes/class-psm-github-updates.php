<?php
/**
 * Integrate the shared deckerweb updater with plugin-scoped safeguards.
 *
 * @package PluginSubmenuMover
 */

namespace Deckerweb\PluginSubmenuMover;

defined( 'ABSPATH' ) || exit;

/** Configure shared update artwork and isolate plugin-specific safeguards. */
final class GitHubUpdates {
	/** Public release repository; never taken from user input. */
	private const REPOSITORY = 'https://github.com/deckerweb/plugin-submenu-mover';

	/** Register after WordPress initializes translations. @return void */
	public function register(): void {
		add_action( 'init', array( $this, 'boot' ) );
	}

	/** Load the shared versioned class once, including beside other deckerweb plugins. @return void */
	public function boot(): void {
		$updater_class = require __DIR__ . '/deckerweb-github-release-updater-v2.php';
		if ( ! defined( $updater_class . '::SUPPORTS_HOST_TRANSLATIONS' ) ) {
			add_action( 'admin_notices', [ $this, 'component_notice' ] ); return;
		}
		try {
			$updater = new $updater_class(
				PSM_PLUGIN_FILE,
				self::REPOSITORY,
				'Plugin Submenu Mover',
				__( 'Automatically move selected plugin settings pages into useful WordPress submenus.', 'plugin-submenu-mover' ),
				$this->artwork(),
				[ 'translate' => require __DIR__ . '/updater-translations.php' ]
			);
		} catch ( \InvalidArgumentException $error ) {
			// An unsupported directory must not break the admin or login design.
			return;
		}
		$updater->register();
		add_filter( 'http_request_args', array( $this, 'request_limits' ), 20, 2 );
		add_filter( 'upgrader_source_selection', array( $this, 'validate_source' ), 30, 4 );
	}

	/**
	 * Explain an older component loaded by another host without breaking this plugin.
	 * @return void
	 */
	public function component_notice(): void {
		$screen = get_current_screen();
		if ( ! current_user_can( 'update_plugins' ) || ! $screen || ! in_array( $screen->base, [ 'plugins', 'update-core', 'tools_page_plugin-submenu-mover' ], true ) ) { return; }
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Another plugin loaded an older shared updater. Update the other deckerweb plugins to restore update checks for Plugin Submenu Mover.', 'plugin-submenu-mover' ) . '</p></div>';
	}

	/**
	 * Provide bundled artwork matching the current administrator's language.
	 *
	 * @return array<string,array<string,string>> WordPress icon and banner maps.
	 */
	public function artwork(): array {
		$language = 1 === preg_match( '/^de(?:_|$)/i', is_locale_switched() ? get_locale() : determine_locale() ) ? 'de' : 'en';
		return array(
			'icons'   => array(
				'1x'  => plugins_url( 'assets/icon.png', PSM_PLUGIN_FILE ),
				'2x'  => plugins_url( 'assets/icon.png', PSM_PLUGIN_FILE ),
			),
			'banners' => array(
				'low'  => plugins_url( 'assets/banner-' . $language . '.png', PSM_PLUGIN_FILE ),
				'high' => plugins_url( 'assets/banner-' . $language . '.png', PSM_PLUGIN_FILE ),
			),
		);
	}

	/**
	 * Bound only this repository's metadata requests; do not change package downloads.
	 *
	 * @param array  $args WordPress HTTP arguments.
	 * @param string $url Requested URL.
	 * @return array
	 */
	public function request_limits( array $args, string $url ): array {
		if ( 'https://api.github.com/repos/deckerweb/plugin-submenu-mover/releases/latest' === $url ) {
			$args['limit_response_size'] = 512 * 1024;
			$args['timeout']             = 6;
			$args['redirection']         = 0;
			$args['sslverify']           = true;
			$args['reject_unsafe_urls']  = true;
		}
		return $args;
	}

	/**
	 * Validate the actual candidate before WordPress removes the installed plugin.
	 * Requirements in installed headers cannot describe a future release reliably.
	 *
	 * @param mixed $source Normalized directory or WP_Error from the shared updater.
	 * @param mixed $remote_source Unused extraction root.
	 * @param mixed $upgrader Unused WordPress upgrader instance.
	 * @param array $hook_extra Upgrade context.
	 * @return mixed Original source or localized WP_Error.
	 */
	public function validate_source( $source, $remote_source, $upgrader, array $hook_extra ) {
		if ( ( $hook_extra['plugin'] ?? '' ) !== plugin_basename( PSM_PLUGIN_FILE ) || ( isset( $hook_extra['type'] ) && 'plugin' !== $hook_extra['type'] ) || ( isset( $hook_extra['action'] ) && 'update' !== $hook_extra['action'] ) || ( ! isset( $hook_extra['type'], $hook_extra['action'] ) && ! ( $upgrader instanceof \Plugin_Upgrader && true === $upgrader->bulk ) ) ) {
			return $source;
		}
		if ( is_wp_error( $source ) ) {
			$messages = array(
				'ddw_ghru_filesystem' => __( 'The update filesystem is unavailable. Please try again.', 'plugin-submenu-mover' ),
				'ddw_ghru_archive'    => __( 'The GitHub package does not contain the Plugin Submenu Mover plugin file.', 'plugin-submenu-mover' ),
				'ddw_ghru_rename'     => __( 'The GitHub package could not be prepared. The installed version has been kept.', 'plugin-submenu-mover' ),
			);
			$code     = $source->get_error_code();
			return isset( $messages[ $code ] ) ? new \WP_Error( $code, $messages[ $code ], $source->get_error_data( $code ) ) : $source;
		}
		global $wp_filesystem;
		if ( ! is_string( $source ) || ! $wp_filesystem ) {
			return new \WP_Error( 'psm_update_source', __( 'The update package could not be checked.', 'plugin-submenu-mover' ) );
		}
		$main = trailingslashit( $source ) . basename( PSM_PLUGIN_FILE );
		if ( ! $wp_filesystem->is_file( $main ) || $wp_filesystem->size( $main ) > 1024 * 1024 ) {
			return new \WP_Error( 'psm_update_source', __( 'The update package could not be checked.', 'plugin-submenu-mover' ) );
		}
		$text = $wp_filesystem->get_contents( $main );
		if ( ! is_string( $text ) ) {
			return new \WP_Error( 'psm_update_source', __( 'The update package could not be checked.', 'plugin-submenu-mover' ) );
		}
		$headers = array();
		foreach ( array( 'Plugin Name', 'Version', 'Update URI', 'Requires PHP', 'Requires at least' ) as $header ) {
			$headers[ $header ] = preg_match( '/^[ \t\/*#@]*' . preg_quote( $header, '/' ) . ':(.*)$/mi', str_replace( "\r", "\n", substr( $text, 0, 8192 ) ), $match ) ? trim( $match[1] ) : '';
		}
		if ( 'Plugin Submenu Mover' !== $headers['Plugin Name'] || self::REPOSITORY !== $headers['Update URI'] || ! preg_match( '/^\d+\.\d+\.\d+$/D', $headers['Version'] ) || version_compare( $headers['Version'], \DDW_Plugin_Submenu_Mover::VERSION, '<=' ) ) {
			return new \WP_Error( 'psm_update_identity', __( 'The package identity or version does not match a newer Plugin Submenu Mover release.', 'plugin-submenu-mover' ) );
		}
		$current  = get_site_transient( 'update_plugins' );
		$expected = is_object( $current ) ? ( $current->response[ plugin_basename( PSM_PLUGIN_FILE ) ]->new_version ?? '' ) : '';
		if ( ! is_string( $expected ) || '' === $expected || $headers['Version'] !== $expected ) {
			return new \WP_Error( 'psm_update_version', __( 'The package version differs from the offered update. Please check for updates again.', 'plugin-submenu-mover' ) );
		}
		foreach ( array( 'Requires PHP', 'Requires at least' ) as $header ) {
			if ( ! preg_match( '/^\d+\.\d+(?:\.\d+)?$/D', $headers[ $header ] ) ) {
				return new \WP_Error( 'psm_update_requirements', __( 'The update package has missing or invalid WordPress/PHP requirements.', 'plugin-submenu-mover' ) );
			}
		}
		if ( ! is_php_version_compatible( $headers['Requires PHP'] ) || ! is_wp_version_compatible( $headers['Requires at least'] ) ) {
			return new \WP_Error( 'psm_update_compatibility', __( 'This release requires a newer WordPress or PHP version. The installed version has been kept.', 'plugin-submenu-mover' ) );
		}
		return $source;
	}
}
