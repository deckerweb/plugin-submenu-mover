<?php
/** Reviewable workplace placement and installation-based selection. @package PluginSubmenuMover */
defined( 'ABSPATH' ) || exit;

/** Keeps placement anchors opaque and limits templates to the current permitted inventory. */
trait DDW_PSM_Setup {
	private $menu_anchors = [];
	private $toolbar_anchors = [];

	/** Render searchable multiple selection with a plain-ID fallback. @return void */
	private function user_exclusion_settings() {
		$ids = $this->config()['excluded_users']; $choices = [];
		if ( current_user_can( 'list_users' ) && $ids ) {
			foreach ( get_users( [ 'include' => $ids, 'blog_id' => get_current_blog_id(), 'number' => 200, 'fields' => [ 'ID', 'display_name', 'user_login' ] ] ) as $user ) { $choices[] = $this->user_choice( $user ); }
		}
		$found = array_column( $choices, 'id' );
		foreach ( array_diff( $ids, $found ) as $id ) { $choices[] = [ 'id' => $id, 'label' => sprintf( $this->t( 'Unavailable account (#%d)' ), $id ) ]; }
		echo '<fieldset class="psm-placement" data-psm-user-picker data-selected="' . esc_attr( wp_json_encode( $choices ) ) . '"><legend>' . esc_html( $this->t( 'Excluded users' ) ) . '</legend><p>' . esc_html( $this->t( 'Excluded users keep their original admin menus. Plugin Hub, automatic moves, favorites and plugin settings are disabled for them on this site. Roles and saved preferences remain unchanged.' ) ) . '</p><div data-psm-user-fallback><label for="psm-excluded-users">' . esc_html( $this->t( 'User IDs separated by commas' ) ) . '</label><input id="psm-excluded-users" name="excluded_users" type="text" value="' . esc_attr( implode( ', ', $ids ) ) . '" maxlength="4200" aria-describedby="psm-excluded-help"></div><p id="psm-excluded-help" class="description">' . esc_html( $this->t( 'Choose several users by name or username. Your own account cannot be excluded. Changes take effect after preview and apply. This selection stays on this site and is not exported.' ) ) . '</p>';
		if ( current_user_can( 'list_users' ) ) {
			echo '<div data-psm-user-enhanced hidden><h3>' . esc_html( $this->t( 'Selected users' ) ) . '</h3><ul data-psm-user-selected class="psm-user-chips"></ul><p data-psm-user-none>' . esc_html( $this->t( 'No users excluded.' ) ) . '</p><label for="psm-user-search">' . esc_html( $this->t( 'Find users' ) ) . '</label><input id="psm-user-search" type="search" autocomplete="off" aria-controls="psm-user-results" placeholder="' . esc_attr( $this->t( 'Name or username…' ) ) . '"><p data-psm-user-status role="status" aria-live="polite"></p><ul id="psm-user-results" class="psm-user-results"></ul><button class="button" type="button" data-psm-user-more hidden>' . esc_html( $this->t( 'Load more users' ) ) . '</button></div>';
		}
		echo '</fieldset>';
	}

	/**
	 * Resolve bounded local account names for the before/after review.
	 * @param array $ids Validated local user IDs.
	 * @return string Plain account labels, with a retained-ID fallback.
	 */
	private function exclusion_label( $ids ) {
		if ( ! $ids ) { return '—'; }
		if ( ! current_user_can( 'list_users' ) ) { return implode( ', ', $ids ); }
		$labels = [];
		foreach ( get_users( [ 'include' => $ids, 'blog_id' => get_current_blog_id(), 'number' => 200, 'fields' => [ 'ID', 'display_name', 'user_login' ] ] ) as $user ) { $choice = $this->user_choice( $user ); $labels[ $choice['id'] ] = $choice['label']; }
		return implode( ', ', array_map( function( $id ) use ( $labels ) { return $labels[ $id ] ?? sprintf( $this->t( 'Unavailable account (#%d)' ), $id ); }, $ids ) );
	}

