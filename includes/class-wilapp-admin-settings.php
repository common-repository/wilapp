<?php
/**
 * Library for admin settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Library for WooCommerce Settings
 *
 * Settings in order to sync products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    0.1
 */
class Wilapp_Admin_Settings {
	/**
	 * Settings
	 *
	 * @var array
	 */
	private $wilapp_settings;

	/**
	 * Construct of class
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices_action' ) );
	}

	/**
	 * Admin Scripts
	 *
	 * @return void
	 */
	public function admin_scripts() {
		wp_enqueue_style(
			'wilapp-admin',
			WILAPP_PLUGIN_URL . 'includes/assets/wilapp-admin.css',
			array(),
			WILAPP_VERSION
		);
	}

	/**
	 * Adds plugin page.
	 *
	 * @return void
	 */
	public function add_plugin_page() {
		add_submenu_page(
			'options-general.php',
			__( 'Wilapp', 'wilapp' ),
			__( 'Wilapp', 'wilapp' ),
			'manage_options',
			'wilapp-options',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Admin notices
	 *
	 * @return void
	 */
	public function admin_notices_action() {
		settings_errors( 'wilapp_notification_error' );
	}

	/**
	 * Create admin page.
	 *
	 * @return void
	 */
	public function create_admin_page() {
		global $helpers_wilapp;
		$this->wilapp_settings = get_option( 'wilapp_options' );
		?>
		<div class="header-wrap">
			<div class="wrapper">
				<h2 style="display: none;"></h2>
				<div id="nag-container"></div>
				<div class="header wilapp-header">
					<div class="logo">
						<img src="<?php echo esc_url( WILAPP_PLUGIN_URL ) . 'includes/assets/logo.svg'; ?>" height="50" width="154"/>
						<h2><?php esc_html_e( 'Wilapp Settings', 'wilapp' ); ?></h2>
					</div>
					<div class="connection">
						<p>
						<?php
						$login_result = $helpers_wilapp->login();
						if ( 'error' === $login_result['status'] ) {
							echo '<svg width="24" height="24" viewBox="0 0 24 24" class="license-icon"><defs><circle id="license-unchecked-a" cx="8" cy="8" r="8"></circle></defs><g fill="none" fill-rule="evenodd" transform="translate(4 4)"><use fill="#dc3232" xlink:href="#license-unchecked-a"></use><g fill="#FFF" transform="translate(4 4)"><rect width="2" height="8" x="3" rx="1" transform="rotate(-45 4 4)"></rect><rect width="2" height="8" x="3" rx="1" transform="rotate(-135 4 4)"></rect></g></g></svg>';
							esc_html_e( 'ERROR: We could not connect to Wilapp.', 'wilapp' );
							echo ' ' . esc_html( $login_result['data'] );
						} else {
							echo '<svg width="24" height="24" viewBox="0 0 24 24" class="icon-24 license-icon"><defs><circle id="license-checked-a" cx="8" cy="8" r="8"></circle></defs><g fill="none" fill-rule="evenodd" transform="translate(4 4)"><mask id="license-checked-b" fill="#fff"><use xlink:href="#license-checked-a"></use></mask><use fill="#52AA59" xlink:href="#license-checked-a"></use><path fill="#FFF" fill-rule="nonzero" d="M7.58684811,11.33783 C7.19116948,11.7358748 6.54914653,11.7358748 6.15365886,11.33783 L3.93312261,9.10401503 C3.53744398,8.70616235 3.53744398,8.06030011 3.93312261,7.66244744 C4.32861028,7.26440266 4.97063323,7.26440266 5.36631186,7.66244744 L6.68931454,8.99316954 C6.78918902,9.09344917 6.95131795,9.09344917 7.0513834,8.99316954 L10.6336881,5.38944268 C11.0291758,4.9913979 11.6711988,4.9913979 12.0668774,5.38944268 C12.2568872,5.5805887 12.3636364,5.83993255 12.3636364,6.11022647 C12.3636364,6.3805204 12.2568872,6.63986424 12.0668774,6.83101027 L7.58684811,11.33783 Z" mask="url(#license-checked-b)"></path></g></svg>';

							esc_html_e( 'Connected to Wilapp', 'wilapp' );
							$professional_name = isset( $login_result['data']['public_name'] ) ? $login_result['data']['public_name'] : '';
							echo ': ' . esc_html( $professional_name );
						}
						?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<div class="wrap">
			<p></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'admin_wilapp_settings' );
					do_settings_sections( 'wilapp_options' );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Init for page
	 *
	 * @return void
	 */
	public function page_init() {

		/**
		 * ## API Settings
		 * --------------------------- */
		register_setting(
			'admin_wilapp_settings',
			'wilapp_options',
			array( $this, 'sanitize_fields_api' )
		);

		add_settings_section(
			'admin_wilapp_settings',
			__( 'Settings for integration to Wilapp', 'wilapp' ),
			array( $this, 'admin_section_api_info' ),
			'wilapp_options'
		);

		add_settings_field(
			'wilapp_username',
			__( 'Username', 'wilapp' ),
			array( $this, 'username_callback' ),
			'wilapp_options',
			'admin_wilapp_settings'
		);

		add_settings_field(
			'wilapp_password',
			__( 'Password', 'wilapp' ),
			array( $this, 'password_callback' ),
			'wilapp_options',
			'admin_wilapp_settings'
		);

		add_settings_field(
			'wilapp_terms',
			__( 'Terms and conditions page', 'wilapp' ),
			array( $this, 'terms_callback' ),
			'wilapp_options',
			'admin_wilapp_settings'
		);

		add_settings_field(
			'wilapp_privacy',
			__( 'Privacy policy page', 'wilapp' ),
			array( $this, 'privacy_callback' ),
			'wilapp_options',
			'admin_wilapp_settings'
		);
	}

	/**
	 * Sanitize fiels before saves in DB
	 *
	 * @param array $input Input fields.
	 * @return array
	 */
	public function sanitize_fields_api( $input ) {
		global $helpers_wilapp;
		$sanitary_values = array();

		if ( isset( $input['username'] ) ) {
			$sanitary_values['username'] = sanitize_text_field( $input['username'] );
		}

		if ( isset( $input['password'] ) ) {
			$sanitary_values['password'] = sanitize_text_field( $input['password'] );
		}

		if ( isset( $input['terms'] ) ) {
			$sanitary_values['terms'] = sanitize_text_field( $input['terms'] );
		}

		if ( isset( $input['privacy'] ) ) {
			$sanitary_values['privacy'] = sanitize_text_field( $input['privacy'] );
		}

		return $sanitary_values;
	}

	/**
	 * Info for neo automate section.
	 *
	 * @return void
	 */
	public function admin_section_api_info() {
		esc_html_e( 'Put the connection API key settings in order to connect external data.', 'wilapp' );
	}

	/**
	 * Username callback
	 *
	 * @return void
	 */
	public function username_callback() {
		printf(
			'<input class="regular-text" type="text" name="wilapp_options[username]" id="wilapp_username" value="%s">',
			isset( $this->wilapp_settings['username'] ) ? esc_attr( $this->wilapp_settings['username'] ) : ''
		);
	}

	/**
	 * Password callback
	 *
	 * @return void
	 */
	public function password_callback() {
		printf(
			'<input class="regular-text" type="password" name="wilapp_options[password]" id="password" value="%s">',
			isset( $this->wilapp_settings['password'] ) ? esc_attr( $this->wilapp_settings['password'] ) : ''
		);
	}

	/**
	 * Terms and Conditions page
	 *
	 * @return void
	 */
	public function terms_callback() {
		$args_query  = array(
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		$posts_array = get_posts( $args_query );
		echo '<select id="wilapp_terms" name="wilapp_options[terms]">';
		echo '<option value=""></option>';
		foreach ( $posts_array as $post_single ) {
			echo '<option value="' . esc_html( $post_single->ID ) . '"';
			selected( $this->wilapp_settings['terms'], $post_single->ID );
			echo '>' . esc_html( $post_single->post_title ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Privacy Policy page
	 *
	 * @return void
	 */
	public function privacy_callback() {
		$args_query  = array(
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		$posts_array = get_posts( $args_query );
		echo '<select id="wilapp_privacy" name="wilapp_options[privacy]">';
		echo '<option value=""></option>';
		foreach ( $posts_array as $post_single ) {
			echo '<option value="' . esc_html( $post_single->ID ) . '"';
			selected( $this->wilapp_settings['privacy'], $post_single->ID );
			echo '>' . esc_html( $post_single->post_title ) . '</option>';
		}
		echo '</select>';
	}
}

new Wilapp_Admin_Settings();


