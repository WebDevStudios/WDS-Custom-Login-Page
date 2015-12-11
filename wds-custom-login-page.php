<?php
/**
 * Plugin Name: WDS Custom Login Page
 * Plugin URI: http://webdevstudios.com
 * Description: Plugin that adds a custom login page.
 * Author: WebDevStudios
 * Author URI: http://webdevstudios.com
 * Version: 1.1
 * License: GPLv2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WDS_Custom_Login_Page' ) ) {

	class WDS_Custom_Login_Page {

		/**
		 * Construct function to get things started.
		 */
		public function __construct() {

			// Setup some base variables for the plugin.
			$this->basename       = plugin_basename( __FILE__ );
			$this->directory_path = plugin_dir_path( __FILE__ );
			$this->directory_url  = plugins_url( dirname( $this->basename ) );

			// Include required files.
			require_once( $this->directory_path . '/inc/options.php' );
			require_once( $this->directory_path . '/inc/cmb2/init.php' );

			// Activation/Deactivation Hooks.
			register_activation_hook( __FILE__, array( $this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

			// Make sure we have our requirements, and disable the plugin if we do not have them.
			add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		}

		/**
		 * Run our hooks
		 */
		public function do_hooks() {
			add_action( 'wp_logout', array( $this, 'logout_page' ) );
			add_filter( 'authenticate', array( $this, 'verify_username_password' ), 1, 3 );
			add_action( 'wp_login_failed', array( $this, 'login_failed' ) );
			add_action( 'init', array( $this, 'redirect_login_page' ) );
			add_action( 'wds_insert_login_page', array( $this, 'insert_login_page' ) );
			add_filter( 'the_content', array( $this, 'insert_login_form' ) );
			add_shortcode( 'login_form', array( $this, 'render_login_form' ) );
		}

		/**
		 * Activation hook for the plugin.
		 */
		public function activate() {

			// Check if a login page exists, if not, create one.
			if ( ! $this->get_page_by_name( 'login' ) && ! $this->get_page_by_name( wds_login_slug() ) ) {
				do_action( 'wds_insert_login_page' );
			}

		}

		/**
		 * Deactivation hook for the plugin.
		 */
		public function deactivate() {

		}

		/**
		 * Check that all plugin requirements are met
		 *
		 * @return boolean
		 */
		public static function meets_requirements() {
			// Make sure we have CMB so we can use it.
			if ( ! defined( 'CMB2_LOADED' ) ) {
				return false;
			}

			// We have met all requirements.
			return true;
		}

		/**
		 * Check if the plugin meets requirements and
		 * disable it if they are not present.
		 */
		public function maybe_disable_plugin() {
			if ( ! $this->meets_requirements() ) {
				// Display our error.
				echo '<div id="message" class="error">';
				echo '<p>' . sprintf( __( 'WDS Simple Page Builder requires CMB2 but could not find it. The plugin has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'wds-simple-page-builder' ), admin_url( 'plugins.php' ) ) . '</p>';
				echo '</div>';

				// Deactivate our plugin.
				deactivate_plugins( $this->basename );
			}
		}

		/**
		 * Create the login page
		 */
		public function insert_login_page() {
			$page = array(
				'post_name'   => 'login',
				'post_title'  => __( 'Login', 'wds-custom-login-page' ),
				'post_type'   => 'page',
				'post_status' => 'publish',
			);

			$post_id = wp_insert_post( $page, false );

			// Make sure the post id exists and there was no error.
			if ( $post_id && ! is_wp_error( $post_id ) ) {

				// Check for a login template file. page-login.php works, too, but we don't need to save that as the page template.
				if ( locate_template( 'template-login.php', false, false ) ) {
					update_post_meta( $post_id, '_wp_page_template', 'template-login.php' );
				}

				update_option( '_wds_custom_login_page', $post_id );

			}

			return;
		}


		/**
		 * Send to login page on logout
		 */
		public function logout_page() {
			wp_redirect( wds_login_page() . '?login=false' );
			exit;
		}

		/**
		 * Check if a username or password were left blank, redirect to login page if they were
		 */
		public function verify_username_password( $user, $username, $password ) {

			// If they were actually getting here because of an empty login.
			if ( '' == $username || '' == $password ) {
				wp_redirect( wds_login_page() . '?login=empty' );
				exit;
			}

		}

		/**
		 * If login has failed, redirect to the login page
		 */
		public function login_failed() {
			wp_redirect( wds_login_page() . '?login=failed' );
			exit;
		}

		/**
		 * Redirect all login requests to...you guessed it...the login page
		 */
		public function redirect_login_page() {
			$page_viewed = basename($_SERVER['REQUEST_URI']);

			if ( 'wp-login.php' == $page_viewed && $_SERVER['REQUEST_METHOD'] == 'GET') {
				wp_redirect( wds_login_page() );
				exit;
			}
		}

		/**
		 * Helper function to get a specific page by a page slug
		 *
		 * @param string $slug The slug to look for.
		 * @link  https://wordpress.org/support/topic/how-to-check-if-page-exists?replies=8#post-466937
		 */
		public function get_page_by_name( $slug = '' ) {
			$pages = get_pages();

			foreach ( $pages as $page ) {
				if ( $slug == $page->post_name ) {
					return $page;
				}

				return false;
			}
		}

		/**
		 * Default login form if there's no login template found
		 */
		public function insert_login_form( $content ) {
			// Bail if we aren't on the login page.
			if ( ! is_admin() && ! is_page( 'login' ) && ! is_page( wds_login_slug() ) ) {
				return $content;
			}

			// If there's a defined login page already in the theme, let that page template deal with the login form.
			if ( locate_template( 'page-login.php', false, false ) || locate_template( 'page-' . wds_login_slug(), false, false ) ) {
				return $content;
			}

			// If there's a template file matching either template-login.php or template-{login page slug}.php, let that page template deal with the login form.
			if ( 'template-login.php' == get_post_meta( get_the_ID(), '_wp_page_template', true ) || 'template-' . wds_login_slug() == get_post_meta( get_the_ID(), '_wp_page_template', true ) ) {
				return $content;
			}

			// Get the login query string, if it exists.
			$login = ( isset( $_GET['login'] ) ) ? $_GET['login'] : 0;

			$message = '';

			// If the current user is already logged in, give them the opportunity to log out.
			if ( ! $login && is_user_logged_in() ) {

				$message = '<p class="login-msg">' . sprintf( __( 'You are logged in. Would you like to <a href="%s">log out</a>?', 'wds-custom-login-page' ), wp_logout_url( home_url() ) ) . '</p>';

				return $message;

			}


			if ( $login ) {

				$message = $this->get_message( $login );

			}

			// Return the post content (if there is any), the message (if there is any), and the login form with the passed args.
			return $content . $message . $this->render_login_form();

		}

		/**
		 * Return one of several possible messages depending on what the login request returns.
		 * @param  string $login The URL query string value for the login parameter.
		 * @return string        A message about the login attempt.
		 */
		public function get_message( $login = '' ) {
			$message = '';

			// Get the login query string, if it exists.
			if ( '' == $login ) {
				$login = ( isset( $_GET['login'] ) ) ? $_GET['login'] : 0;
			}

			// If there's still no login query string, bail.
			if ( ! $login ) {
				return;
			}

			switch ( $login ) {

				case 'failed' :
					$message .= '<p class="login-msg">' . __( '<strong>ERROR:</strong> Invalid username and/or password.', 'wds-custom-login-page' ) . '</p>';
					break;

				case 'empty' :
					$message .= '<p class="login-msg">' . __( '<strong>ERROR:</strong> Username and/or Password is empty.', 'wds-custom-login-page' ) . '</p>';
					break;

				case 'false' :
					$message .= '<p class="login-msg">' . __( 'You have been logged out. You will be redirected to the home page in 5 seconds.', 'wds-custom-login-page' ) . '</p>';
					$message .= '<p><a href="' . home_url() . '">' . __( 'Go there now.', 'wds-custom-login-page' ) . '</a></p>';
					$redirect = '<script type="text/javascript">setTimeout("window.location=\'' . home_url() . '\'",5000);</script>';
					return $message . $redirect;

				default :
					break;
			}

			return $message;
		}

		/**
		 * Function to display the actual login form
		 *
		 * @param string $redirect Optional page to redirect the user to after logging in. Defaults to site home.
		 * @param bool   $echo     Whether to echo or return the login form. Default is false, return the login form.
		 */
		public function render_login_form( $redirect = '', $echo = false ) {

			// If the user is already logged in, we don't need a form.
			if ( is_user_logged_in() )
				return;

			// Set a default for the redirect if no value was passed.
			if ( '' == $redirect ) {
				$redirect = home_url();
			}

			// Set up the arguments.
			$args = array(
				'redirect'    => $redirect,
				'id_username' => 'user',
				'id_password' => 'pass',
				'echo'        => $echo, // Return, don't echo.
			);

			$forgot_password = '<span class="recover-password"><a href="' . wp_lostpassword_url( $redirect ) . '" title="' . __( 'Lost password', 'maintainn' ) . '">' . __( 'Lost password?', 'wds-custom-login-page' ) . '</a></span>';

			// Return the form.
			return wp_login_form( $args ) . $forgot_password;
		}

	}

	$_GLOBALS['WDS_Custom_Login_Page'] = new WDS_Custom_Login_Page;
	$_GLOBALS['WDS_Custom_Login_Page']->do_hooks();
}

/**
 * Optional wrapper function for calling this class
 */
function wds_custom_login_page() {
	return new WDS_Custom_Login_Page;
}

/**
 * Public template tag to just spit out the login form. Wrapper for
 * WDS_Custom_Login_Page::render_login_form()
 *
 * @param string $redirect Optional page to redirect the user to after logging in. Defaults to site home.
 * @param bool   $echo     Whether to echo or return the login form. Default is false, return the login form.
 */
function wds_login_form( $redirect = '', $echo = false ) {
	return wds_custom_login_page()->render_login_form( $redirect, $echo );
}

/**
 * Return one of several possible messages depending on what the login request returns.
 * @return string        A message about the login attempt.
 */
function wds_login_form_message( $echo = false ) {
	if ( $echo ) {
		echo wds_custom_login_page()->get_message(); // WPCS: XSS ok.
		return;
	}
	return wds_custom_login_page()->get_message();
}