	/**
	 * Return only the account identity needed by the local selector.
	 * @param object $user WordPress user query row.
	 * @return array Integer ID and plain display label.
	 */
	private function user_choice( $user ) {
		return [ 'id' => (int) $user->ID, 'label' => $user->display_name === $user->user_login ? $user->display_name : $user->display_name . ' (' . $user->user_login . ')' ];
	}

	/**
	 * Query a bounded page of current-site accounts without exposing email addresses.
	 * @param string $term Name or username search, at most 240 bytes.
	 * @param int $page One-based result page, at most 10000.
	 * @return array|WP_Error Permitted choices and next-page flag, or validation error.
	 */
	public function user_choices( $term, $page = 1 ) {
		if ( is_network_admin() || ! $this->mover->user_allowed() || ! current_user_can( 'manage_options' ) || ! current_user_can( 'list_users' ) ) { return new WP_Error( 'psm_access', $this->t( 'Access denied.' ), [ 'status' => 403 ] ); }
		if ( ! is_string( $term ) || strlen( $term ) > 240 || ! is_int( $page ) || $page < 1 || $page > 10000 ) { return new WP_Error( 'psm_users', $this->t( 'Invalid configuration.' ), [ 'status' => 400 ] ); }
		$term = trim( sanitize_text_field( $term ) );
		$args = [ 'blog_id' => get_current_blog_id(), 'exclude' => [ get_current_user_id() ], 'number' => 21, 'offset' => ( $page - 1 ) * 20, 'count_total' => false, 'fields' => [ 'ID', 'display_name', 'user_login' ], 'orderby' => [ 'display_name' => 'ASC', 'ID' => 'ASC' ] ];
		if ( '' !== $term ) { $args['search'] = '*' . $term . '*'; $args['search_columns'] = [ 'display_name', 'user_login' ]; }
		$users = get_users( $args );
		return [ 'users' => array_map( [ $this, 'user_choice' ], array_slice( $users, 0, 20 ) ), 'more' => count( $users ) > 20 ];
	}

	/** Handle authenticated, nonce-protected account search without modifying data. @return never */
	public function ajax_user_choices() {
		if ( ! check_ajax_referer( 'psm_user_search', 'nonce', false ) ) { wp_send_json_error( [ 'message' => $this->t( 'Access denied.' ) ], 403 ); }
		$page = $_POST['page'] ?? '1';
		if ( ! is_string( $page ) || ! ctype_digit( $page ) || strlen( $page ) > 5 ) { wp_send_json_error( [ 'message' => $this->t( 'Invalid configuration.' ) ], 400 ); }
		$term = $_POST['search'] ?? '';
		$result = $this->user_choices( is_string( $term ) ? wp_unslash( $term ) : $term, (int) $page );
		if ( is_wp_error( $result ) ) { wp_send_json_error( [ 'message' => $result->get_error_message() ], $result->get_error_data()['status'] ); }
		wp_send_json_success( $result );
	}

	/** Register native ordering hooks without changing other menus' relative order. @return void */
	private function register_setup() {
		add_action( 'wp_ajax_psm_user_search', [ $this, 'ajax_user_choices' ] );
		add_filter( 'custom_menu_order', [ $this, 'custom_menu_order' ] );
		add_filter( 'menu_order', [ $this, 'menu_order' ], 1000 );
		add_action( 'wp_before_admin_bar_render', [ $this, 'position_toolbar' ], 1000 );
	}

	/**
	 * Derive a stable anchor without storing a destination URL or translated label.
	 * @param string $slug Registered menu or toolbar node ID.
	 * @return string Opaque local identifier.
	 */
	private function anchor_id( $slug ) { return substr( hash( 'sha256', $slug ), 0, 16 ); }

	/** Collect currently permitted menu anchors before bundling. @return void */
	private function collect_menu_anchors() {
		global $menu;
		$this->menu_anchors = [];
		$entries = (array) $menu; ksort( $entries, SORT_NUMERIC );
		foreach ( $entries as $item ) {
			if ( ! isset( $item[0], $item[1], $item[2] ) || self::HUB === $item[2] || str_starts_with( $item[2], 'separator' ) || ! current_user_can( $item[1] ) ) { continue; }
			$label = trim( wp_strip_all_tags( preg_replace( '/<span\b[^>]*(?:awaiting-mod|update-plugins)[^>]*>.*$/is', '', $item[0] ) ) );
			if ( $label ) { $this->menu_anchors[ $this->anchor_id( $item[2] ) ] = [ 'slug' => $item[2], 'label' => $label ]; }
		}
	}

