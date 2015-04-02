<?php
/**
 * Simple options page for custom login page
 * @version 0.1.0
 */
class WDS_Login_Page_Options {

	/**
	 * Login page slug. This will be configurable if we want to change the page slug from what was created
	 */
	public $login_slug = '';

	/**
 	 * Option key, and option page slug
 	 * @var string
 	 */
	private $key = 'wds_login_options';

	/**
 	 * Options page metabox id
 	 * @var string
 	 */
	private $metabox_id = 'wds_login_option_metabox';

	/**
	 * Options Page title
	 * @var string
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Constructor
	 * @since 0.1.0
	 */
	public function __construct() {
		// Set our title
		$this->title = __( 'Login Page Options', 'maintainn' );
		$this->login_slug     = 'login';
	}

	/**
	 * Initiate our hooks
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_init', array( $this, 'add_options_page_metabox' ) );
	}


	/**
	 * Register our setting to WP
	 * @since  0.1.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Add menu options page
	 * @since 0.1.0
	 */
	public function add_options_page() {
		$this->options_page = add_submenu_page( 'options-general.php', $this->title, $this->title, 'manage_options', $this->key, array( $this, 'admin_page_display' ) );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 * @since  0.1.0
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2_options_page <?php echo $this->key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 * @since  0.1.0
	 */
	function add_options_page_metabox() {

		$cmb = new_cmb2_box( array(
			'id'      => $this->metabox_id,
			'hookup'  => false,
			'show_on' => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( $this->key, )
			),
		) );

		// Set our CMB2 fields

		$cmb->add_field( array(
			'name'    => __( 'Login Page', 'maintainn' ),
			'desc'    => __( 'field description (optional)', 'maintainn' ),
			'id'      => 'login_slug',
			'type'    => 'select',
			'options' => $this->get_page_list(),
			'default' => wds_login_slug(),
		) );

	}

	/**
	 * Get a list of pages to use in CMB options
	 */
	public function get_page_list() {
		$page_list = array();
		$pages = get_pages();

		foreach( $pages as $page ) {
			$page_list[$page->post_name] = $page->post_title;
		}

		return $page_list;
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

}

/**
 * Helper function to get/return the WDS_Login_Page_Options object
 * @since  0.1.0
 * @return WDS_Login_Page_Options object
 */
function wds_login_options() {
	static $object = null;
	if ( is_null( $object ) ) {
		$object = new WDS_Login_Page_Options();
		$object->hooks();
	}

	return $object;
}

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 * @param  string  $key Options array key
 * @return mixed        Option value
 */
function wds_login_get_option( $key = '' ) {
	return cmb2_get_option( wds_login_options()->key, $key );
}

function wds_login_page() {
	return home_url( '/' . wds_login_slug() . '/' );
}

function wds_login_slug() {
	$login_slug = get_option( 'login_slug', 'login' );
	$login_slug = apply_filters( 'wds_login_slug', $login_slug );
	return $login_slug;
}

// Get it started
wds_login_options();

