<?php
/**
 * CCOO Registre
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Registre.
 *
 * Class Registre Form.
 *
 * @since 1.0
 */
class WilApp_Wizard {
	/**
	 * Options
	 *
	 * @var array
	 */
	private $wilapp_options;

	/**
	 * Construct of Class
	 */
	public function __construct() {
		$this->wilapp_options = get_option( 'wilapp_options' );

		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_frontend' ) );
		add_shortcode( 'wilapp', array( $this, 'render_form' ) );

		// AJAX Validate Step.
		add_action( 'wp_ajax_wizard_step', array( $this, 'wizard_step' ) );
		add_action( 'wp_ajax_nopriv_wizard_step', array( $this, 'wizard_step' ) );

		// AJAX Validate Submit.
		add_action( 'wp_ajax_validate_submit', array( $this, 'validate_submit' ) );
		add_action( 'wp_ajax_nopriv_validate_submit', array( $this, 'validate_submit' ) );
	}

	/**
	 * Loads Scripts
	 *
	 * @return void
	 */
	public function scripts_frontend() {

		wp_enqueue_style(
			'wilapp-wizard',
			plugins_url( '/assets/wilapp-frontend.css', __FILE__ ),
			array(),
			WILAPP_VERSION
		);

		wp_register_script(
			'wilapp-wizard',
			WILAPP_PLUGIN_URL . 'includes/assets/wilapp-app.js',
			array(),
			WILAPP_VERSION,
			true
		);
		wp_enqueue_script( 'wilapp-wizard' );

		// Form steps AJAX.
		wp_localize_script(
			'wilapp-wizard',
			'AjaxVarStep',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'validate_step_nonce' ),
			)
		);

		// Form steps SUBMIT AJAX.
		wp_localize_script(
			'wilapp-wizard',
			'AjaxVarSubmit',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'validate_submit_nonce' ),
			)
		);
	}
	/**
	 * Renders Form
	 *
	 * @return void
	 */
	public function render_form() {
		global $helpers_wilapp;

		$html  = '<section class="wilapp-wizard">';
		$html .= '<h2>' . __( 'Make an appointment', 'wilapp' ) . '</h2>';
		$html .= '<div class="form-wizard"><form action="" method="post" role="form" autocomplete="off">';

		$login_result = $helpers_wilapp->login();
		if ( 'error' === $login_result['status'] ) {
			return;
		}

		$professional = get_transient( 'wilapp_query_professional' );
		if ( ! $professional ) {
			$professional = ! empty( $login_result['data'] ) ? $login_result['data'] : false;
			set_transient( 'wilapp_query_professional', $login_result['data'], HOUR_IN_SECONDS * 3 );
		}
		$categories = isset( $professional['categories'] ) ? $professional['categories'] : array();

		// Nonce.
		$html .= wp_nonce_field( 'validate_step', 'validate_step_nonce' );

		/**
		 * ## STEP 1 - Category
		 * --------------------------- */
		$html .= '<fieldset class="wizard-fieldset show" data-page="1">';
		$html .= '<h3>' . __( 'Select a category', 'wilapp' ) . '</h3>';

		$html .= '<div class="row"><ul class="options categories">';
		foreach ( $categories as $category ) {
			$html .= '<li class="wilapp-item" data-cat-id="' . esc_attr( $category['id'] ) . '">';
			$html .= '<img src="' . esc_url( $category['image'] ) . '" width="80" height="60" />';
			$html .= esc_html( $category['name'] );
			$html .= '</li>';
		}
		$html .= '</ul></div>';
		$html .= '<div id="response-error-page-1" class="response-error"></div>';
		$html .= '</fieldset>';

		/**
		 * ## STEP 2 - Services
		 * --------------------------- */
		$html .= '<fieldset class="wizard-fieldset" data-page="2">';
		$html .= '<button id="wilapp-step-back" class="icon-left-open">' . esc_html__( 'Back', 'wilapp' ) . '</button>';
		$html .= '<h3>' . __( 'Select a service', 'wilapp' ) . '</h3>';
		$html .= '<div class="row"><ul class="options services">';
		$html .= '</ul></div>';
		$html .= '<div id="response-error-page-1" class="response-error"></div>';
		$html .= '</fieldset>';

		/**
		 * ## STEP 3 - Appointment Day
		 * --------------------------- */
		$html .= '<fieldset class="wizard-fieldset" data-page="3">';
		$html .= '<button id="wilapp-step-back" class="icon-left-open">' . esc_html__( 'Back', 'wilapp' ) . '</button>';
		$html .= '<h3>' . __( 'Select a day', 'wilapp' ) . '</h3>';
		$html .= '<div class="row"><ul class="options appointment-day"></ul></div>';
		$html .= '<div id="response-error-page-3" class="response-error"></div>';
		$html .= '</fieldset>';

		/**
		 * ## STEP 4 - Worker
		 * --------------------------- */
		$html .= '<fieldset class="wizard-fieldset" data-page="4">';
		$html .= '<button id="wilapp-step-back" class="icon-left-open">' . esc_html__( 'Back', 'wilapp' ) . '</button>';
		$html .= '<h3>' . __( 'Select worker', 'wilapp' ) . '</h3>';
		$html .= '<div class="row"><ul class="options appointment-worker"></ul></div>';
		$html .= '<div id="response-error-page-4" class="response-error"></div>';
		$html .= '</fieldset>';

		/**
		 * ## STEP 5 - Appointment Hour
		 * --------------------------- */
		$html .= '<fieldset class="wizard-fieldset" data-page="5">';
		$html .= '<button id="wilapp-step-back" class="icon-left-open">' . esc_html__( 'Back', 'wilapp' ) . '</button>';
		$html .= '<h3>' . __( 'Select a hour', 'wilapp' ) . '</h3>';
		$html .= '<div class="row"><ul class="options appointment-hour"></ul></div>';
		$html .= '<div id="response-error-page-5" class="response-error"></div>';
		$html .= '</fieldset>';

		/**
		 * ## STEP 6 - Appointment
		 * --------------------------- */
		$html .= '<fieldset class="wizard-fieldset" data-page="6" data-worker="">';
		$html .= '<button id="wilapp-step-back" class="icon-left-open">' . esc_html__( 'Back', 'wilapp' ) . '</button>';
		$html .= '<h3>' . __( 'New Appointmet', 'wilapp' ) . '</h3>';
		$html .= '<div class="wilapp-loader"></div>';

		// First and Last name.
		$html .= '<div class="form-group focus-input">';
		$html .= '<label for="name" class="wizard-form-text-label">' . __( 'Name and lastname', 'wilapp' ) . '*</label>';
		$html .= '<input autocomplete="off" type="text" class="form-control wizard-required" id="wilapp-name"';
		$html .= '>';
		$html .= '<div class="wizard-form-error"></div>';
		$html .= '</div>';

		// Phone.
		$html .= '<div class="form-group focus-input">';
		$html .= '<label for="name" class="wizard-form-text-label">' . __( 'Phone', 'wilapp' ) . '*</label>';
		$html .= '<input autocomplete="off" type="text" class="form-control wizard-required" id="wilapp-phone"';
		$html .= '>';
		$html .= '<div class="wizard-form-error"></div>';
		$html .= '</div>';

		// Email.
		$html .= '<div class="form-group focus-input">';
		$html .= '<label for="email" class="wizard-form-text-label">' . __( 'Email', 'wilapp' ) . '*</label>';
		$html .= '<input autocomplete="off" type="email" class="form-control wizard-required" id="wilapp-email"';
		$html .= '>';
		$html .= '<div class="wizard-form-error"></div>';
		$html .= '</div>';

		// Notes.
		$html .= '<div class="form-group focus-input">';
		$html .= '<label for="email" class="wizard-form-text-label">' . __( 'Write a note, e.g. any specific requirements.', 'wilapp' ) . '</label>';
		$html .= '<input autocomplete="off" type="text" class="form-control wizard-required" id="wilapp-notes"';
		$html .= '>';
		$html .= '<div class="wizard-form-error"></div>';
		$html .= '</div>';

		// GDPR.
		$html .= '<div class="form-group focus-input form-conditions">';
		$html .= '<label for="wilapp-gdpr"><input type="checkbox" class="form-check wizard-required" id="wilapp-gdpr"';
		$html .= '>';
		$html .= sprintf(
			// translators: %s link terms, %s link privacy.
			__( 'I’ve read and agree with <a target="_blank" href="%1$s">Terms and Conditions</a> and <a target="_blank" href="%2$s">Privacy Policy</a>.', 'wilapp' ),
			get_the_permalink( $this->wilapp_options['terms'] ),
			get_the_permalink( $this->wilapp_options['privacy'] ),
		);
		$html .= '</label>';
		$html .= '<div class="wizard-form-error"></div>';
		$html .= '</div>';

		// Submit.
		$html .= '<div class="form-group clearfix">';
		$html .= '<a href="#" id="wilapp-submit" class="button form-wizard-submit"><span class="icon-calendar"></span>' . esc_html__( 'Confirm', 'wilapp' ) . '</a>';
		$html .= '<div id="response-error-submit" class="response-error"></div>';
		$html .= '</div>';

		$html .= '<div id="response-error-page-6" class="response-error"></div>';
		$html .= '</fieldset>';

		/**
		 * ## STEP 7 - Finish
		 * --------------------------- */
		$html .= '<fieldset class="wizard-fieldset" data-page="7">';
		$html .= '<div class="wilapp-loader"></div>';
		$html .= '<div id="wilapp-result-appointment"></div>';
		$html .= '</fieldset>';

		/**
		 * ## END
		 * --------------------------- */
		$html .= '</div></form></div></section>';

		return $html;
	}

	/**
	 * # AJAX Validations
	 * ---------------------------------------------------------------------------------------------------- */

	/**
	 * Validates steps
	 *
	 * @return void
	 */
	public function wizard_step() {
		global $helpers_wilapp;
		$page = isset( $_POST['page'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['page'] ) ) : 1;
		session_start();
		if ( 2 === $page ) {
			unset( $_SESSION['wilapp'] );
		}
		if ( ! empty( $_POST['cat_id'] ) && 'null' !== $_POST['cat_id'] ) {
			$_SESSION['wilapp']['cat_id'] = sanitize_text_field( wp_unslash( $_POST['cat_id'] ) );
		}
		if ( ! empty( $_POST['service_id'] ) && 'null' !== $_POST['service_id'] ) {
			$_SESSION['wilapp']['service_id'] = sanitize_text_field( wp_unslash( $_POST['service_id'] ) );
		}
		if ( ! empty( $_POST['day'] ) && 'null' !== $_POST['day'] ) {
			$_SESSION['wilapp']['day'] = sanitize_text_field( wp_unslash( $_POST['day'] ) );
		}
		if ( ! empty( $_POST['hour'] ) && 'null' !== $_POST['hour'] ) {
			$_SESSION['wilapp']['hour'] = sanitize_text_field( wp_unslash( $_POST['hour'] ) );
		}
		if ( ! empty( $_POST['worker'] ) && 'null' !== $_POST['worker'] ) {
			$_SESSION['wilapp']['worker'] = sanitize_text_field( wp_unslash( $_POST['worker'] ) );
		}

		$cat_id     = isset( $_SESSION['wilapp']['cat_id'] ) ? sanitize_text_field( wp_unslash( $_SESSION['wilapp']['cat_id'] ) ) : '';
		$service_id = isset( $_SESSION['wilapp']['service_id'] ) ? sanitize_text_field( wp_unslash( $_SESSION['wilapp']['service_id'] ) ) : '';
		$day        = isset( $_SESSION['wilapp']['day'] ) ? sanitize_text_field( wp_unslash( $_SESSION['wilapp']['day'] ) ) : '';
		$hour       = isset( $_SESSION['wilapp']['hour'] ) ? sanitize_text_field( wp_unslash( $_SESSION['wilapp']['hour'] ) ) : '';
		$worker     = isset( $_SESSION['wilapp']['worker'] ) ? sanitize_text_field( wp_unslash( $_SESSION['wilapp']['worker'] ) ) : '';
		$nonce_step = isset( $_POST['validate_step_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['validate_step_nonce'] ) ) : '';

		if ( wp_verify_nonce( $nonce_step, 'validate_step_nonce' ) ) {
			$professional = get_transient( 'wilapp_query_professional' );
			$services     = $professional['services'];
			// Request from page 1.
			if ( 2 === $page ) {
				$services_cat = $helpers_wilapp->filter_services( $services, $cat_id );
				$options      = array();
				foreach ( $services_cat as $service ) {
					$options[] = array(
						'id'    => $service['id'],
						'image' => $service['image'],
						'name'  => $service['name'],
						'type'  => 'service-id',
					);
				}
				wp_send_json_success( $options );
			} elseif ( 3 === $page ) {
				// Schedules Day.
				$service           = $helpers_wilapp->filter_service( $services, $service_id );
				$schedules_service = $helpers_wilapp->get_schedules( $professional, $service );
				$services_day      = explode( ',', $schedules_service['day'] );

				$start_time = strtotime( 'today' );
				$end_time   = strtotime( '+' . WILAPP_MAXDAYS . ' day' );
				$options    = array();
				for ( $i = $start_time; $i <= $end_time; $i = $i + 86400 ) {
					$week_day = (int) $helpers_wilapp->convert_week( gmdate( 'w', $i ) );
					if ( isset( $services_day[ $week_day ] ) && $services_day[ $week_day ] ) {
						$options[] = array(
							'id'   => gmdate( 'Y-m-d', $i ),
							'name' => $helpers_wilapp->get_week_name( $week_day ) . ' ' . gmdate( 'd-m-Y', $i ),
							'type' => 'appointment-weekday',
						);
					}
				}
				wp_send_json_success( $options );
			} elseif ( 4 === $page ) {
				$workers_service = $helpers_wilapp->get_workers( $professional );
				// Workers.
				$options = array();
				foreach ( $workers_service as $worker ) {
					$options[] = array(
						'id'    => $worker['id'],
						'image' => $worker['image'],
						'name'  => $worker['name'],
						'type'  => 'worker-id',
					);
				}
				wp_send_json_success( $options );
			} elseif ( 5 === $page ) {
				// Schedules Hour.
				$options           = array();
				$service           = $helpers_wilapp->filter_service( $services, $service_id );
				$schedules_service = $helpers_wilapp->get_schedules( $professional, $service, $day, $worker );

				if ( ! empty( $schedules_service['hours'] ) ) {
					foreach ( $schedules_service['hours'] as $schedule_hour ) {
						$options[] = array(
							'id'   => $schedule_hour,
							'name' => $schedule_hour,
							'type' => 'appointment-hour',
						);
					}
				}
				wp_send_json_success( $options );
			}
		} else {
			wp_send_json_error( esc_html__( 'Error connecting API', 'wilapp' ) );
		}
	}
	/**
	 * Validates Final submission
	 *
	 * @return boolean
	 */
	public function validate_submit() {
		global $helpers_wilapp;
		session_start();
		if ( ! isset( $_SESSION['wilapp'] ) ) {
			return false;
		}

		$worker_id = isset( $_POST['worker_id'] ) ? sanitize_text_field( wp_unslash( $_POST['worker_id'] ) ) : '';
		$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$email     = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
		$notes     = isset( $_POST['notes'] ) ? sanitize_text_field( wp_unslash( $_POST['notes'] ) ) : '';
		$nonce     = isset( $_POST['validate_submit_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['validate_submit_nonce'] ) ) : '';

		if ( wp_verify_nonce( $nonce, 'validate_submit_nonce' ) ) {
			$professional = get_transient( 'wilapp_query_professional' );
			$services     = $professional['services'];
			$service      = $helpers_wilapp->filter_service( $services, $_SESSION['wilapp']['service_id'] );

			// Process dates: Y-m-d H:i:s.
			$start_date  = sanitize_text_field( wp_unslash( $_SESSION['wilapp']['day'] ) ) . ' ';
			$start_date .= sanitize_text_field( wp_unslash( $_SESSION['wilapp']['hour'] ) );
			$end_date    = gmdate( 'Y-m-d H:i', strtotime( $start_date ) + $service['duration'] * 60 );

			$result_appointment = $helpers_wilapp->post_appointment(
				$professional,
				array(
					'professional_id' => $professional['id'],
					'service_id'      => sanitize_text_field( wp_unslash( $_SESSION['wilapp']['service_id'] ) ),
					'worker_id'       => $worker_id,
					'start_date'      => $start_date,
					'end_date'        => $end_date,
					'client_name'     => $name,
					'client_email'    => $email,
					'client_phone'    => $phone,
					'client_notes'    => $notes,
					'isProfessional'  => false,
				)
			);

			if ( 'ok' === $result_appointment['status'] ) {
				wp_send_json_success( esc_html__( 'Appointment created correctly', 'wilapp' ) );
			} else {
				wp_send_json_error( esc_html__( 'Error creating the appointment', 'wilapp' ) );
			}
		}
	}

}

new WilApp_Wizard();
