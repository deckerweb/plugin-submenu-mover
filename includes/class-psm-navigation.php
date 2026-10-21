<?php
/**
 * Registered admin destinations, personal shortcuts and reviewable site configuration.
 *
 * Original callbacks, page registrations and capabilities remain owned by their plugins.
 * @package PluginSubmenuMover
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/trait-psm-workspace.php';
require_once __DIR__ . '/trait-psm-setup.php';

/** Builds the optional hub from the live, permission-filtered WordPress menu. */
final class DDW_PSM_Navigation {
	use DDW_PSM_Workspace;
	use DDW_PSM_Setup;
	const OPTION = 'ddw_psm_navigation';
	const HUB = 'psm-plugin-hub';
	private $mover;
	private $groups = [];
	private $pages = [];
	private $active = '';
	private $config = [];

	/**
	 * Register the admin-only navigation lifecycle.
	 * @param DDW_Plugin_Submenu_Mover $mover Existing rule controller.
	 */
	public function __construct( $mover ) {
		$this->mover = $mover;
		$this->register_workspace();
		$this->register_setup();
		add_action( 'admin_menu', [ $this, 'inventory' ], 998 );
		add_action( 'admin_menu', [ $this, 'menus' ], 1000 );
		add_action( 'admin_post_psm_navigation', [ $this, 'post' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'admin_notices', [ $this, 'switcher' ] );
		add_action( 'admin_bar_menu', [ $this, 'toolbar' ], 90 );
		add_filter( 'parent_file', [ $this, 'parent_file' ] );
		add_filter( 'submenu_file', [ $this, 'submenu_file' ] );
	}

	/**
	 * Translate interface text in the host domain.
	 * @param string $text English source text.
	 * @return string Translated text.
	 */
	private function t( $text ) { return __( $text, 'plugin-submenu-mover' ); }

	/**
	 * Read site configuration without changing dormant settings.
	 * @return array Site settings.
	 */
	public function config() {
		$value = get_option( self::OPTION, [] );
		$defaults = [ 'enabled' => false, 'groups' => [], 'baseline' => [], 'profiles' => [], 'delete_data' => false, 'toolbar_icon' => 'grid', 'structures' => [], 'menu_position' => 'default', 'toolbar_position' => 'default', 'excluded_users' => [] ];
		$validated = $this->validate( is_array( $value ) ? $value : [] );
		if ( is_wp_error( $validated ) ) { return $defaults; }
		foreach ( is_array( $value['baseline'] ?? null ) ? $value['baseline'] : [] as $id => $hash ) {
			if ( is_string( $id ) && preg_match( '/^menu-[a-f0-9]{16}$/D', $id ) && is_string( $hash ) && preg_match( '/^[a-f0-9]{64}$/D', $hash ) ) { $validated['baseline'][ $id ] = $hash; }
		}
		foreach ( is_array( $value['structures'] ?? null ) ? array_slice( $value['structures'], 0, 200, true ) : [] as $id => $rows ) {
			if ( ! is_string( $id ) || ! preg_match( '/^menu-[a-f0-9]{16}$/D', $id ) || ! is_array( $rows ) || count( $rows ) > 200 ) { continue; }
			$valid = array_filter( $rows, static function( $row ) { return is_array( $row ) && count( $row ) === 2 && isset( $row[0], $row[1] ) && is_string( $row[0] ) && is_string( $row[1] ) && strlen( $row[0] ) <= 1000 && strlen( $row[1] ) <= 200; } );
			if ( count( $valid ) === count( $rows ) ) { $validated['structures'][ $id ] = array_values( $valid ); }
		}
		return $validated;
	}

	/**
	 * Return the fixed local toolbar icon choices and Dashicons fallbacks.
	 * @return array Safe icon IDs, English labels and fallback classes.
	 */
	private function icons() {
		return [ 'grid' => [ 'Overview grid', 'dashicons-screenoptions' ], 'cards' => [ 'Plugin cards', 'dashicons-index-card' ], 'stack' => [ 'Stacked pages', 'dashicons-admin-page' ], 'plugin' => [ 'Plugin connection', 'dashicons-admin-plugins' ], 'arrows' => [ 'Move arrows', 'dashicons-randomize' ], 'dashicons' => [ 'Dashicons overview', 'dashicons-screenoptions' ] ];
	}

	/**
	 * Render a bundled vector mask with a native fallback; never accepts uploaded SVG.
	 * @param string $id Allowlisted icon identifier.
	 * @return string Escaped icon markup.
	 */
	private function icon( $id ) {
		$icons = $this->icons();
		$id = isset( $icons[ $id ] ) ? $id : 'grid';
		$vector = 'dashicons' !== $id && is_file( __DIR__ . '/../assets/toolbar-' . $id . '.svg' );
		return '<span aria-hidden="true" class="psm-toolbar-symbol psm-symbol-' . esc_attr( $id ) . ( $vector ? ' psm-vector' : '' ) . '"><span class="dashicons ' . esc_attr( $icons[ $id ][1] ) . '"></span></span>';
	}

	/**
	 * Resolve saved favorites identically for cards, sidebar and toolbar.
	 * @return array Current permitted pages keyed by stable ID.
	 */
	private function favorites() {
		$out = []; $selected = $this->selected(); $personal = $this->personal();
		foreach ( $personal['pins'] as $id ) {
			if ( isset( $this->pages[ $id ] ) && isset( $selected[ $this->pages[ $id ]['group'] ] ) && current_user_can( $this->pages[ $id ]['capability'] ) ) {
				$page = $this->pages[ $id ];
				$original = $selected[ $page['group'] ]['label'] . ' · ' . $page['label'];
				$alias = $personal['labels'][ $id ] ?? '';
				$page['context_label'] = '' !== $alias ? $alias : $original;
				$page['original_label'] = $original;
				$out[ $id ] = $page;
			}
		}
		return $out;
	}

	/**
	 * Resolve a registered menu slug to its original admin URL.
	 * @param string $slug Menu slug.
	 * @param string $parent Original parent.
	 * @return string Safe local URL, or empty for actions/external links.
	 */
	public function destination( $slug, $parent = '' ) {
		$slug = html_entity_decode( $slug, ENT_QUOTES, 'UTF-8' );
		if ( strlen( $slug ) > 1000 || preg_match( '~[:\\\\#\x00-\x20]~', $slug ) || str_contains( $slug, '..' ) || str_starts_with( $slug, '/' ) ) { return ''; }
		if ( preg_match( '~^[a-z0-9_-]+\.php(?:\?|$)~i', $slug ) ) {
			parse_str( (string) wp_parse_url( $slug, PHP_URL_QUERY ), $query );
			if ( isset( $query['action'] ) || isset( $query['_wpnonce'] ) ) { return ''; }
			return admin_url( $slug );
		}
		$hook = get_plugin_page_hookname( $slug, $parent );
		global $_registered_pages;
		if ( empty( $_registered_pages[ $hook ] ) ) { return ''; }
		// Plugin configuration post types retain their original parent query.
		if ( in_array( $parent, [ 'edit.php?post_type=acf-field-group', 'edit.php?post_type=jet-form-builder' ], true ) ) { return add_query_arg( 'page', $slug, admin_url( $parent ) ); }
		$base = in_array( $parent, [ 'options-general.php', 'tools.php', 'themes.php', 'users.php', 'plugins.php', 'upload.php', 'edit.php' ], true ) ? $parent : 'admin.php';
		return add_query_arg( 'page', $slug, admin_url( $base ) );
	}

	/**
	 * Collect third-party menus before the existing single-page mover runs.
	 * @return void
	 */
	public function inventory() {
		if ( is_network_admin() || ! current_user_can( 'read' ) ) { return; }
		global $menu, $submenu;
		$this->config = $this->config();
		$this->collect_menu_anchors();
		$core = [ 'index.php', 'edit.php', 'upload.php', 'edit.php?post_type=page', 'edit-comments.php', 'themes.php', 'plugins.php', 'users.php', 'profile.php', 'tools.php', 'options-general.php', 'separator1', 'separator2', 'separator-last', self::HUB ];
		$this->groups = []; $this->pages = [];
		foreach ( (array) $menu as $item ) {
			if ( ! isset( $item[0], $item[1], $item[2] ) || in_array( $item[2], $core, true ) || ! current_user_can( $item[1] ) || ( str_starts_with( $item[2], 'edit.php?post_type=' ) && ! in_array( $item[2], [ 'edit.php?post_type=acf-field-group', 'edit.php?post_type=jet-form-builder' ], true ) ) ) { continue; }
			$slug = (string) $item[2]; $id = 'menu-' . substr( hash( 'sha256', $slug ), 0, 16 );
			$label = trim( wp_strip_all_tags( $item[0] ) );
			$children = $submenu[ $slug ] ?? [ $item ];
			// Include the main page once even when a plugin omits its self-link.
			array_unshift( $children, $item );
			$group = [ 'icon' => isset( $item[6] ) && is_string( $item[6] ) && preg_match( '/^dashicons-[a-z0-9-]+$/D', $item[6] ) ? $item[6] : 'dashicons-admin-plugins', 'id' => $id, 'label' => $label, 'slug' => $slug, 'pages' => [], 'structure' => [] ];
			foreach ( $children as $child ) {
				if ( ! isset( $child[0], $child[1], $child[2] ) ) { continue; }
				$url = $this->destination( (string) $child[2], $slug );
				if ( ! $url ) { continue; }
				$group['structure'][] = [ (string) $child[2], (string) $child[1] ];
				if ( ! current_user_can( $child[1] ) ) { continue; }
				$page_id = $id . '-' . substr( hash( 'sha256', $child[2] ), 0, 16 );
				$page = [ 'id' => $page_id, 'group' => $id, 'label' => trim( wp_strip_all_tags( $child[0] ) ), 'slug' => $child[2], 'capability' => $child[1], 'url' => $url ];
				$group['pages'][ $page_id ] = $page; $this->pages[ $page_id ] = $page;
			}
			if ( ! $group['pages'] ) { continue; }
			$group['metadata'] = $this->metadata( $slug );
			$group['fingerprint'] = hash( 'sha256', wp_json_encode( $group['structure'] ) );
			$this->groups[ $id ] = $group;
		}
		uasort( $this->groups, static function( $a, $b ) { return strnatcasecmp( $a['label'], $b['label'] ); } );
		$this->active = $this->resolve_context();
		$this->resolve_attachment();
		$this->record_visit();
	}

	/**
	 * Identify the plugin owning a registered render callback without executing it.
	 * @param string $slug Registered top-level page.
	 * @return array Installed name and version, with an explicit unavailable fallback.
	 */
	private function metadata( $slug ) {
		global $wp_filter;
		$unknown = [ 'name' => $this->t( 'Plugin identity unavailable' ), 'version' => '' ];
		$hook = get_plugin_page_hookname( $slug, '' );
		if ( empty( $wp_filter[ $hook ] ) ) { return $unknown; }
		foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				try {
					$fn = $callback['function'];
					if ( is_string( $fn ) && str_contains( $fn, '::' ) ) { $fn = explode( '::', $fn, 2 ); }
					$reflection = is_array( $fn ) ? new ReflectionMethod( $fn[0], $fn[1] ) : ( is_object( $fn ) && ! $fn instanceof Closure ? new ReflectionMethod( $fn, '__invoke' ) : new ReflectionFunction( $fn ) );
					$file = $reflection->getFileName();
					if ( ! $file || ! str_starts_with( wp_normalize_path( $file ), wp_normalize_path( WP_PLUGIN_DIR ) . '/' ) ) { continue; }
					$relative = plugin_basename( $file ); $directory = dirname( $relative ); $root = explode( '/', $relative )[0];
					foreach ( get_plugins() as $plugin_file => $data ) { if ( $plugin_file === $relative || ( '.' !== $directory && str_starts_with( $plugin_file, $root . '/' ) ) ) { return [ 'name' => wp_strip_all_tags( $data['Name'] ), 'version' => $data['Version'] ]; } }
				} catch ( Throwable $error ) { continue; }
			}
		}
		return $unknown;
	}

	/**
	 * Match the actual request; transient action and editing parameters do not change ownership.
	 * @return string Group identifier or empty.
	 */
	private function resolve_context() {
		global $pagenow;
		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		global $_parent_pages;
		$registered_parent = $_parent_pages[ $page ] ?? '';
		foreach ( $this->groups as $id => $group ) { if ( $registered_parent && $registered_parent === $group['slug'] ) { return $id; } }
		foreach ( $this->pages as $entry ) {
			parse_str( (string) wp_parse_url( $entry['url'], PHP_URL_QUERY ), $query );
			if ( $page && ( $query['page'] ?? '' ) === $page ) { return $entry['group']; }
			if ( ! $page && basename( wp_parse_url( $entry['url'], PHP_URL_PATH ) ) === $pagenow && ! empty( $query ) ) {
				$matches = true;
				foreach ( $query as $key => $value ) { if ( ! isset( $_GET[ $key ] ) || ! is_string( $_GET[ $key ] ) || sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) !== $value ) { $matches = false; } }
				if ( $matches ) { return $entry['group']; }
			}
		}
		return '';
	}

	/**
	 * Return explicitly enabled groups that remain available to this user.
	 * @return array Registered groups.
	 */
	private function selected() {
		return array_intersect_key( $this->groups, array_filter( (array) ( $this->config['groups'] ?? [] ) ) );
	}

	/**
	 * Determine whether an enabled hub selection owns this menu group.
	 * @param string $slug Original top-level menu slug.
	 * @return bool Whether the single-page mover should leave it to the hub.
	 */
	public function owns( $slug ) {
		if ( empty( $this->config['enabled'] ) || $this->mover->original_view() ) { return false; }
		foreach ( $this->selected() as $group ) { if ( $slug === $group['slug'] ) { return true; } }
		return false;
	}

	/**
	 * Check whether a destination menu is reserved for hub consolidation.
	 * @param string $slug Original top-level slug.
	 * @return bool Whether moves into that group must be skipped.
	 */
	public function bundles( $slug ) {
		if ( ! $this->owns( $slug ) ) { return false; }
		foreach ( $this->selected() as $id => $group ) { if ( $slug === $group['slug'] ) { return 'bundle' === $this->config['groups'][ $id ]; } }
		return false;
	}

	/**
	 * Export shared settings without approvals, deletion consent or personal data.
	 * @return array Portable hub settings.
	 */
	public function portable() {
		$config = $this->config();
		unset( $config['baseline'], $config['structures'], $config['delete_data'], $config['excluded_users'] );
		return $config;
	}

	/**
	 * Return the live inventory for local diagnostics and integration consumers.
	 * @return array Permission-filtered registered destinations.
	 */
	public function destinations() { return $this->groups; }

	/**
	 * Register one hub and contextual links without re-registering original callbacks.
	 * @return void
	 */
	public function menus() {
		if ( is_network_admin() || empty( $this->config['enabled'] ) || $this->mover->original_view() || ! $this->selected() ) { return; }
		add_menu_page( $this->t( 'Plugin Hub' ), $this->t( 'Plugin Hub' ), 'read', self::HUB, [ $this, 'render' ], 'dashicons-arrow-right-alt2', 81 );
		add_submenu_page( self::HUB, $this->t( 'All plugins' ), $this->t( 'All plugins' ), 'read', self::HUB, [ $this, 'render' ] );
		global $submenu;
		$submenu[ self::HUB ][0][0] = '<span class="dashicons dashicons-screenoptions" aria-hidden="true"></span> ' . esc_html( $this->t( 'All plugins' ) );
		$submenu[ self::HUB ][0][4] = 'psm-hub-overview';
		$favorites = $this->favorites(); $last = array_key_last( $favorites );
		foreach ( $favorites as $id => $page ) {
			$submenu[ self::HUB ][] = [ '<span aria-hidden="true">★</span> ' . esc_html( $page['context_label'] ), $page['capability'], $page['url'], '', 'psm-menu-favorite' . ( $id === $last ? ' psm-favorite-last' : '' ) ];
		}
		$group = $this->selected()[ $this->attached ] ?? null;
		if ( $group ) {
			foreach ( $group['pages'] as $page ) {
				// Full original URLs keep the original page route and load hook intact.
				$submenu[ self::HUB ][] = [ ( $group['label'] === $page['label'] ? $group['label'] : $group['label'] . ' · ' . $page['label'] ), $page['capability'], $page['url'] ];
			}
		}
		else {
			// The overview needs visible entries too: WordPress hides a sole self-link.
			foreach ( $this->selected() as $selected ) {
				$page = reset( $selected['pages'] );
				$submenu[ self::HUB ][] = [ $selected['label'], $page['capability'], $page['url'] ];
			}
		}
		if ( $group ) { global $menu; foreach ( $menu as &$item ) { if ( self::HUB === $item[2] ) { $item[4] = trim( ( $item[4] ?? '' ) . ' psm-attached-menu' ); break; } } unset( $item ); }
		// Consolidation is opt-in and leaves every original registration and child array intact.
		foreach ( $this->selected() as $id => $selected ) {
			if ( 'bundle' === $this->config['groups'][ $id ] && $this->unchanged( $selected ) ) { remove_menu_page( $selected['slug'] ); }
		}
	}

	/**
	 * Check the saved structural baseline for the current role capabilities.
	 * @param array $group Current group.
	 * @return bool Whether the accepted menu structure still matches.
	 */
	private function unchanged( $group ) {
		return ( $this->config['baseline'][ $group['id'] ] ?? '' ) === $group['fingerprint'];
	}

	/**
	 * Set visual parent only, preserving the original registered parent.
	 * @param string $parent WordPress parent slug.
	 * @return string Visual parent slug.
	 */
	public function parent_file( $parent ) {
		if ( empty( $this->config['enabled'] ) || ! isset( $this->selected()[ $this->active ] ) || $this->mover->original_view() ) { return $parent; }
		// Core resolves the registered parent again after this filter. Keep the hub
		// visually expanded without changing registrations used by foreign callbacks.
		global $menu;
		foreach ( $menu as &$item ) {
			if ( self::HUB === $item[2] ) {
				$item[4] = trim( ( $item[4] ?? '' ) . ' wp-has-current-submenu wp-menu-open' );
				break;
			}
		}
		unset( $item );
		return self::HUB;
	}

	/**
	 * Set the selected contextual link to its actual original URL.
	 * @param string|null $file Selected submenu slug.
	 * @return string|null Selected submenu URL.
	 */
	public function submenu_file( $file ) {
		if ( $this->parent_file( '' ) !== self::HUB ) { return $file; }
		foreach ( $this->groups[ $this->active ]['pages'] as $page ) {
			if ( isset( $_GET['page'] ) && is_string( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === $page['slug'] ) { return $page['url']; }
		}
		return $file;
	}

	/**
	 * Enqueue the small context bridge; scope presentation assets to navigation screens.
	 * @param string $hook Current screen hook.
	 * @return void
	 */
	public function assets( $hook ) {
		if ( is_network_admin() ) { return; }
		$this->workspace_assets();
		if ( ! is_network_admin() && ! empty( $this->config['enabled'] ) && $this->selected() && ! $this->mover->original_view() ) { wp_enqueue_style( 'psm-menu', plugins_url( '../assets/menu.css', __FILE__ ), [], DDW_Plugin_Submenu_Mover::VERSION ); }
		$screen = get_current_screen();
		if ( $screen && str_contains( $screen->id, 'plugin-submenu-mover' ) ) { wp_enqueue_style( 'psm-menu', plugins_url( '../assets/menu.css', __FILE__ ), [], DDW_Plugin_Submenu_Mover::VERSION ); wp_enqueue_script( 'psm-setup', plugins_url( '../assets/setup.js', __FILE__ ), [], DDW_Plugin_Submenu_Mover::VERSION, true ); wp_localize_script( 'psm-setup', 'psmSetupLabels', [ 'summary' => $this->t( '%1$d selected · %2$d available · %3$d visible · %4$d marked' ), 'template' => $this->t( '%d matching groups marked. Navigation choices are unchanged.' ), 'dirty' => $this->t( 'Unsaved changes. Preview and apply to update navigation.' ), 'clean' => $this->t( 'Navigation choices match the saved configuration.' ) ] ); wp_enqueue_style( 'psm-settings', plugins_url( '../assets/settings.css', __FILE__ ), [ 'psm-admin' ], DDW_Plugin_Submenu_Mover::VERSION ); wp_enqueue_script( 'psm-user-picker', plugins_url( '../assets/user-picker.js', __FILE__ ), [ 'psm-setup' ], DDW_Plugin_Submenu_Mover::VERSION, true ); wp_localize_script( 'psm-user-picker', 'psmUserPicker', [ 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'psm_user_search' ), 'loading' => $this->t( 'Finding users…' ), 'empty' => $this->t( 'No matching users.' ), 'error' => $this->t( 'Could not load users. Try searching again.' ), 'remove' => $this->t( 'Remove user: %s' ), 'selected' => $this->t( '%d users selected' ), 'limit' => $this->t( 'Select up to 200 users.' ) ] ); }
		if ( ! $screen || ( false === strpos( $screen->id, 'plugin-submenu-mover' ) && false === strpos( $screen->id, self::HUB ) && ! isset( $this->selected()[ $this->attached ] ) ) ) { return; }
		if ( str_contains( $screen->id, self::HUB ) ) {
			wp_enqueue_style( 'psm-admin', plugins_url( '../assets/admin.css', __FILE__ ), [], DDW_Plugin_Submenu_Mover::VERSION );
			wp_enqueue_script( 'psm-admin', plugins_url( '../assets/admin.js', __FILE__ ), [], DDW_Plugin_Submenu_Mover::VERSION, true );
		}
		wp_enqueue_style( 'psm-navigation', plugins_url( '../assets/navigation.css', __FILE__ ), [], DDW_Plugin_Submenu_Mover::VERSION );
		wp_enqueue_script( 'psm-navigation', plugins_url( '../assets/navigation.js', __FILE__ ), [], DDW_Plugin_Submenu_Mover::VERSION, true );
		wp_localize_script( 'psm-navigation', 'psmNavigationLabels', [ 'matches' => $this->t( 'Matching plugin groups: %d' ), 'pinSaved' => $this->t( 'Favorites updated.' ), 'pinError' => $this->t( 'Could not save favorites. Please try again.' ) ] );
	}

	/**
	 * Provide attached-group controls while retaining the actual page context.
	 * @return void
	 */
	public function switcher() {
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], [ self::HUB, 'plugin-submenu-mover' ], true ) ) { return; }
		$this->workspace_bar( true );
	}

	/**
	 * Read per-user, per-site preferences.
	 * @return array Personal preferences.
	 */
	private function personal() {
		$value = get_user_meta( get_current_user_id(), 'ddw_psm_personal_' . get_current_blog_id(), true );
		$value = is_array( $value ) ? $value : [];
		$ids = static function( $raw, $limit ) { return array_slice( array_values( array_filter( is_array( $raw ) ? $raw : [], static function( $id ) { return is_string( $id ) && preg_match( '/^menu-[a-f0-9]{16}-[a-f0-9]{16}$/D', $id ); } ) ), 0, $limit ); };
		$labels = [];
		foreach ( is_array( $value['labels'] ?? null ) ? array_slice( $value['labels'], 0, 30, true ) : [] as $id => $label ) {
			if ( is_string( $id ) && preg_match( '/^menu-[a-f0-9]{16}-[a-f0-9]{16}$/D', $id ) && is_string( $label ) ) { preg_match( '/^.{0,60}/us', sanitize_text_field( $label ), $match ); $labels[ $id ] = $match[0] ?? ''; }
		}
		return [ 'bar_hidden' => true === ( $value['bar_hidden'] ?? false ), 'pins' => $ids( $value['pins'] ?? [], 30 ), 'recent' => $ids( $value['recent'] ?? [], 12 ), 'history' => true === ( $value['history'] ?? false ), 'view' => is_string( $value['view'] ?? null ) ? $value['view'] : 'all', 'labels' => $labels, 'group' => is_string( $value['group'] ?? null ) && preg_match( '/^menu-[a-f0-9]{16}$/D', $value['group'] ) ? $value['group'] : '' ];
	}

	/**
	 * Render a verified destination with a personal pin control.
	 * @param array $page Registered destination.
	 * @param bool $context Whether to include its plugin name outside a card.
	 * @return void
	 */
	private function page_link( $page, $context = false ) {
		$pinned = in_array( $page['id'], $this->personal()['pins'], true );
		$full_label = $this->groups[ $page['group'] ]['label'] . ' · ' . $page['label'];
		echo '<li data-psm-page="' . esc_attr( $page['id'] ) . '"><a data-psm-destination title="' . esc_attr( $full_label ) . '" data-psm-keywords="' . esc_attr( implode( ' ', $this->keywords( $full_label ) ) ) . '" data-psm-label="' . esc_attr( $full_label ) . '" href="' . esc_url( $page['url'] ) . '">' . esc_html( $context ? ( $page['context_label'] ?? $full_label ) : $page['label'] ) . '</a> ';
		$this->form_start( 'pin', 'personal' );
		echo '<input type="hidden" name="page_id" value="' . esc_attr( $page['id'] ) . '"><button class="button-link psm-pin" aria-pressed="' . ( $pinned ? 'true' : 'false' ) . '" aria-label="' . esc_attr( $this->t( 'Toggle favorite' ) . ': ' . $full_label ) . '"><span aria-hidden="true">' . ( $pinned ? '★' : '☆' ) . '</span></button></form></li>';
	}

	/**
	 * Render cards, local destination search and personal views.
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'read' ) ) { return; }
		$personal = $this->personal();
		$selected = $this->selected();
		$profile = $this->config['profiles'][ $personal['view'] ] ?? null;
		if ( $profile ) { $selected = array_intersect_key( $selected, array_fill_keys( $profile['groups'], true ) ); }
		echo '<div class="wrap psm-navigation"><header class="psm-hub-header"><img src="' . esc_url( plugins_url( '../assets/icon-56.png', __FILE__ ) ) . '" srcset="' . esc_url( plugins_url( 'assets/icon-112.png', PSM_PLUGIN_FILE ) ) . ' 2x" width="56" height="56" alt=""><div><h1>' . esc_html( $this->t( 'Plugin Hub' ) ) . '</h1><p>' . esc_html( $this->t( 'Your plugins. Shorter paths.' ) ) . '</p></div>';
		if ( current_user_can( 'manage_options' ) ) { echo '<a class="button psm-settings-link" href="' . esc_url( admin_url( 'tools.php?page=plugin-submenu-mover' ) ) . '"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span> ' . esc_html( $this->t( 'Settings' ) ) . '</a>'; }
		echo '</header><hr class="wp-header-end">';
		if ( empty( $this->config['enabled'] ) ) { echo '<p>' . esc_html( $this->t( 'The hub is disabled.' ) ) . '</p></div>'; return; }
		$this->workspace_bar();
		echo '<div class="psm-hub-tools"><div class="psm-search-field"><label for="psm-search">' . esc_html( $this->t( 'Find a page' ) ) . '</label><div class="psm-search-control"><span class="dashicons dashicons-search" aria-hidden="true"></span><input id="psm-search" type="search" data-psm-search aria-describedby="psm-search-help" placeholder="' . esc_attr( $this->t( 'Plugin or page name…' ) ) . '"><button type="button" class="button-link" data-psm-clear hidden>' . esc_html( $this->t( 'Clear search' ) ) . '</button></div><span id="psm-search-help" class="description">' . esc_html( $this->t( 'Enter opens the first matching page. Escape clears the search.' ) ) . '</span></div><p class="psm-group-count">' . esc_html( sprintf( $this->t( 'Plugin groups: %d' ), count( $selected ) ) ) . '</p></div>';
		echo '<p data-psm-search-status role="status" class="screen-reader-text"></p><p data-psm-pin-status role="status" class="psm-feedback"></p>';
		$favorites = $this->favorites();
		echo '<section id="psm-hub-favorites" class="psm-shortcuts" data-psm-favorites aria-labelledby="psm-favorites-title"><h2 id="psm-favorites-title"><span aria-hidden="true">★</span> ' . esc_html( $this->t( 'Favorites' ) ) . '</h2><p data-psm-favorites-empty' . ( $favorites ? ' hidden' : '' ) . '>' . esc_html( $this->t( 'Star a page to keep it here and in the hub menu.' ) ) . '</p><ul class="psm-pins">';
		foreach ( $favorites as $page ) { $this->page_link( $page, true ); }
		echo '</ul></section>';
		if ( $personal['history'] && $personal['recent'] ) {
			echo '<section class="psm-shortcuts"><h2>' . esc_html( $this->t( 'Recently visited' ) ) . '</h2><ul class="psm-pins">';
			foreach ( $personal['recent'] as $id ) { if ( isset( $this->pages[ $id ] ) && isset( $selected[ $this->pages[ $id ]['group'] ] ) ) { $this->page_link( $this->pages[ $id ], true ); } }
			echo '</ul>'; $this->form_start( 'clear-history', 'personal' ); echo '<button class="button">' . esc_html( $this->t( 'Clear history' ) ) . '</button></form></section>';
		}
		echo '<div class="psm-section-heading"><h2>' . esc_html( $profile ? $profile['name'] : $this->t( 'All plugins' ) ) . '</h2><p>' . esc_html( $this->t( 'Open a plugin menu or go straight to a page.' ) ) . '</p></div><div class="psm-hub-grid">';
		foreach ( $selected as $group ) {
			$entry = reset( $group['pages'] );
			$tone = hexdec( substr( $group['id'], -2 ) ) % 5;
			echo '<section class="psm-hub-card psm-tone-' . (int) $tone . '" data-psm-card data-psm-group="' . esc_attr( $group['label'] ) . '"><header class="psm-card-heading"><span class="psm-card-icon dashicons ' . esc_attr( $group['icon'] ) . '" aria-hidden="true"></span><div><h3><a href="' . esc_url( $entry['url'] ) . '">' . esc_html( $group['label'] ) . '</a></h3><span class="psm-page-count">' . esc_html( sprintf( $this->t( 'Pages: %d' ), count( $group['pages'] ) ) ) . '</span></div></header><ul class="psm-page-list">';
			$i = 0;
			foreach ( $group['pages'] as $page ) {
				if ( 3 === $i ) { echo '</ul><details><summary>' . esc_html( $this->t( 'All pages' ) ) . '<span class="psm-more-count"> +' . (int) ( count( $group['pages'] ) - 3 ) . '</span></summary><ul class="psm-page-list">'; }
				$this->page_link( $page ); $i++;
			}
			echo '</ul>' . ( $i > 3 ? '</details>' : '' ) . '<footer class="psm-card-footer"><a class="button" href="' . esc_url( $entry['url'] ) . '" aria-label="' . esc_attr( sprintf( $this->t( 'Open plugin menu: %s' ), $group['label'] ) ) . '">' . esc_html( $this->t( 'Open plugin menu' ) ) . '<span aria-hidden="true"> →</span></a></footer></section>';
		}
		echo '</div><div class="psm-empty" data-psm-empty hidden><span class="dashicons dashicons-search" aria-hidden="true"></span><h2>' . esc_html( $this->t( 'No matching pages' ) ) . '</h2><p>' . esc_html( $this->t( 'Try another plugin or page name.' ) ) . '</p><button type="button" class="button" data-psm-reset>' . esc_html( $this->t( 'Clear search' ) ) . '</button></div>';
		if ( ! $selected ) { echo '<p>' . esc_html( $this->t( 'No plugins in this workspace. Choose All plugins in Personal view.' ) ) . '</p>'; }
		echo '<details class="psm-preferences"><summary>' . esc_html( $this->t( 'Personal view' ) ) . '</summary>';
		$this->form_start( 'preferences', 'personal' );
		echo '<p><label>' . esc_html( $this->t( 'Workspace' ) ) . ' <select name="view"><option value="all">' . esc_html( $this->t( 'All plugins' ) ) . '</option>';
		foreach ( $this->config['profiles'] as $id => $view ) { echo '<option value="' . esc_attr( $id ) . '" ' . selected( $personal['view'], $id, false ) . '>' . esc_html( $view['name'] ) . '</option>'; }
		echo '</select></label> <label><input type="checkbox" name="history" ' . checked( $personal['history'], true, false ) . '> ' . esc_html( $this->t( 'Remember recently visited pages on this site' ) ) . '</label> <button class="button">' . esc_html( $this->t( 'Save preferences' ) ) . '</button></p></form></details>';
		$this->personal_tools();
		$this->mover->footer(); echo '</div>';
	}

	/**
	 * Record only an opted-in registered page ID, with no query values or timestamps.
	 * @return void
	 */
	private function record_visit() {
		if ( ! $this->active || empty( $this->config['enabled'] ) || ! isset( $this->selected()[ $this->active ] ) ) { return; }
		$p = $this->personal();
		if ( ! $p['history'] || ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) !== 'GET' ) { return; }
		foreach ( $this->groups[ $this->active ]['pages'] as $page ) {
			if ( isset( $_GET['page'] ) && is_string( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === $page['slug'] ) {
				$recent = array_slice( array_values( array_unique( array_merge( [ $page['id'] ], $p['recent'] ) ) ), 0, 12 );
				if ( $recent !== $p['recent'] ) { $p['recent'] = $recent; update_user_meta( get_current_user_id(), 'ddw_psm_personal_' . get_current_blog_id(), $p ); }
				return;
			}
		}
	}

	/**
	 * Add permitted favorites to the native toolbar without custom scripts.
	 * @param WP_Admin_Bar $bar Toolbar instance.
	 * @return void
	 */
	public function toolbar( $bar ) {
		if ( ! is_admin() || is_network_admin() || empty( $this->config['enabled'] ) || ! $this->selected() || $this->mover->original_view() ) { return; }
		$bar->add_node( [ 'id' => 'psm-hub', 'title' => '<span class="ab-icon">' . $this->icon( $this->config['toolbar_icon'] ) . '</span><span class="ab-label">' . esc_html( $this->t( 'Plugin Hub' ) ) . '</span>', 'href' => admin_url( 'admin.php?page=' . self::HUB ), 'meta' => [ 'title' => $this->t( 'Plugin Hub' ) ] ] );
		$bar->add_node( [ 'id' => 'psm-overview', 'parent' => 'psm-hub', 'title' => esc_html( $this->t( 'All plugins' ) ), 'href' => admin_url( 'admin.php?page=' . self::HUB ) ] );
		if ( isset( $this->selected()[ $this->attached ] ) ) {
			$group = $this->groups[ $this->attached ];
			$bar->add_group( [ 'id' => 'psm-current', 'parent' => 'psm-hub', 'meta' => [ 'class' => 'psm-toolbar-section' ] ] );
			$bar->add_node( [ 'id' => 'psm-current-label', 'parent' => 'psm-current', 'title' => '<span class="psm-toolbar-heading">' . esc_html( $group['label'] ) . '</span>' ] );
			foreach ( $group['pages'] as $page ) { $bar->add_node( [ 'id' => 'psm-current-' . $page['id'], 'parent' => 'psm-current', 'title' => esc_html( $page['label'] ), 'href' => $page['url'] ] ); }
		}
		$favorites = $this->favorites();
		if ( $favorites ) {
			$bar->add_group( [ 'id' => 'psm-favorites', 'parent' => 'psm-hub', 'meta' => [ 'class' => 'psm-toolbar-section' ] ] );
			$bar->add_node( [ 'id' => 'psm-favorites-label', 'parent' => 'psm-favorites', 'title' => '<span class="psm-toolbar-heading">' . esc_html( $this->t( 'Favorites' ) ) . '</span>' ] );
			foreach ( $favorites as $id => $page ) { $bar->add_node( [ 'id' => 'psm-' . $id, 'parent' => 'psm-favorites', 'title' => esc_html( $page['context_label'] ), 'href' => $page['url'], 'meta' => [ 'title' => $page['original_label'] ] ] ); }
		}
	}

	/**
	 * Begin a POST form with a scoped nonce.
	 * @param string $action Requested operation.
	 * @param string $scope Permission/nonce scope.
	 * @return void
	 */
	private function form_start( $action, $scope = 'site' ) {
		echo '<form class="psm-inline psm-form-' . esc_attr( $action ) . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="psm_navigation"><input type="hidden" name="psm_navigation" value="' . esc_attr( $action ) . '"><input type="hidden" name="psm_scope" value="' . esc_attr( $scope ) . '">';
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( wp_create_nonce( 'psm_navigation_' . $scope ) ) . '">';
	}

	/**
	 * Normalize portable navigation settings without accepting executable destinations.
	 * @param mixed $raw Untrusted settings.
	 * @return array|WP_Error Validated settings or error.
	 */
	public function validate( $raw ) {
		if ( ! is_array( $raw ) || count( $raw ) > 10 || array_diff( array_keys( $raw ), [ 'enabled', 'groups', 'baseline', 'profiles', 'delete_data', 'toolbar_icon', 'structures', 'menu_position', 'toolbar_position', 'excluded_users' ] ) ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); }
		if ( isset( $raw['excluded_users'] ) && ( ! is_array( $raw['excluded_users'] ) || count( $raw['excluded_users'] ) > 200 || array_filter( $raw['excluded_users'], static function( $id ) { return ! is_int( $id ) || $id < 1; } ) ) ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); }
		if ( isset( $raw['structures'] ) && ! is_array( $raw['structures'] ) ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); }
		foreach ( [ 'enabled', 'delete_data' ] as $flag ) { if ( isset( $raw[ $flag ] ) && ! is_bool( $raw[ $flag ] ) ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); } }
		$out = [ 'enabled' => ! empty( $raw['enabled'] ), 'delete_data' => ! empty( $raw['delete_data'] ), 'groups' => [], 'baseline' => [], 'profiles' => [], 'toolbar_icon' => 'grid', 'structures' => [], 'menu_position' => 'default', 'toolbar_position' => 'default', 'excluded_users' => [] ];
		if ( isset( $raw['toolbar_icon'] ) && ( ! is_string( $raw['toolbar_icon'] ) || ! isset( $this->icons()[ $raw['toolbar_icon'] ] ) ) ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); }
		$out['excluded_users'] = array_values( array_unique( $raw['excluded_users'] ?? [] ) );
		sort( $out['excluded_users'] );
		$out['toolbar_icon'] = $raw['toolbar_icon'] ?? 'grid';
		foreach ( [ 'menu_position', 'toolbar_position' ] as $key ) {
			if ( ! $this->valid_position( $raw[ $key ] ?? 'default', 'toolbar_position' === $key ) ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); }
			$out[ $key ] = $raw[ $key ] ?? 'default';
		}
		if ( ! is_array( $raw['groups'] ?? [] ) || count( $raw['groups'] ?? [] ) > 200 ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); }
		foreach ( $raw['groups'] ?? [] as $id => $mode ) {
			if ( ! preg_match( '/^menu-[a-f0-9]{16}$/D', (string) $id ) || ! in_array( $mode, [ '', 'link', 'bundle' ], true ) ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); }
			$out['groups'][ $id ] = $mode;
		}
		if ( ! is_array( $raw['profiles'] ?? [] ) || count( $raw['profiles'] ?? [] ) > 12 ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); }
		foreach ( $raw['profiles'] ?? [] as $id => $profile ) {
			if ( ! is_string( $id ) || ! preg_match( '/^profile-[a-f0-9]{12}$/D', $id ) || ! is_array( $profile ) || ! is_string( $profile['name'] ?? null ) || strlen( $profile['name'] ) > 80 || ! is_array( $profile['groups'] ?? null ) || count( $profile['groups'] ) > 200 ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); }
			foreach ( $profile['groups'] as $group_id ) { if ( ! is_string( $group_id ) || ! preg_match( '/^menu-[a-f0-9]{16}$/D', $group_id ) ) { return new WP_Error( 'psm_navigation', $this->t( 'Invalid configuration.' ) ); } }
			$out['profiles'][ $id ] = [ 'name' => sanitize_text_field( $profile['name'] ), 'groups' => array_values( array_unique( $profile['groups'] ) ) ];
		}
		// Never trust imported structural approval: it is local to the installed menu.
		return $out;
	}

	/**
	 * Stage a validated change with an optimistic concurrency guard.
	 * @param array $rules Proposed mover rules.
	 * @param array $navigation Proposed hub settings.
	 * @return void
	 */
	public function stage( $rules, $navigation ) {
		if ( is_wp_error( $this->mover->validate_rules( $rules ) ) || is_wp_error( $this->validate( $navigation ) ) ) { wp_die( esc_html( $this->t( 'Invalid configuration.' ) ), '', [ 'response' => 400 ] ); }
		$state = [ 'rules' => $rules, 'navigation' => $navigation, 'before' => $this->snapshot(), 'expires' => time() + HOUR_IN_SECONDS ];
		update_user_meta( get_current_user_id(), 'ddw_psm_pending_' . get_current_blog_id(), $state );
	}

	/**
	 * Snapshot shared options for review, conflict detection and undo.
	 * @return array Shared configuration.
	 */
	private function snapshot() { return [ 'rules' => $this->mover->rules(), 'navigation' => $this->config() ]; }

	/**
	 * Intercept rule saves/imports and handle nonce-protected hub/personal actions.
	 * @return void
	 */
	public function post() {
		if ( ! $this->mover->user_allowed() ) { wp_die( esc_html( $this->t( 'Access denied.' ) ), '', [ 'response' => 403 ] ); }
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { return; }
		$navigation_action = $_POST['psm_navigation'] ?? '';
		if ( ! is_string( $navigation_action ) || '' === $navigation_action ) { return; }
		$scope = isset( $_POST['psm_scope'] ) && 'personal' === $_POST['psm_scope'] ? 'personal' : 'site';
		if ( is_network_admin() || ! current_user_can( 'personal' === $scope ? 'read' : 'manage_options' ) ) { wp_die( esc_html( $this->t( 'Access denied.' ) ), '', [ 'response' => 403 ] ); }
		check_admin_referer( 'psm_navigation_' . $scope );
		$action = wp_unslash( $navigation_action ); $key = get_current_blog_id();
		if ( 'personal' === $scope ) {
			if ( ! in_array( $action, [ 'pin', 'preferences', 'clear-history', 'detach', 'reset-favorites', 'reset-view', 'set-view', 'favorite-order', 'favorite-labels', 'bar-visibility' ], true ) ) { wp_die( esc_html( $this->t( 'Invalid configuration.' ) ) ); }
			// admin_init runs before admin_menu. Build the live inventory in an isolated menu pass.
			$this->prepare_post_inventory();
			$this->workspace_action( $action );
			if ( 'pin' !== $action ) {
				$p = $this->personal();
				if ( 'clear-history' === $action ) { $p['recent'] = []; } else {
					$p['history'] = isset( $_POST['history'] );
					$p['view'] = isset( $_POST['view'] ) && is_string( $_POST['view'] ) && ( 'all' === $_POST['view'] || isset( $this->config['profiles'][ $_POST['view'] ] ) ) ? $_POST['view'] : 'all';
					if ( ! $p['history'] ) { $p['recent'] = []; }
				}
				update_user_meta( get_current_user_id(), 'ddw_psm_personal_' . $key, $p ); $this->redirect( true );
			}
			$id = isset( $_POST['page_id'] ) && is_string( $_POST['page_id'] ) ? wp_unslash( $_POST['page_id'] ) : '';
			if ( ! isset( $this->pages[ $id ] ) || ! isset( $this->selected()[ $this->pages[ $id ]['group'] ] ) ) { wp_die( esc_html( $this->t( 'Page unavailable.' ) ), '', [ 'response' => 403 ] ); }
			$p = $this->personal(); $p['pins'] = in_array( $id, $p['pins'], true ) ? array_values( array_diff( $p['pins'], [ $id ] ) ) : array_slice( array_merge( $p['pins'], [ $id ] ), -30 );
			update_user_meta( get_current_user_id(), 'ddw_psm_personal_' . $key, $p );
			if ( isset( $_POST['psm_inline'] ) && '1' === $_POST['psm_inline'] ) {
				ob_start(); $this->personal_tools(); $personal_controls = ob_get_clean();
				wp_send_json_success( [ 'pins' => $p['pins'], 'favorites' => array_values( $this->favorites() ), 'commands' => isset( $_POST['psm_commands'] ) && '1' === $_POST['psm_commands'] ? $this->command_data() : [], 'personal_controls' => $personal_controls ] );
			}
			$this->redirect( true );
		}
		if ( 'remove-profile' === $action ) {
			$nav = $this->config(); $id = isset( $_POST['profile_id'] ) && is_string( $_POST['profile_id'] ) ? wp_unslash( $_POST['profile_id'] ) : '';
			if ( ! isset( $nav['profiles'][ $id ] ) ) { wp_die( esc_html( $this->t( 'Invalid configuration.' ) ) ); }
			unset( $nav['profiles'][ $id ] ); $this->stage( $this->mover->rules(), $nav );
		} elseif ( 'profile' === $action ) {
			$nav = $this->config();
			$name = isset( $_POST['profile_name'] ) && is_string( $_POST['profile_name'] ) ? sanitize_text_field( wp_unslash( $_POST['profile_name'] ) ) : '';
			$ids = isset( $_POST['profile_groups'] ) && is_array( $_POST['profile_groups'] ) ? wp_unslash( $_POST['profile_groups'] ) : [];
			$ids = array_values( array_intersect( array_keys( array_filter( $nav['groups'] ) ), array_filter( $ids, 'is_string' ) ) );
			if ( ! $name || strlen( $name ) > 80 || ( count( $nav['profiles'] ) >= 12 && ! isset( $nav['profiles'][ 'profile-' . substr( hash( 'sha256', $name ), 0, 12 ) ] ) ) || ! $ids ) { wp_die( esc_html( $this->t( 'Invalid configuration.' ) ) ); }
			$nav['profiles'][ 'profile-' . substr( hash( 'sha256', $name ), 0, 12 ) ] = [ 'name' => $name, 'groups' => $ids ];
			$this->stage( $this->mover->rules(), $nav );
		} elseif ( 'review-hub' === $action ) {
			$this->prepare_post_inventory();
			$value = $this->validate( [ 'enabled' => isset( $_POST['enabled'] ), 'groups' => isset( $_POST['groups'] ) && is_array( $_POST['groups'] ) ? wp_unslash( $_POST['groups'] ) : [], 'delete_data' => isset( $_POST['delete_data'] ) ] );
			if ( is_wp_error( $value ) ) { wp_die( esc_html( $value->get_error_message() ) ); }
			$icon = $_POST['toolbar_icon'] ?? $this->config()['toolbar_icon'];
			if ( ! is_string( $icon ) || ! isset( $this->icons()[ $icon ] ) ) { wp_die( esc_html( $this->t( 'Invalid configuration.' ) ) ); }
			$value['toolbar_icon'] = $icon;
			foreach ( [ 'menu_position', 'toolbar_position' ] as $position ) {
				$token = isset( $_POST[ $position ] ) && is_string( $_POST[ $position ] ) ? wp_unslash( $_POST[ $position ] ) : ( $_POST[ $position ] ?? $this->config()[ $position ] );
				if ( ! $this->valid_position( $token, 'toolbar_position' === $position ) ) { wp_die( esc_html( $this->t( 'Invalid configuration.' ) ) ); }
				$value[ $position ] = $token;
			}
			$value['excluded_users'] = $this->config()['excluded_users'];
			if ( isset( $_POST['excluded_users'] ) ) {
				$raw = $_POST['excluded_users'];
				if ( ! is_string( $raw ) || strlen( $raw ) > 4200 || ! preg_match( '/^[0-9,\s]*$/D', $raw ) ) { wp_die( esc_html( $this->t( 'Enter valid user IDs separated by commas.' ) ), '', [ 'response' => 400 ] ); }
				$ids = preg_split( '/[,\s]+/', trim( $raw ), -1, PREG_SPLIT_NO_EMPTY );
				if ( count( $ids ) > 200 ) { wp_die( esc_html( $this->t( 'Enter valid user IDs separated by commas.' ) ), '', [ 'response' => 400 ] ); }
				$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
				foreach ( $ids as $id ) { if ( $id < 1 || ( ! in_array( $id, $this->config()['excluded_users'], true ) && ! is_user_member_of_blog( $id ) ) ) { wp_die( esc_html( $this->t( 'Choose users who belong to this site.' ) ), '', [ 'response' => 400 ] ); } }
				if ( in_array( get_current_user_id(), $ids, true ) ) { wp_die( esc_html( $this->t( 'Your own account cannot be excluded.' ) ), '', [ 'response' => 400 ] ); }
				sort( $ids ); $value['excluded_users'] = $ids;
			}
			$value['profiles'] = $this->config()['profiles'];
			$value['structures'] = $this->config()['structures'];
			$value['baseline'] = $this->config()['baseline'];
			foreach ( $this->groups as $id => $group ) { if ( ! $this->unchanged( $group ) ) { unset( $value['baseline'][ $id ] ); } }
			$this->stage( $this->mover->rules(), $value );
		} elseif ( 'apply' === $action ) {
			$pending = get_user_meta( get_current_user_id(), 'ddw_psm_pending_' . $key, true );
			if ( ! is_array( $pending ) || $pending['expires'] < time() || $pending['before'] !== $this->snapshot() ) { wp_die( esc_html( $this->t( 'Configuration changed. Review again.' ) ), '', [ 'response' => 409 ] ); }
			if ( isset( $_POST['rule_selection'] ) ) {
				$ids = isset( $_POST['apply_rules'] ) && is_array( $_POST['apply_rules'] ) ? array_filter( wp_unslash( $_POST['apply_rules'] ), 'is_string' ) : [];
				foreach ( $pending['rules'] as $id => $rule ) { if ( ! in_array( $id, $ids, true ) ) { if ( isset( $pending['before']['rules'][ $id ] ) ) { $pending['rules'][ $id ] = $pending['before']['rules'][ $id ]; } else { unset( $pending['rules'][ $id ] ); } } }
			}
			$this->prepare_post_inventory();
			foreach ( $this->groups as $id => $group ) { if ( ! empty( $pending['navigation']['groups'][ $id ] ) && ! isset( $pending['navigation']['baseline'][ $id ] ) ) { $pending['navigation']['baseline'][ $id ] = $group['fingerprint']; } }
			foreach ( $this->groups as $id => $group ) { if ( ! empty( $pending['navigation']['groups'][ $id ] ) && ( $pending['navigation']['baseline'][ $id ] ?? '' ) === $group['fingerprint'] && count( $group['structure'] ) <= 200 ) { $pending['navigation']['structures'][ $id ] = $group['structure']; } }
			update_option( 'ddw_psm_rules', $pending['rules'], false ); update_option( self::OPTION, $pending['navigation'], false );
			update_user_meta( get_current_user_id(), 'ddw_psm_undo_' . $key, [ 'before' => $pending['before'], 'after' => $this->snapshot() ] );
			delete_user_meta( get_current_user_id(), 'ddw_psm_pending_' . $key );
		} elseif ( 'cancel' === $action ) { delete_user_meta( get_current_user_id(), 'ddw_psm_pending_' . $key );
		} elseif ( 'undo' === $action ) {
			$undo = get_user_meta( get_current_user_id(), 'ddw_psm_undo_' . $key, true );
			if ( ! is_array( $undo ) || $undo['after'] !== $this->snapshot() ) { wp_die( esc_html( $this->t( 'Configuration changed. Review again.' ) ), '', [ 'response' => 409 ] ); }
			update_option( 'ddw_psm_rules', $undo['before']['rules'], false ); update_option( self::OPTION, $undo['before']['navigation'], false ); delete_user_meta( get_current_user_id(), 'ddw_psm_undo_' . $key );
		} else { return; }
		$this->redirect( false, in_array( $action, [ 'apply', 'undo', 'cancel' ], true ) ? $action : '' );
	}

	/**
	 * Register menu data for an admin-post request without executing movers twice.
	 * @return void
	 */
	private function prepare_post_inventory() {
		if ( ! did_action( 'admin_menu' ) ) { global $menu, $submenu, $admin_page_hooks, $_registered_pages, $_parent_pages, $_wp_submenu_nopriv; require_once ABSPATH . 'wp-admin/menu.php'; }
	}

	/**
	 * Redirect after a successful POST to avoid duplicate submissions.
	 * @param bool $hub Whether to return to the hub.
	 * @param string $notice Validated successful operation name.
	 * @return never
	 */
	private function redirect( $hub = false, $notice = '' ) {
		$url = admin_url( $hub ? 'admin.php?page=' . self::HUB : 'tools.php?page=plugin-submenu-mover' );
		if ( $notice ) { $url = add_query_arg( 'psm_notice', $notice, $url ); }
		$url = add_query_arg( 'psm_context', $this->attached ?: 'none', $url );
		wp_safe_redirect( $url ); exit;
	}

	/**
	 * Describe a proposed configuration using readable labels and explicit dormant state.
	 * @param array $pending Validated pending snapshot.
	 * @return array Change rows.
	 */
	public function changes( $pending ) {
		$rows = []; $labels = $this->mover->diagnostic_data()['integrations'];
		foreach ( array_unique( array_merge( array_keys( $pending['before']['rules'] ), array_keys( $pending['rules'] ) ) ) as $id ) {
			$old = $pending['before']['rules'][ $id ] ?? null; $new = $pending['rules'][ $id ] ?? null;
			if ( $old === $new ) { continue; }
			$format = function( $rule ) { return null === $rule ? $this->t( 'Recommended' ) : ( $rule['disabled'] ? $this->t( 'Original menu' ) : ( $rule['parent'] ?: $this->t( 'Recommended' ) ) ); };
			$rows[] = [ 'label' => $labels[ $id ]['label'] ?? $id . ' · ' . $this->t( 'Not currently available; rule retained' ), 'before' => $format( $old ), 'after' => $format( $new ) ];
		}
		$placement_defaults = [ 'menu_position' => 'default', 'toolbar_position' => 'default', 'excluded_users' => [] ];
		$before = array_replace( $placement_defaults, $pending['before']['navigation'] ); $after = array_replace( $placement_defaults, $pending['navigation'] );
		foreach ( [ 'menu_position' => 'Admin menu position', 'toolbar_position' => 'Toolbar position' ] as $key => $label ) {
			if ( $before[ $key ] !== $after[ $key ] ) { $rows[] = [ 'label' => $this->t( $label ), 'before' => $this->position_label( $before[ $key ], 'toolbar_position' === $key ), 'after' => $this->position_label( $after[ $key ], 'toolbar_position' === $key ) ]; }
		}
		if ( $before['excluded_users'] !== $after['excluded_users'] ) { $rows[] = [ 'label' => $this->t( 'Excluded users' ), 'before' => $this->exclusion_label( $before['excluded_users'] ), 'after' => $this->exclusion_label( $after['excluded_users'] ) ]; }
		if ( $before['toolbar_icon'] !== $after['toolbar_icon'] ) { $rows[] = [ 'label' => $this->t( 'Toolbar icon' ), 'before' => $this->t( $this->icons()[ $before['toolbar_icon'] ][0] ), 'after' => $this->t( $this->icons()[ $after['toolbar_icon'] ][0] ) ]; }
		foreach ( [ 'enabled' => 'Enable Plugin Hub', 'delete_data' => 'Delete plugin data on uninstall' ] as $key => $label ) {
			if ( $before[ $key ] !== $after[ $key ] ) { $rows[] = [ 'label' => $this->t( $label ), 'before' => $this->t( $before[ $key ] ? 'Enabled' : 'Disabled' ), 'after' => $this->t( $after[ $key ] ? 'Enabled' : 'Disabled' ) ]; }
		}
		$modes = [ '' => 'Original menu', 'link' => 'Link from hub', 'bundle' => 'Bundle in hub' ];
		foreach ( array_unique( array_merge( array_keys( $before['groups'] ), array_keys( $after['groups'] ) ) ) as $id ) {
			$old = $before['groups'][ $id ] ?? ''; $new = $after['groups'][ $id ] ?? '';
			if ( $old === $new && ( $after['baseline'][ $id ] ?? '' ) === ( $before['baseline'][ $id ] ?? '' ) ) { continue; }
			$rows[] = [ 'label' => $this->groups[ $id ]['label'] ?? $id . ' · ' . $this->t( 'Not currently available; rule retained' ), 'before' => $this->t( $modes[ $old ] ), 'after' => $this->t( $modes[ $new ] ) ];
		}
		foreach ( array_diff_key( $before['profiles'], $after['profiles'] ) as $profile ) { $rows[] = [ 'label' => $this->t( 'Workspace' ), 'before' => $profile['name'], 'after' => $this->t( 'Remove workspace' ) ]; }
		foreach ( $after['profiles'] as $id => $profile ) {
			if ( ( $before['profiles'][ $id ] ?? null ) !== $profile ) { $rows[] = [ 'label' => $this->t( 'Workspace' ) . ': ' . $profile['name'], 'before' => isset( $before['profiles'][ $id ] ) ? implode( ', ', array_map( function( $group_id ) { return $this->groups[ $group_id ]['label'] ?? $group_id; }, $before['profiles'][ $id ]['groups'] ) ) : '—', 'after' => implode( ', ', array_map( function( $id ) { return $this->groups[ $id ]['label'] ?? $id; }, $profile['groups'] ) ) ]; }
		}
		return $rows;
	}

	/**
	 * Render hub settings and pending change review inside the existing PSM screen.
	 * @return void
	 */
	public function settings() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$pending = get_user_meta( get_current_user_id(), 'ddw_psm_pending_' . get_current_blog_id(), true );
		if ( is_array( $pending ) && $pending['expires'] >= time() ) {
			echo '<section class="psm-card"><h2>' . esc_html( $this->t( 'Review changes' ) ) . '</h2><p>' . esc_html( $this->t( 'Nothing has been changed yet. Apply this configuration or discard it.' ) ) . '</p><div class="psm-table-scroll"><table class="widefat"><thead><tr><th>' . esc_html( $this->t( 'Setting' ) ) . '</th><th>' . esc_html( $this->t( 'Before' ) ) . '</th><th>' . esc_html( $this->t( 'After' ) ) . '</th></tr></thead><tbody>';

			foreach ( $this->changes( $pending ) as $change ) {
				echo '<tr><th>' . esc_html( $change['label'] ) . '</th><td>' . esc_html( $change['before'] ) . '</td><td>' . esc_html( $change['after'] ) . '</td></tr>';
			}

			echo '</tbody></table></div>';
			foreach ( [ 'apply' => 'Apply changes', 'cancel' => 'Discard changes' ] as $action => $label ) { $this->form_start( $action ); if ( 'apply' === $action ) { foreach ( $pending['rules'] as $id => $rule ) { if ( ( $pending['before']['rules'][ $id ] ?? null ) !== $rule ) { echo '<label class="psm-review-choice"><input type="checkbox" name="apply_rules[]" value="' . esc_attr( $id ) . '" checked> ' . esc_html( $id ) . '</label> '; } } echo '<input type="hidden" name="rule_selection" value="1">'; } echo '<button class="button button-primary">' . esc_html( $this->t( $label ) ) . '</button></form> '; }
			echo '</section>';
		}
		if ( get_user_meta( get_current_user_id(), 'ddw_psm_undo_' . get_current_blog_id(), true ) ) { $this->form_start( 'undo' ); echo '<button class="button">' . esc_html( $this->t( 'Undo last change' ) ) . '</button></form>'; }
		echo '<section class="psm-card"><h2>' . esc_html( $this->t( 'Plugin Hub' ) ) . '</h2><p>' . esc_html( $this->t( 'Cards and a contextual menu keep plugin pages together. Link mode keeps original menus. Bundle mode hides a group only while its accepted structure is unchanged.' ) ) . '</p>';
		$this->form_start( 'review-hub' );
		$this->user_exclusion_settings();
		$this->placement_settings();
		$this->selection_tools();
		echo '<p><label><input type="checkbox" name="enabled" ' . checked( $this->config['enabled'], true, false ) . '> ' . esc_html( $this->t( 'Enable Plugin Hub' ) ) . '</label></p><div class="psm-table-scroll"><table class="widefat striped psm-hub-settings-table"><thead><tr><th scope="col" class="psm-mark-column" hidden><input type="checkbox" data-psm-mark-visible aria-label="' . esc_attr( $this->t( 'Mark visible groups' ) ) . '"></th><th scope="col">' . esc_html( $this->t( 'Plugin' ) ) . '</th><th scope="col">' . esc_html( $this->t( 'Navigation' ) ) . '</th><th scope="col">' . esc_html( $this->t( 'Structure' ) ) . '</th></tr></thead><tbody>';
		foreach ( $this->groups as $id => $group ) {
			echo '<tr data-psm-selection-row="' . esc_attr( $id ) . '" data-search="' . esc_attr( $group['label'] . ' ' . $group['slug'] . ' ' . $group['metadata']['name'] ) . '" data-review="' . ( $this->unchanged( $group ) ? '0' : '1' ) . '"><td class="psm-mark-column" hidden><input type="checkbox" data-psm-mark aria-label="' . esc_attr( sprintf( $this->t( 'Mark %s' ), $group['label'] ) ) . '"></td><th scope="row"><strong>' . esc_html( $group['label'] ) . '</strong><br><small>' . esc_html( $group['metadata']['name'] . ' ' . $group['metadata']['version'] ) . '</small></th><td><label><span class="screen-reader-text">' . esc_html( $group['label'] ) . '</span><select name="groups[' . esc_attr( $id ) . ']">';
			foreach ( [ '' => 'Original menu', 'link' => 'Link from hub', 'bundle' => 'Bundle in hub' ] as $value => $label ) { echo '<option value="' . esc_attr( $value ) . '" ' . selected( $this->config['groups'][ $id ] ?? '', $value, false ) . '>' . esc_html( $this->t( $label ) ) . '</option>'; }
			echo '</select></label></td><td><span class="psm-structure-badge' . ( $this->unchanged( $group ) ? ' is-accepted' : '' ) . '">' . esc_html( $this->structure_summary( $group ) ) . '</span></td></tr>';
		}
		echo '</tbody></table></div><p class="description">' . esc_html( $this->t( 'Menu detected; full plugin compatibility not certified' ) ) . '</p>';
		// Preserve dormant choices without trusting client-supplied URLs or callbacks.
		foreach ( array_diff_key( $this->config['groups'], $this->groups ) as $id => $mode ) { echo '<input type="hidden" name="groups[' . esc_attr( $id ) . ']" value="' . esc_attr( $mode ) . '">'; }
		echo '<fieldset class="psm-icon-picker"><legend>' . esc_html( $this->t( 'Toolbar icon' ) ) . '</legend><p class="description">' . esc_html( $this->t( 'Choose the Plugin Hub icon for this site. Local SVG icons include a Dashicons fallback.' ) ) . '</p><div class="psm-icon-options">';
		foreach ( $this->icons() as $id => $icon ) { echo '<label class="psm-icon-choice"><input type="radio" name="toolbar_icon" value="' . esc_attr( $id ) . '" ' . checked( $this->config['toolbar_icon'], $id, false ) . '>' . $this->icon( $id ) . '<span>' . esc_html( $this->t( $icon[0] ) ) . '</span></label>'; }
		echo '</div></fieldset><p class="psm-data-choice"><label><input type="checkbox" name="delete_data" ' . checked( $this->config['delete_data'], true, false ) . '> ' . esc_html( $this->t( 'Delete this plugin’s settings and personal shortcuts when uninstalling.' ) ) . '</label></p><button class="button">' . esc_html( $this->t( 'Preview changes' ) ) . '</button></form></section>';

		if ( $this->selected() ) {
			echo '<section class="psm-card"><h2>' . esc_html( $this->t( 'Workspace templates' ) ) . '</h2><p>' . esc_html( $this->t( 'Choose plugin groups for a workspace such as setup, content or maintenance. Workspaces only filter navigation and never change permissions.' ) ) . '</p>';
			$this->form_start( 'profile' );
			echo '<p><label>' . esc_html( $this->t( 'Workspace name' ) ) . ' <input name="profile_name" maxlength="80" required></label></p>';
			echo '<div class="psm-profile-options">';
			foreach ( $this->selected() as $id => $group ) { echo '<p><label><input type="checkbox" name="profile_groups[]" value="' . esc_attr( $id ) . '"> ' . esc_html( $group['label'] ) . '</label></p>'; }
			echo '</div><button class="button">' . esc_html( $this->t( 'Preview changes' ) ) . '</button></form>';
			foreach ( $this->config['profiles'] as $id => $profile ) { $this->form_start( 'remove-profile' ); echo '<input type="hidden" name="profile_id" value="' . esc_attr( $id ) . '"><p>' . esc_html( $profile['name'] ) . ' <button class="button">' . esc_html( $this->t( 'Remove workspace' ) ) . '</button></p></form>'; }
			echo '</section>';
		}
	}
}
