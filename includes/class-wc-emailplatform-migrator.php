<?php 

/**
 * WooCommerce eMailPlatform plugin migrator class
 */
final class WC_Emailplatform_Migrator {

	const VERSION_KEY = 'wc_emailplatform_version';
	const OLD_SETTINGS_KEY = 'woocommerce_emailplatform_settings';

	protected static $versions = array(
	);

	public static function migrate( $target_version ) {

		$old_settings = get_option( self::OLD_SETTINGS_KEY );
		$current_version = get_option( self::VERSION_KEY );

		if ( ! $old_settings && ! $current_version ) {
			// This is a new install, so no need to migrate
			return;
		}

		if ( ! $current_version ) {
			$current_version = '1.0.X';
		}
	
		if ( $current_version !== $target_version ) {

			// error_log( 'Need to migrate from ' . $current_version . ' to ' . $target_version );

			require_once( WC_Emailplatform_DIR . 'includes/migrations/class-emp-wc-migration.php' );

			$start = array_search( $current_version, self::$versions );

			// error_log( 'Starting at migration ' . $start );

			for ($start; $start < count(self::$versions) - 1; $start++) {
			    $next = $start + 1;
			    $current_version = self::$versions[$start];
				$next_version = self::$versions[$next];

				// error_log( 'Migrating from ' . $current_version . ' to ' . $target_version );
				// 
			    if ( file_exists( WC_Emailplatform_DIR . "includes/migrations/class-emp-wc-migration-from-$current_version-to-$next_version.php" ) ) {

					require_once( WC_Emailplatform_DIR . "includes/migrations/class-emp-wc-migration-from-$current_version-to-$next_version.php" );

					$migration_name = 'WC_Emailplatform_Migration_From_'. self::clean_version( $current_version ) .'_To_'. self::clean_version( $next_version );

					$migration = new $migration_name( $current_version, $next_version );
					if ( $migration->up() ) {
						// Update the current plugin version
						update_option( self::VERSION_KEY, $next_version );
					}

				}
			}
			//update_option( self::VERSION_KEY, $target_version );

		}

	}

	private static function clean_version( $version ) {
		return str_replace( '.', '_', $version );
	}

}