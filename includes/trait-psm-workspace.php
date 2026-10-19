<?php
/** Persistent personal navigation, native commands and bounded diagnostics. @package PluginSubmenuMover */
defined( 'ABSPATH' ) || exit;

/** Extends the live inventory controller without registering third-party callbacks again. */
trait DDW_PSM_Workspace {
	private $attached = '';

	/** Register read-only WordPress health extensions. @return void */
	private function register_workspace() {
		add_filter( 'debug_information', [ $this, 'health_info' ] );
		add_filter( 'site_status_tests', [ $this, 'health_tests' ] );
	}

	/**
	 * Keep a selected group separately from the actual current page.
	 * Explicit navigation context keeps tabs independent; untagged visits use the personal default.
	 * @return void
	 */
	private function resolve_attachment() {
		$this->attached = '';
		if ( empty( $this->config['enabled'] ) || $this->mover->original_view() ) { return; }
		$p = $this->personal(); $selected = $this->selected();
		$requested = $_GET['psm_context'] ?? $_POST['psm_context'] ?? null;
		$requested = is_string( $requested ) ? wp_unslash( $requested ) : null;
		$this->attached = 'none' === $requested ? '' : ( isset( $selected[ $requested ?? '' ] ) ? $requested : ( isset( $selected[ $p['group'] ] ) ? $p['group'] : '' ) );
		if ( isset( $selected[ $this->active ] ) ) {
			$this->attached = $this->active;
			if ( 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) && $p['group'] !== $this->active ) {
				$p['group'] = $this->active;
				update_user_meta( get_current_user_id(), 'ddw_psm_personal_' . get_current_blog_id(), $p );
			}
		}
	}

	/**
	 * Return a visible search vocabulary for navigation, never executable actions.
	 * @param string $label Original plugin and page label.
	 * @return array Original words and useful English/German synonyms.
	 */
	private function keywords( $label ) {
		$words = [ $label, 'Plugin Hub', 'Plugin-Hub' ];
		foreach ( [ 'backup|sicher' => 'backup sicherung sicherungen', 'cache|zwischen' => 'cache zwischenspeicher', 'member|role|rolle' => 'members roles rollen berechtigungen', 'form|eintr|entries' => 'forms formular formulare entries einträge', 'redirect|weiterleit' => 'redirect weiterleitung weiterleitungen', 'smtp|mail' => 'smtp email e-mail mail', 'settings|einstellung' => 'settings einstellungen', 'tools|werkzeug' => 'tools werkzeuge', 'overview|dashboard|übersicht' => 'overview dashboard übersicht' ] as $pattern => $synonyms ) {
			if ( preg_match( '/' . $pattern . '/iu', $label ) ) { $words[] = $synonyms; }
		}
		return $words;
	}

	/**
	 * Render the same attached-group controls on the hub and other admin pages.
	 * @param bool $notice Whether this is outside the hub screen.
	 * @return void
	 */
	private function workspace_bar( $notice = false ) {
		if ( $this->personal()['bar_hidden'] || ! isset( $this->selected()[ $this->attached ] ) || $this->mover->original_view() ) { return; }
		echo '<div class="' . ( $notice ? 'notice inline ' : '' ) . 'psm-context" role="region" aria-label="' . esc_attr( $this->t( 'Attached workgroup' ) ) . '"><p><a href="' . esc_url( admin_url( 'admin.php?page=' . self::HUB ) ) . '">' . esc_html( $this->t( 'All plugins' ) ) . '</a> · ' . esc_html( $this->t( 'Attached:' ) ) . ' <strong>' . esc_html( $this->groups[ $this->attached ]['label'] ) . '</strong></p><label>' . esc_html( $this->t( 'Switch plugin' ) ) . ' <select data-psm-switch>';
		foreach ( $this->selected() as $id => $group ) { $page = reset( $group['pages'] ); echo '<option value="' . esc_url( $page['url'] ) . '" ' . selected( $this->attached, $id, false ) . '>' . esc_html( $group['label'] ) . '</option>'; }
		echo '</select></label>'; $this->form_start( 'detach', 'personal' ); echo '<button class="button">' . esc_html( $this->t( 'Detach group' ) ) . '</button></form>';
		if ( ! $this->unchanged( $this->groups[ $this->attached ] ) ) { echo '<p>' . esc_html( $this->structure_summary( $this->groups[ $this->attached ] ) ) . '</p>'; }
		echo '</div>';
	}

	/** Render the settings-page bar after its complete branded header. @return void */
	public function render_context_bar() { $this->workspace_bar(); }

	/**
	 * Process personal workspace actions behind the existing capability and nonce gate.
	 * @param string $action Validated action name.
	 * @return bool False when the existing controller should handle this action.
	 */
	private function workspace_action( $action ) {
		if ( ! in_array( $action, [ 'detach', 'reset-favorites', 'reset-view', 'set-view', 'favorite-order', 'favorite-labels', 'bar-visibility' ], true ) ) { return false; }
		$p = $this->personal();
		if ( 'bar-visibility' === $action ) { $p['bar_hidden'] = ! isset( $_POST['show_bar'] ); }
		elseif ( 'detach' === $action ) { $p['group'] = ''; $this->attached = ''; }
		elseif ( 'reset-favorites' === $action ) { $p['pins'] = []; $p['labels'] = []; }
		elseif ( 'reset-view' === $action ) { $p['view'] = 'all'; }
		elseif ( 'set-view' === $action ) {
			$id = $_POST['view'] ?? '';
			if ( ! is_string( $id ) || ( 'all' !== $id && ! isset( $this->config['profiles'][ $id ] ) ) ) { wp_die( esc_html( $this->t( 'Invalid configuration.' ) ), '', [ 'response' => 400 ] ); }
			$p['view'] = $id;
		} elseif ( 'favorite-labels' === $action ) {
			$labels = $_POST['favorite_labels'] ?? [];
			if ( ! is_array( $labels ) || count( $labels ) > 30 ) { wp_die( esc_html( $this->t( 'Invalid configuration.' ) ), '', [ 'response' => 400 ] ); }
			foreach ( $this->favorites() as $id => $page ) {
				if ( ! isset( $labels[ $id ] ) || ! is_string( $labels[ $id ] ) ) { continue; }
				preg_match( '/^.{0,60}/us', sanitize_text_field( wp_unslash( $labels[ $id ] ) ), $match );
				$p['labels'][ $id ] = $match[0] ?? '';
			}
		} else {
			$id = $_POST['page_id'] ?? ''; $direction = $_POST['direction'] ?? '';
			$visible = array_keys( $this->favorites() );
			$position = is_string( $id ) ? array_search( $id, $visible, true ) : false;
			if ( false === $position || ! in_array( $direction, [ 'up', 'down' ], true ) ) { wp_die( esc_html( $this->t( 'Page unavailable.' ) ), '', [ 'response' => 403 ] ); }
			$next = $position + ( 'up' === $direction ? -1 : 1 );
			if ( isset( $visible[ $next ] ) ) {
				$a = array_search( $id, $p['pins'], true ); $b = array_search( $visible[ $next ], $p['pins'], true );
				[ $p['pins'][ $a ], $p['pins'][ $b ] ] = [ $p['pins'][ $b ], $p['pins'][ $a ] ];
			}
		}
		update_user_meta( get_current_user_id(), 'ddw_psm_personal_' . get_current_blog_id(), $p );
		$this->redirect( ! ( 'bar-visibility' === $action && current_user_can( 'manage_options' ) && '1' === ( $_POST['psm_return_settings'] ?? '' ) ) );
	}

	/** Render keyboard-accessible ordering, labels and separate personal reset actions. @return void */
	private function personal_tools() {
		echo '<section id="psm-personal-tools" class="psm-personal-tools"><h2>' . esc_html( $this->t( 'Your navigation on this site' ) ) . '</h2><p>' . esc_html( $this->t( 'These controls affect only your account on this site. Shared rules and other users remain unchanged.' ) ) . '</p>';
		$this->bar_preferences();
		$favorites = $this->favorites(); $personal = $this->personal(); $i = 0;
		foreach ( $favorites as $id => $page ) {
			echo '<div class="psm-order-row"><span>' . esc_html( $page['original_label'] ) . '</span>';
			$this->form_start( 'favorite-order', 'personal' );
			echo '<input type="hidden" name="page_id" value="' . esc_attr( $id ) . '"><button class="button" name="direction" value="up" ' . disabled( 0 === $i, true, false ) . ' aria-label="' . esc_attr( $this->t( 'Move favorite up' ) . ': ' . $page['context_label'] ) . '">↑</button> <button class="button" name="direction" value="down" ' . disabled( $i === count( $favorites ) - 1, true, false ) . ' aria-label="' . esc_attr( $this->t( 'Move favorite down' ) . ': ' . $page['context_label'] ) . '">↓</button></form></div>'; $i++;
		}
		if ( $favorites ) {
			$this->form_start( 'favorite-labels', 'personal' );
			echo '<fieldset><legend>' . esc_html( $this->t( 'Favorite short labels' ) ) . '</legend><p>' . esc_html( $this->t( 'Short labels replace menu and toolbar labels. Original names remain here for reference.' ) ) . '</p>';
			foreach ( $favorites as $id => $page ) { echo '<p><label>' . esc_html( $this->groups[ $page['group'] ]['label'] . ' · ' . $page['label'] ) . ' <input name="favorite_labels[' . esc_attr( $id ) . ']" value="' . esc_attr( $personal['labels'][ $id ] ?? '' ) . '" maxlength="60"></label></p>'; }
			echo '<button class="button">' . esc_html( $this->t( 'Save short labels' ) ) . '</button></fieldset></form>';
		}
		echo '<div class="psm-reset-actions">';
		foreach ( [ 'detach' => 'Detach group', 'reset-view' => 'Reset my workspace view', 'reset-favorites' => 'Remove all my favorites' ] as $action => $label ) { $this->form_start( $action, 'personal' ); echo '<button class="button">' . esc_html( $this->t( $label ) ) . '</button></form>'; }
		echo '</div></section>';
	}

	/** Render the per-account, per-site context-bar preference. @return void */
	public function bar_preferences() {
		$this->form_start( 'bar-visibility', 'personal' );
		if ( current_user_can( 'manage_options' ) && 'plugin-submenu-mover' === ( $_GET['page'] ?? '' ) ) { echo '<input type="hidden" name="psm_return_settings" value="1">'; }
		echo '<fieldset class="psm-bar-preference"><legend>' . esc_html( $this->t( 'Group bar' ) ) . '</legend><p><label><input type="checkbox" name="show_bar" ' . checked( ! $this->personal()['bar_hidden'], true, false ) . '> ' . esc_html( $this->t( 'Show the group bar on admin pages' ) ) . '</label></p><p class="description">' . esc_html( $this->t( 'Personal setting for this site. Hiding the bar keeps your attached group in the admin menu and toolbar.' ) ) . '</p><button class="button">' . esc_html( $this->t( 'Save preferences' ) ) . '</button></fieldset></form>';
	}

	/**
	 * Describe only changes supported by a locally accepted structural snapshot.
	 * @param array $group Current registered group.
	 * @return string Human-readable state.
	 */
	private function structure_summary( $group ) {
		if ( $this->unchanged( $group ) ) { return $this->t( 'Accepted structure' ); }
		$before = $this->config['structures'][ $group['id'] ] ?? [];
		if ( ! $before ) { return $this->t( 'Review structure before bundling' ); }
		$old = array_column( $before, 1, 0 ); $new = array_column( $group['structure'], 1, 0 ); $permissions = 0;
		foreach ( array_intersect_key( $old, $new ) as $slug => $cap ) { if ( $cap !== $new[ $slug ] ) { $permissions++; } }
		return sprintf( $this->t( 'Added pages: %1$d · Removed pages: %2$d · Permission changes: %3$d' ), count( array_diff_key( $new, $old ) ), count( array_diff_key( $old, $new ) ), $permissions );
	}

	/**
	 * Build native palette commands from this request's permitted destinations.
	 * @return array Commands with fixed navigation or nonce-protected POST targets.
	 */
	public function command_data() {
		if ( ! $this->mover->user_allowed() || is_network_admin() || ! current_user_can( 'read' ) ) { return []; }
		$commands = [];
		$navigate = function( $id, $label, $url, $keywords = [] ) use ( &$commands ) { $commands[] = [ 'id' => 'psm/' . $id, 'label' => $this->t( 'Plugin Hub' ) . ': ' . $label, 'url' => $url, 'keywords' => $keywords ]; };
		$post = function( $id, $label, $action, $fields = [] ) use ( &$commands ) { $commands[] = [ 'id' => 'psm/' . $id, 'label' => $this->t( 'Plugin Hub' ) . ': ' . $label, 'url' => admin_url( 'admin-post.php' ), 'fields' => array_merge( [ 'action' => 'psm_navigation', 'psm_navigation' => $action, 'psm_scope' => 'personal', '_wpnonce' => wp_create_nonce( 'psm_navigation_personal' ) ], $fields ) ]; };
		if ( current_user_can( 'manage_options' ) ) {
			$navigate( 'settings', $this->t( 'Settings' ), admin_url( 'tools.php?page=plugin-submenu-mover' ) );
			$commands[] = [ 'id' => 'psm/original', 'label' => $this->t( 'Plugin Hub' ) . ': ' . $this->t( $this->mover->original_view() ? 'Resume automatic moves' : 'Show original menu for one hour' ), 'url' => admin_url( 'tools.php?page=plugin-submenu-mover' ), 'fields' => [ 'psm_action' => $this->mover->original_view() ? 'resume' : 'original', '_wpnonce' => wp_create_nonce( 'psm_rules' ) ] ];
		}
		if ( current_user_can( 'view_site_health_checks' ) ) { $navigate( 'health', $this->t( 'Site Health information' ), admin_url( 'site-health.php?tab=debug' ) ); }
		if ( empty( $this->config['enabled'] ) || $this->mover->original_view() || ! $this->selected() ) { return $commands; }
		$navigate( 'overview', $this->t( 'All plugins' ), admin_url( 'admin.php?page=' . self::HUB ) );
		$navigate( 'personal', $this->t( 'Personal view' ), admin_url( 'admin.php?page=' . self::HUB . '#psm-personal-tools' ) );
		$post( 'detach', $this->t( 'Detach group' ), 'detach' );
		$bar_hidden = $this->personal()['bar_hidden'];
		$post( 'group-bar', $this->t( $bar_hidden ? 'Show group bar' : 'Hide group bar' ), 'bar-visibility', $bar_hidden ? [ 'show_bar' => 'on' ] : [] );
		$post( 'all-view', $this->t( 'Reset my workspace view' ), 'set-view', [ 'view' => 'all' ] );
		foreach ( $this->config['profiles'] as $id => $profile ) { $post( $id, $this->t( 'Workspace' ) . ': ' . $profile['name'], 'set-view', [ 'view' => $id ] ); }
		$favorites = $this->favorites(); $p = $this->personal();
		foreach ( $this->selected() as $id => $group ) {
			$page = reset( $group['pages'] ); $navigate( 'group-' . $id, $this->t( 'Attach group' ) . ': ' . $group['label'], $page['url'], $this->keywords( $group['label'] ) );
			foreach ( $group['pages'] as $page ) {
				$label = $group['label'] . ' · ' . $page['label'];
				if ( isset( $favorites[ $page['id'] ] ) ) { $label = $this->t( 'Favorites' ) . ': ' . $favorites[ $page['id'] ]['context_label']; }
				$navigate( $page['id'], $label, $page['url'], $this->keywords( $label . ' ' . $group['label'] . ' ' . $page['label'] ) );
				if ( $p['history'] && in_array( $page['id'], $p['recent'], true ) ) { $navigate( 'recent-' . $page['id'], $this->t( 'Recently visited' ) . ': ' . $label, $page['url'], $this->keywords( $label . ' ' . $group['label'] . ' ' . $page['label'] ) ); }
			}
		}
		return $commands;
	}

	/**
	 * Enqueue a tab-local navigation bridge and register commands only with the native API.
	 * @return void
	 */
	private function workspace_assets() {
		if ( is_network_admin() || ! current_user_can( 'read' ) ) { return; }
		wp_enqueue_script( 'psm-workspace', plugins_url( '../assets/workspace.js', __FILE__ ), [], DDW_Plugin_Submenu_Mover::VERSION, true );
		$screen = get_current_screen();
		$native_palette = ( function_exists( 'wp_enqueue_command_palette_assets' ) || ( $screen && ( $screen->is_block_editor() || 'site-editor' === $screen->id ) ) ) && wp_script_is( 'wp-commands', 'registered' );
		wp_localize_script( 'psm-workspace', 'psmWorkspace', [ 'key' => 'psm-' . get_current_blog_id() . '-' . get_current_user_id(), 'group' => $this->attached ?: 'none', 'admin' => admin_url(), 'commands' => $native_palette ? $this->command_data() : [], 'nativePalette' => $native_palette ] );
		if ( $native_palette && wp_script_is( 'wp-commands', 'registered' ) ) { wp_enqueue_script( 'psm-commands', plugins_url( '../assets/commands.js', __FILE__ ), [ 'psm-workspace', 'wp-commands', 'wp-data' ], DDW_Plugin_Submenu_Mover::VERSION, true ); }
	}

	/**
	 * Add a minimal non-personal support section without executing menu registration.
	 * @param array $info Existing Site Health information.
	 * @return array Extended information.
	 */
	public function health_info( $info ) {
		if ( is_network_admin() || ! current_user_can( 'view_site_health_checks' ) ) { return $info; }
		$nav = $this->config();
		$info['plugin-submenu-mover'] = [ 'label' => 'Plugin Submenu Mover', 'fields' => [
			'version' => [ 'label' => $this->t( 'Version' ), 'value' => DDW_Plugin_Submenu_Mover::VERSION ],
			'hub' => [ 'label' => $this->t( 'Plugin Hub' ), 'value' => $this->t( $nav['enabled'] ? 'Enabled' : 'Disabled' ) ],
			'groups' => [ 'label' => $this->t( 'Configured groups' ), 'value' => count( array_filter( $nav['groups'] ) ) ],
			'rules' => [ 'label' => $this->t( 'Stored rules' ), 'value' => count( $this->mover->rules() ) ],
			'schema' => [ 'label' => $this->t( 'Export schema' ), 'value' => 2 ],
			'components' => [ 'label' => $this->t( 'Bundled components' ), 'value' => 'Library 1.0.0 / Updater 2.1.0' ],
		] ];
		return $info;
	}

	/**
	 * Register a meaningful configuration-integrity test, independent of optional preferences.
	 * @param array $tests Existing core tests.
	 * @return array Extended test list.
	 */
	public function health_tests( $tests ) {
		if ( ! is_network_admin() ) { $tests['direct']['psm_configuration'] = [ 'label' => $this->t( 'Plugin navigation configuration' ), 'test' => [ $this, 'health_test' ] ]; }
		return $tests;
	}

	/** Check stored configuration only; disabled optional features are healthy. @return array Core health result. */
	public function health_test() {
		$nav = get_option( self::OPTION, false ); $rules = get_option( 'ddw_psm_rules', false );
		$invalid = ( false !== $nav && is_wp_error( $this->validate( $nav ) ) ) || ( false !== $rules && is_wp_error( $this->mover->validate_rules( $rules ) ) );
		return [ 'label' => $this->t( $invalid ? 'Plugin navigation settings need review' : 'Plugin navigation settings are readable' ), 'status' => $invalid ? 'recommended' : 'good', 'badge' => [ 'label' => $this->t( 'Navigation' ), 'color' => 'blue' ], 'description' => '<p>' . esc_html( $this->t( $invalid ? 'Stored navigation settings are invalid. Review the plugin settings before changing them.' : 'Stored navigation settings passed validation. Optional hub, favorites and history features may remain disabled.' ) ) . '</p>', 'actions' => '<a href="' . esc_url( admin_url( 'tools.php?page=plugin-submenu-mover' ) ) . '">' . esc_html( $this->t( 'Settings' ) ) . '</a>', 'test' => 'psm_configuration' ];
	}
}
