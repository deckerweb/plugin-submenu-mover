<?php
/**
 * Clean temporary state; retain site rules and personal settings unless explicitly opted in.
 * @package PluginSubmenuMover
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
$psm_updater_class = require __DIR__ . '/includes/deckerweb-github-release-updater-v2.php';
$psm_updater_class::clear_repository_cache( 'https://github.com/deckerweb/plugin-submenu-mover' );
require_once __DIR__ . '/includes/deckerweb-plugin-library/lifecycle.php';
deckerweb_library_uninstall_v3( __DIR__ . '/plugin-submenu-mover.php' );

/**
 * Remove only this site's PSM metadata, respecting its deletion preference.
 * @return void
 */
function ddw_psm_uninstall_site() {
	$blog = get_current_blog_id();
	$config = get_option( 'ddw_psm_navigation', [] );
	$keys = [ 'ddw_psm_original_until_' . $blog, 'ddw_psm_pending_' . $blog, 'ddw_psm_undo_' . $blog ];
	if ( ! empty( $config['delete_data'] ) ) {
		delete_option( 'ddw_psm_rules' ); delete_option( 'ddw_psm_navigation' );
		$keys[] = 'ddw_psm_personal_' . $blog;
	}
	foreach ( $keys as $key ) { delete_metadata( 'user', 0, $key, '', true ); }
}
if ( is_multisite() ) {
	$offset = 0;
	do {
		$sites = get_sites( [ 'fields' => 'ids', 'number' => 100, 'offset' => $offset ] );
		foreach ( $sites as $site ) { switch_to_blog( $site ); ddw_psm_uninstall_site(); restore_current_blog(); }
		$offset += count( $sites );
	} while ( count( $sites ) === 100 );
} else { ddw_psm_uninstall_site(); }