	/**
	 * Validate placement tokens without permitting arbitrary HTML, URLs or code.
	 * @param mixed $value Submitted token.
	 * @param bool $toolbar Whether toolbar-specific options are allowed.
	 * @return bool Whether the token belongs to the bounded format.
	 */
	private function valid_position( $value, $toolbar = false ) {
		return is_string( $value ) && ( in_array( $value, $toolbar ? [ 'default', 'first', 'right' ] : [ 'default' ], true ) || 1 === preg_match( $toolbar ? '/^after:[a-f0-9]{16}$/D' : '/^(?:before|after):[a-f0-9]{16}$/D', $value ) );
	}

	/**
	 * Enable native custom ordering only while a positioned hub is available.
	 * @param bool $custom Existing custom-order preference.
	 * @return bool Existing preference or the hub requirement.
	 */
	public function custom_menu_order( $custom ) {
		return $custom || ( ! is_network_admin() && ! empty( $this->config['enabled'] ) && 'default' !== ( $this->config['menu_position'] ?? 'default' ) && $this->selected() && ! $this->mover->original_view() );
	}

	/**
	 * Move only the hub beside a still-present anchor; otherwise retain the default.
	 * @param array $order Native or previously customized menu slugs.
	 * @return array Menu slugs in their requested order.
	 */
	public function menu_order( $order ) {
		$value = $this->config['menu_position'] ?? 'default';
		if ( is_network_admin() || 'default' === $value || ! is_array( $order ) || ! in_array( self::HUB, $order, true ) || $this->mover->original_view() ) { return $order; }
		[ $side, $id ] = explode( ':', $value );
		$anchor = $this->menu_anchors[ $id ]['slug'] ?? '';
		if ( ! in_array( $anchor, $order, true ) ) { return $order; }
		$order = array_values( array_diff( $order, [ self::HUB ] ) );
		$index = array_search( $anchor, $order, true ) + ( 'after' === $side ? 1 : 0 );
		array_splice( $order, $index, 0, [ self::HUB ] );
		return $order;
	}

	/**
	 * Collect toolbar anchors and position the hub before WordPress binds its tree.
	 * @param WP_Admin_Bar|null $bar Explicit test instance, or the current native toolbar.
	 * @return void
	 */
	public function position_toolbar( $bar = null ) {
		if ( is_network_admin() ) { return; }
		if ( ! $bar ) { global $wp_admin_bar; $bar = $wp_admin_bar; }
		if ( ! $bar instanceof WP_Admin_Bar ) { return; }
		$nodes = $bar->get_nodes() ?: []; $this->toolbar_anchors = [];
		foreach ( $nodes as $node ) {
			if ( ! $node->parent && ! $node->group && ! in_array( $node->id, [ 'psm-hub', 'menu-toggle' ], true ) ) {
				$label = 'comments' === $node->id ? __( 'Comments', 'plugin-submenu-mover' ) : trim( wp_strip_all_tags( $node->meta['title'] ?? $node->title ) );
				if ( $label ) { $this->toolbar_anchors[ $this->anchor_id( $node->id ) ] = [ 'slug' => $node->id, 'label' => $label ]; }
			}
		}
		$hub = $nodes['psm-hub'] ?? null; $value = $this->config['toolbar_position'] ?? 'default';
		if ( ! $hub || 'default' === $value || $this->mover->original_view() ) { return; }
		$parent = 'right' === $value ? 'top-secondary' : false;
		if ( 'right' === $value && ! isset( $nodes['top-secondary'] ) ) { return; }
		$siblings = array_filter( $nodes, static function( $node ) use ( $parent ) { return $node->parent === $parent && ! $node->group && 'psm-hub' !== $node->id; } );
		$ids = array_keys( $siblings );
		if ( 'right' === $value ) { $index = array_search( 'my-account', $ids, true ); $index = false === $index ? count( $ids ) : $index; }
		elseif ( 'first' === $value ) { $index = 0; }
		else {
			$anchor = $this->toolbar_anchors[ substr( $value, 6 ) ]['slug'] ?? '';
			$index = array_search( $anchor, $ids, true );
			if ( false === $index ) { return; }
			$index++;
		}
		$hub->parent = $parent; $siblings['psm-hub'] = $hub;
		array_splice( $ids, $index, 0, [ 'psm-hub' ] );
		// Removing a node does not remove its children; all original arguments are preserved.
		$bar->remove_node( 'psm-hub' );
		foreach ( $ids as $id ) { $bar->remove_node( $id ); }
		foreach ( $ids as $id ) { $bar->add_node( (array) $siblings[ $id ] ); }
	}

