<?php

class WPORG_CheckTheme {

	public $themeHelper;

	/**
	 * Sends a theme through Theme Check.
	 *
	 * @param array $files All theme files to check.
	 * @return bool Whether the theme passed the checks.
	 */
	public function check_theme( $files ) {

		if( ! defined( 'THEME_CHECK_FOLDER' ) ) {
			exit( 'Missing "THEME_CHECK_FOLDER" from config.' );
		}

		// Load the theme checking code.
		if ( ! function_exists( 'run_themechecks' ) ) {
			include_once WP_PLUGIN_DIR . '/' . THEME_CHECK_FOLDER . '/checkbase.php';
		}

		list( $php_files, $css_files, $other_files ) = $this->themeHelper->separate_files( $files );

		// Run the checks.
		$result = run_themechecks( $php_files, $css_files, $other_files );

		return $result;
	}

	/**
	 * Formats the error and removes html.
	 *
	 * @param string $message The message.
	 * @return string Message after string replacements.
	 */
	public function clean_message( $message ) {
		$cleaned = str_replace( array( '<span class="tc-lead tc-required">', '</span>', "<span class='tc-lead tc-required'>", '<span class="tc-lead tc-recommended">', "<span class='tc-lead tc-recommended'>" ), '', $message );
		$cleaned = str_replace( array( '<strong>', '</strong>' ), '`', $cleaned );
		$cleaned = preg_replace( '!<a href="([^"]+)".+</a>!i', '$1', $cleaned );
		$cleaned = html_entity_decode( strip_tags( $cleaned ) );
		return $cleaned;
	}

	/**
	 * Loops through all the errors and passes them to cleaning function.
	 *
	 * @param array $messages List of messages.
	 * @return array Same messages, cleaned.
	 */
	public function clean_messages( $messages ) {
		$cleaned_messages = array();

		foreach ( $messages as $message ) {
			array_push( $cleaned_messages, $this->clean_message( $message ) );
		}

		return $cleaned_messages;
	}

	/**
	 * Print GitHub actions formatted messages to the console.
	 *
	 * @param string $type The message type. Ie "error/warning".
	 * @param array  $messages The list of messages.
	 */
	public function print_message( $type, $messages ) {
		echo '::' . esc_attr( $type ) . '::';

		$eol = ( ! defined( 'DEV_MODE' ) ? '%0A' : PHP_EOL );

		foreach ( $messages as $key => $val ) {
			$implode = implode( $eol, $val );
			echo '[ ' . esc_attr( $key ) . ' ] '. $eol . $implode;
			echo $eol;
			echo $eol;
		}
	}

	/**
	 * Determines if the start string matches
	 */
	public function starts_with( $haystack, $needle ) {
		return substr_compare( $haystack, $needle, 0, strlen( $needle ) ) === 0;
	}

	/**
	 * If the array doesn't exist, create the array and add the item to it.
	 *
	 * @param array  $arr Associate array to add to.
	 * @param string $key Key.
	 * @return string $item_to_add Item added to the array.
	 */
	public function add_to_array( &$arr, $key, $item_to_add ) {
		if ( ! array_key_exists( $key, $arr ) ) {
			$arr[ $key ] = array();
		}

		array_push( $arr[ $key ], $item_to_add );

		return true;
	}

	/**
	 * This function looks at the global themechecks array for errors, formats and prints them
	 */
	public function display_results() {
		global $themechecks; // global that exists in the theme-check plugin

		$error_list = array();
		$warning_list = array();

		foreach ( $themechecks as $check ) {
			if ( $check instanceof themecheck ) {
				$error = $check->getError();
				$test_id = get_class( $check );

				if ( count( $error ) > 0 ) {
					$messages = $this->clean_messages( $error );

					foreach ( $messages as $clean_message ) {

						// All strings that contain REQUIRED are considered errors
						if ( $this->starts_with( $clean_message, 'REQUIRED:' ) ) {
							$this->add_to_array( $error_list, $test_id, $clean_message );

							// All string that contain RECOMMENDED or INFO are considered warnings
						} else if ( $this->starts_with( $clean_message, 'RECOMMENDED:' ) || $this->starts_with( $clean_message, 'INFO:' ) ) {
							$this->add_to_array( $warning_list, $test_id, $clean_message );
						}
					}
				}
			}
		}

		if( count( $error_list ) > 0) {
			$this->print_message( 'error', $error_list );
		}

		echo PHP_EOL;
		echo PHP_EOL;

		if( count( $warning_list ) > 0) {
			$this->print_message( 'warning', $warning_list );
		}

		if ( count( $error_list ) > 0 ) {
			// Turning the exit off for now as we test this.
			//exit( 1 );
		}
	}

	/**
	 * Get set up to run tests on the uploaded theme.
	 */
	public function __construct() {
		include './helpers/ThemeHelper.php';

		$this->themeHelper = new ThemeHelper();

	}

	/**
	 * Run prepare theme and run theme-check
	 */
	public function run_check() {
		$theme_files = $this->themeHelper->get_all_files( './test-theme/' );
		$passes = $this->check_theme( $theme_files );

		$this->display_results();
	}

}

// run the test
$w = new WPORG_CheckTheme();

$w->run_check();


