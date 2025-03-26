<?php # -*- coding: utf-8 -*-
/*
Plugin Name:  Plugin Submenu Mover
Plugin URI:   https://github.com/deckerweb/plugin-submenu-mover
Description:  Move some plugin's single settings pages from top-level position to submenu under Tools, Settings or were appropriate. Result: cleaner admin screen, less confusing.
Project:      Code Snippet: DDW Plugin Submenu Mover
Version:      1.4.0
Author:       David Decker - DECKERWEB
Author URI:   https://deckerweb.de/
License:      GPL-2.0-or-later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  plugin-submenu-mover
Domain Path:  /languages/
Requires WP:  6.7
Requires PHP: 7.4
Copyright:    © 2022-2025, David Decker - DECKERWEB

TESTED WITH:
Product			Versions
--------------------------------------------------------------------------------------------------------------
PHP 			8.0, 8.3
WordPress		6.7.2 ... 6.8 Beta (used in production since 2022!)
--------------------------------------------------------------------------------------------------------------

VERSION HISTORY:
Date        Version     Description
--------------------------------------------------------------------------------------------------------------
2025-03-??	1.4.0       Initial public release
						- Now with class-based approach
						- Battle tested for 3 years already
.			.			.
2022-07		1.0.0	    Initial private release
2022-06		0.5.0       Private test version
2022-06		0.0.0	    Development start
--------------------------------------------------------------------------------------------------------------
*/

/** Prevent direct access */
if ( ! defined( 'ABSPATH' ) ) exit( 'Sorry, you are not allowed to access this file directly.' );

if ( ! class_exists( 'DDW_Plugin_Submenu_Mover' ) ) :

class DDW_Plugin_Submenu_Mover {

	/** Class constants & variables */
	private const VERSION = '1.4.0';
	
	/**
	 * Constructor
	 */
	public function __construct() {
		
		add_action( 'admin_menu', array( $this, 'move_toplevel_to_submenu' ), 999 );
	}
	
	private function get_plugins() {
		
		/** Slugs for admin pages */
		$tools    = 'tools.php';
		$settings = 'options-general.php';
		$plugins  = 'plugins.php';
		$themes   = 'themes.php';
		
		/** Builder checks */
		$is_builder     = 'none_yet';
		$builder_menu   = 'none_yet';
		$oxyprops_label = 'OxyProps';
		
		if ( defined( 'CT_VERSION' ) ) {
			$is_builder   = 'Oxygen';
			$builder_menu = 'ct_dashboard_page';
		} elseif ( defined( 'BRICKS_VERSION' ) ) {
			$is_builder     = 'Bricks';
			$builder_menu   = 'bricks';
			$oxyprops_label = 'BricksProps';
		}
		
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
			
			/** Plugin: Instant IDE (Premium) */
			'instant-ide-manager' => [
				'is-active'    => defined( 'IIDEM_VERSION' ),
				'capability'   => 'activate_plugins',
				'admin-slug'   => 'instant-ide-manager-dashboard',
				'move-to-menu' => $tools,
				'label'        => 'Instant IDE',
			],
			
			/** Plugin: Schema Pro (Premium) */
			'instant-ide-manager' => [
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
			
		];  // end array
		
		/** Return the array, filterable */
		return apply_filters( 'ddw-psm/plugins', $do_plugins );
	}
	
	/**
	 * Move Admin Menu item from top-level menu to a defined submenu page under
	 *   any top-level page, for example "Tools".
	 * 
	 * @link https://njengah.com/remove-wordpress-admin-menu-items/
	 * @link https://developer.wordpress.org/reference/functions/add_submenu_page/
	 */
	public function move_toplevel_to_submenu() {
	
		/** Get the plugin's array with those that should be moved */
		$plugins = $this->get_plugins();
		
		/**
		 * Necessary in-between-step for "Limit Login Attempts"
		 *  plugin to avoid double submenu items
		 */
		if ( array_key_exists( 'limit-login-attempts-reloaded', $plugins ) && defined( 'LLA_PLUGIN_URL' ) ) {
			remove_submenu_page( 'options-general.php', 'limit-login-attempts' );
		}
		
		/**
		 * Necessary in-between-step for "SearchWP Live Search"
		 *  plugin to avoid double submenu items
		 */
		if ( array_key_exists( 'searchwp-live-search', $plugins ) && defined( 'SEARCHWP_LIVE_SEARCH_VERSION' ) ) {
			remove_submenu_page( 'searchwp-live-search', esc_url( 'https://searchwp.com/?utm_source=WordPress&utm_medium=Admin+Menu+Upgrade+Link&utm_campaign=Live+Ajax+Search&utm_content=Upgrade+to+Pro' ) );
		}
		
		/** Iterating through all the plugins moving the menu item */	
		foreach ( $plugins as $plugin ) {
			if ( $plugin[ 'is-active' ] ) {
				
				/** Remove Top-Level */
				remove_menu_page( $plugin[ 'admin-slug' ] );
				
				/** Add the newly "generated" submenu under the preferred top-level item */
				add_submenu_page(
					esc_attr( $plugin[ 'move-to-menu' ] ),
					esc_attr( $plugin[ 'label' ] ),
					esc_attr( $plugin[ 'label' ] ),
					sanitize_key( $plugin[ 'capability' ] ),
					esc_attr( $plugin[ 'admin-slug' ] )
				);
				
			}  // end if
		}  // end foreach
	}

}  // end of class
	
/** Start instance of Class */
new DDW_Plugin_Submenu_Mover();
	
endif;


if ( ! function_exists( 'ddw_psm_pluginrow_meta' ) ) :
	
add_filter( 'plugin_row_meta', 'ddw_psm_pluginrow_meta', 10, 2 );
/**
 * Add plugin related links to plugin page.
 *
 * @since 1.3.0
 *
 * @param array  $ddwp_meta (Default) Array of plugin meta links.
 * @param string $ddwp_file File location of plugin.
 * @return array $ddwp_meta (Modified) Array of plugin links/ meta.
 */
function ddw_psm_pluginrow_meta( $ddwp_meta, $ddwp_file ) {
 
	 if ( ! current_user_can( 'install_plugins' ) ) return $ddwp_meta;
 
	 /** Get current user */
	 $user = wp_get_current_user();
	 
	 /** Build Newsletter URL */
	 $url_nl = sprintf(
		 'https://deckerweb.us2.list-manage.com/subscribe?u=e09bef034abf80704e5ff9809&amp;id=380976af88&amp;MERGE0=%1$s&amp;MERGE1=%2$s',
		 esc_attr( $user->user_email ),
		 esc_attr( $user->user_firstname )
	 );
	 
	 /** List additional links only for this plugin */
	 if ( $ddwp_file === trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . basename( __FILE__ ) ) {
		 $ddwp_meta[] = sprintf(
			 '<a class="button button-inline" href="https://ko-fi.com/deckerweb" target="_blank" rel="nofollow noopener noreferrer" title="%1$s">❤ <b>%1$s</b></a>',
			 esc_html_x( 'Donate', 'Plugins page listing', 'plugin-submenu-mover' )
		 );
 
		 $ddwp_meta[] = sprintf(
			 '<a class="button-primary" href="%1$s" target="_blank" rel="nofollow noopener noreferrer" title="%2$s">⚡ <b>%2$s</b></a>',
			 $url_nl,
			 esc_html_x( 'Join our Newsletter', 'Plugins page listing', 'plugin-submenu-mover' )
		 );
	 }  // end if
 
	 return apply_filters( 'ddw/admin_extras/pluginrow_meta', $ddwp_meta );
 
}  // end function
 
endif;