	/**
	 * Format placement settings, including a clear unavailable-anchor fallback.
	 * @param string $value Valid token.
	 * @param bool $toolbar Whether to use toolbar anchors.
	 * @return string Localized description.
	 */
	private function position_label( $value, $toolbar = false ) {
		$fixed = [ 'default' => 'Default position', 'first' => 'Left, at the beginning', 'right' => 'Right, before the user account' ];
		if ( isset( $fixed[ $value ] ) ) { return $this->t( $fixed[ $value ] ); }
		[ $side, $id ] = explode( ':', $value );
		$anchors = $toolbar ? $this->toolbar_anchors : $this->menu_anchors;
		if ( ! $toolbar && isset( $anchors[ $id ] ) ) { global $menu; if ( ! in_array( $anchors[ $id ]['slug'], array_column( (array) $menu, 2 ), true ) ) { unset( $anchors[ $id ] ); } }
		return isset( $anchors[ $id ] ) ? sprintf( $this->t( 'before' === $side ? 'Before %s' : 'After %s' ), $anchors[ $id ]['label'] ) : $this->t( 'Anchor unavailable; using the default position' );
	}

	/** Render site-wide placement controls inside the existing preview form. @return void */
	private function placement_settings() {
		echo '<fieldset class="psm-placement"><legend>' . esc_html( $this->t( 'Your hub position' ) ) . '</legend><p>' . esc_html( $this->t( 'These positions apply to this site for all users. Only administrators can change them. Missing anchors use the default position.' ) ) . '</p><div class="psm-placement-fields">';
		foreach ( [ 'menu_position' => 'Admin menu position', 'toolbar_position' => 'Toolbar position' ] as $key => $label ) {
			$toolbar = 'toolbar_position' === $key; $current = $this->config[ $key ];
			$options = [ 'default' => $this->position_label( 'default', $toolbar ) ];
			if ( $toolbar ) { foreach ( [ 'first', 'right' ] as $value ) { $options[ $value ] = $this->position_label( $value, true ); } }
			foreach ( $toolbar ? $this->toolbar_anchors : $this->menu_anchors as $id => $anchor ) {
				if ( ! $toolbar ) { global $menu; if ( ! in_array( $anchor['slug'], array_column( (array) $menu, 2 ), true ) ) { continue; } }
				foreach ( $toolbar ? [ 'after' ] : [ 'before', 'after' ] as $side ) { $options[ $side . ':' . $id ] = $this->position_label( $side . ':' . $id, $toolbar ); }
			}
			if ( ! isset( $options[ $current ] ) ) { $options[ $current ] = $this->position_label( $current, $toolbar ); }
			echo '<label>' . esc_html( $this->t( $label ) ) . '<select name="' . esc_attr( $key ) . '">';
			foreach ( $options as $value => $text ) { echo '<option value="' . esc_attr( $value ) . '" ' . selected( $current, $value, false ) . '>' . esc_html( $text ) . '</option>'; }
			echo '</select></label>';
		}
		echo '</div></fieldset>';
	}

