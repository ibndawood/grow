<?php
/**
 * The Compatibility Checker parent.
 *
 * @package Automattic/WooCommerce/Grow/Tools
 */

namespace Automattic\WooCommerce\Grow\Tools\CompatChecker\v0_0_1\Checks;

defined( 'ABSPATH' ) || exit;

/**
 * The CompatCheck class.
 */
abstract class CompatCheck {

	/**
	 * Array of admin notices.
	 *
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Array of CompatCheck instances.
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * The plugin data.
	 *
	 * @var array
	 */
	protected $plugin_data = array();

	/**
	 * Run checks
	 *
	 * @return bool
	 */
	abstract protected function run_checks();

	/**
	 * Get the instance of the CompatCheck object.
	 *
	 * @return CompatCheck
	 */
	public static function instance() {
		$class = get_called_class();
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class();
		}

		return self::$instances[ $class ];
	}

	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @param string $slug    The slug for the notice.
	 * @param string $class   The CSS class for the notice.
	 * @param string $message The notice message.
	 */
	protected function add_admin_notice( $slug, $class, $message ) {
		// Bail if the user is not a shop manager.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message,
		);
	}

	/**
	 * Compares major version.
	 *
	 * @param string $left     First version number.
	 * @param string $right    Second version number.
	 * @param string $operator An optional operator. The possible operators are: <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne respectively.
	 *
	 * @return int|bool
	 */
	protected function compare_major_version( $left, $right, $operator = null ) {
		$pattern = '/^(\d+\.\d+).*/';
		$replace = '$1.0';

		$left  = preg_replace( $pattern, $replace, $left );
		$right = preg_replace( $pattern, $replace, $right );

		return version_compare( $left, $right, $operator );
	}

	/**
	 * Display admin notices generated by the checker.
	 */
	public function display_admin_notices() {
		$allowed_tags = array(
			'a'      => array(
				'class'  => array(),
				'href'   => array(),
				'target' => array(),
			),
			'strong' => array(),
		);

		foreach ( $this->notices as $key => $notice ) {
			$class   = $notice['class'];
			$message = $notice['message'];
			echo sprintf(
				'<div class="notice notice-%1$s"><p>%2$s</p></div>',
				esc_attr( $class ),
				wp_kses( $message, $allowed_tags )
			);
		}
	}

	/**
	 * Sets the plugin data.
	 *
	 * @param array $plugin_data The plugin data.
	 */
	protected function set_plugin_data( $plugin_data ) {
		$defaults          = array(
			'Name'        => '',
			'Version'     => '',
			'RequiresWP'  => '',
			'RequiresPHP' => '',
			'RequiresWC'  => '',
			'TestedWP'    => '',
			'TestedWC'    => '',
		);
		$this->plugin_data = wp_parse_args( $plugin_data, $defaults );
	}

	/**
	 * Determines if the plugin is WooCommerce compatible.
	 *
	 * @param array $plugin_data The plugin data.
	 *
	 * @return bool
	 */
	public function is_compatible( $plugin_data ) {
		$this->set_plugin_data( $plugin_data );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ), 20 );
		return $this->run_checks();
	}
}
