<?php
/** Copy into the host adapter and replace the plugin-submenu-mover textdomain with its literal domain. */
defined( 'ABSPATH' ) || exit;
return static function ( string $message ): string {
    // Literal calls let the host's normal translation extractor collect every source string.
    switch ( $message ) {
        case 'Private mode must be boolean.':
            return __( 'Private mode must be boolean.', 'plugin-submenu-mover' );
        case 'Invalid authentication provider.':
            return __( 'Invalid authentication provider.', 'plugin-submenu-mover' );
        case 'The plugin must be installed in a stable slug directory.':
            return __( 'The plugin must be installed in a stable slug directory.', 'plugin-submenu-mover' );
        case 'Invalid GitHub repository URL.':
            return __( 'Invalid GitHub repository URL.', 'plugin-submenu-mover' );
        case 'The private update could not be authorized. Check the repository credentials and refresh updates.':
            return __( 'The private update could not be authorized. Check the repository credentials and refresh updates.', 'plugin-submenu-mover' );
        case 'Could not create the update download file.':
            return __( 'Could not create the update download file.', 'plugin-submenu-mover' );
        case 'The private update download failed. Check credentials and try again.':
            return __( 'The private update download failed. Check credentials and try again.', 'plugin-submenu-mover' );
        case 'Could not access the update filesystem.':
            return __( 'Could not access the update filesystem.', 'plugin-submenu-mover' );
        case 'GitHub release does not contain the plugin main file.':
            return __( 'GitHub release does not contain the plugin main file.', 'plugin-submenu-mover' );
        case 'Could not prepare the GitHub release package.':
            return __( 'Could not prepare the GitHub release package.', 'plugin-submenu-mover' );
        case 'See the release on GitHub.':
            return __( 'See the release on GitHub.', 'plugin-submenu-mover' );
        default:
            return $message;
    }
};