	/**
	 * Suggest current groups by common task vocabulary; never claim compatibility.
	 * @return array Template labels and permitted current group IDs.
	 */
	public function selection_templates() {
		$out = [];
		foreach ( [ 'maintenance' => [ 'Maintenance', 'backup|cache|sicher|security|smtp|mail|redirect|optim|wartung' ], 'content' => [ 'Content', 'seo|content|inhalt|form|media|member|role|rolle|snippet' ], 'building' => [ 'Site building', 'bricks|elementor|oxygen|divi|beaver|breakdance|astra|acf|advanced.custom.fields|meta.box|metabox|spectra|block|builder' ] ] as $key => $definition ) {
			$ids = []; $reasons = [];
			foreach ( $this->groups as $id => $group ) { if ( preg_match( '/' . $definition[1] . '/iu', $group['label'] . ' ' . $group['slug'] . ' ' . $group['metadata']['name'], $match ) ) { $ids[] = $id; $reasons[] = $group['label'] . ': ' . sprintf( $this->t( 'Name or menu contains “%s”.' ), $match[0] ); } }
			$out[ $key ] = [ 'label' => $this->t( $definition[0] ), 'groups' => $ids, 'reasons' => $reasons ];
		}
		foreach ( $this->config['profiles'] as $id => $profile ) { $out[ $id ] = [ 'label' => $this->t( 'Saved workspace' ) . ': ' . $profile['name'], 'groups' => array_values( array_intersect( $profile['groups'], array_keys( $this->groups ) ) ), 'reasons' => [ $this->t( 'Uses the groups saved in this workspace that are available on this site.' ) ] ]; }
		return $out;
	}

	/** Render progressive-enhancement selection controls; native selects remain usable. @return void */
	private function selection_tools() {
		echo '<div class="psm-selection-tools" data-psm-selection-tools hidden><div class="psm-selection-filters"><label>' . esc_html( $this->t( 'Find plugin groups' ) ) . '<input type="search" data-psm-group-search></label><label>' . esc_html( $this->t( 'Show groups' ) ) . '<select data-psm-group-filter>';
		foreach ( [ 'all' => 'All', 'selected' => 'Selected', 'unselected' => 'Not selected', 'review' => 'Review required' ] as $value => $label ) { echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $this->t( $label ) ) . '</option>'; }
		echo '</select></label><label>' . esc_html( $this->t( 'Selection template' ) ) . '<select data-psm-template><option value="">' . esc_html( $this->t( 'Choose a template' ) ) . '</option>';
		foreach ( $this->selection_templates() as $id => $template ) { echo '<option value="' . esc_attr( $id ) . '" data-groups="' . esc_attr( wp_json_encode( $template['groups'] ) ) . '" data-reasons="' . esc_attr( wp_json_encode( $template['reasons'] ) ) . '">' . esc_html( $template['label'] . ' (' . count( $template['groups'] ) . ')' ) . '</option>'; }
		echo '</select></label><button type="button" class="button" data-psm-template-mark>' . esc_html( $this->t( 'Mark template matches' ) ) . '</button></div><p class="description">' . esc_html( $this->t( 'Templates suggest matching groups on this installation, not certified plugin sets. Mark rows, then choose an action. Changes take effect only after preview and apply.' ) ) . ' ' . esc_html( $this->t( 'Actions include marked rows hidden by filters.' ) ) . '</p><div class="psm-selection-actions">';
		foreach ( [ 'link' => 'Link marked groups', 'bundle' => 'Bundle marked groups', 'exclude' => 'Exclude marked groups', 'only' => 'Use only marked groups' ] as $action => $label ) { echo '<button type="button" class="button" data-psm-bulk="' . esc_attr( $action ) . '">' . esc_html( $this->t( $label ) ) . '</button>'; }
		echo '</div><details data-psm-template-reasons hidden><summary>' . esc_html( $this->t( 'Why these groups?' ) ) . '</summary><ul></ul></details><p data-psm-unsaved role="status"></p><p data-psm-selection-summary role="status" aria-live="polite"></p><p data-psm-selection-empty hidden>' . esc_html( $this->t( 'No matching groups. Clear the search or change the filter.' ) ) . '</p></div><p class="description">' . esc_html( $this->t( 'Available groups come from the current permitted admin menus, not every installed plugin. Newly detected groups stay unselected. Saved workspaces and exported hub settings can be reused as templates.' ) ) . ' <a href="' . esc_url( admin_url( 'tools.php?page=plugin-submenu-mover&psm_rescan=1' ) ) . '">' . esc_html( $this->t( 'Read installation again' ) ) . '</a></p>';
	}
}
