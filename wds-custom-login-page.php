<?php
/**
 * Plugin Name: WDS Custom Login Page
 * Plugin URI: http://webdevstudios.com
 * Description: Plugin that adds a custom login page.
 * Author: WebDevStudios
 * Author URI: http://webdevstudios.com
 * Version: 1.0.0
 * License: GPLv2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WDS_Custom_Login_page' ) ) {

	class WDS_Custom_Login_page {

		/**
		 * Login page slug. This will always exist, one way or another.
		 */
		private $login_page = '';

		/**
		 * Construct function to get things started.
		 */
		public function __construct() {

			// Setup some base variables for the plugin
			$this->basename       = plugin_basename( __FILE__ );
			$this->directory_path = plugin_dir_path( __FILE__ );
			$this->directory_url  = plugins_url( dirname( $this->basename ) );
			$this->login_page     = home_url( '/login/' );

			// Activation/Deactivation Hooks
			register_activation_hook( __FILE__, array( $this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		}

		/**
		 * Run our hooks
		 */
		public function do_hooks() {
			add_action( 'wp_logout', array( $this, 'logout_page' ) );
			add_filter( 'authenticate', array( $this, 'verify_username_password' ), 1, 3);
			add_action( 'wp_login_failed', array( $this, 'login_failed' ) );
			add_action( 'init', array( $this, 'redirect_login_page' ) );
			add_action( 'wds_insert_login_page', array( $this, 'insert_login_page' ) );
			add_filter( 'the_content', array( $this, 'insert_login_form' ) );
		}

		/**
		 * Activation hook for the plugin.
		 */
		public function activate() {

			// check if a login page exists, if not, create one
			if ( !$this->get_page_by_name( 'login' ) ) {
				do_action( 'wds_insert_login_page' );
			}

		}

		/**
		 * Deactivation hook for the plugin.
		 */
		public function deactivate() {

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

			// make sure the post id exists and there was no error
			if ( $post_id && ! is_wp_error( $post_id ) ) {

				// check for a login template file. page-login.php works, too, but we don't need to save that as the page template
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
			wp_redirect( $this->login_page . '?login=false' );
			exit;
		}

		/**
		 * Check if a username or password were left blank, redirect to login page if they were
		 */
		public function verify_username_password( $user, $username, $password ) {

			// if they were actually getting here because of an empty login
			if ( '' == $username || '' == $password ) {
				wp_redirect( $this->login_page . "?login=empty" );
				exit;
			}

		}

		/**
		 * If login has failed, redirect to the login page
		 */
		public function login_failed() {
			wp_redirect( $this->login_page . '?login=failed' );
			exit;
		}

		/**
		 * Redirect all login requests to...you guessed it...the login page
		 */
		public function redirect_login_page() {
			$page_viewed = basename($_SERVER['REQUEST_URI']);

			if ( 'wp-login.php' == $page_viewed && $_SERVER['REQUEST_METHOD'] == 'GET') {
				wp_redirect( $this->login_page );
				exit;
			}
		}

		/**
		 * Helper function to get a specific page by a page slug
		 *
		 * @param string $slug The slug to look for
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
			// bail if we aren't on the login page
			if ( ! is_admin() && ! is_page( 'login' ) ) {
				return $content;
			}

			if ( locate_template( 'template-login.php', false, false ) ) {
				return $content;
			}

			// get the login query string, if it exists
			$login = ( isset( $_GET['login'] ) ) ? $_GET['login'] : 0;

			$message = '';

			// if the current user is already logged in, give them the opportunity to log out
			if ( ! $login && is_user_logged_in() ) {

				$message = '<p class="login-msg">' . sprintf( __( 'You are logged in. Would you like to <a href="%s">log out</a>?', 'wds-custom-login-page' ), wp_logout_url( home_url() ) ) . '</p>';

				return $message;

			}


			$args = array(
				'redirect'    => home_url(),
				'id_username' => 'user',
				'id_password' => 'pass',
				'echo'        => false, // return, don't echo
			);

			if ( $login ) {

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
						return $content. $message . $redirect;

					default :
						break;
				}

			}

			// return the post content (if there is any), the message (if there is any), and the login form with the passed args
			return $content . $message . wp_login_form( $args );

		}

	}

	$_GLOBALS['WDS_Custom_Login_page'] = new WDS_Custom_Login_page;
	$_GLOBALS['WDS_Custom_Login_page']->do_hooks();
}

/**
 * Optional wrapper function for calling this class
 */
function wds_login_page() {
	return new WDS_Custom_Login_page;
}