<?php
/**
 * Connection Library Wilapp
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Wilapp.
 *
 * Connnector to Wilapp.
 *
 * @since 1.0
 */
class Helpers_Wilapp {
	/**
	 * POSTS API from Wilapp
	 *
	 * @param string $credentials Credentials to login API.
	 * @param string $endpoint Function to execute.
	 * @param string $method Method API.
	 * @param string $query Query.
	 * @return array
	 */
	public function api( $credentials, $endpoint, $method = 'GET', $query = array() ) {
		$args = array(
			'method'  => $method,
			'timeout' => 30,
			'body'    => array(),
		);
		if ( empty( $credentials['auth_key'] ) ) {
			$settings = get_option( 'wilapp_options' );
			if ( ! empty( $settings['auth_key'] ) ) {
				$credentials['auth_key'] = $settings['auth_key'];
			}
		}
		if ( ! empty( $credentials['auth_key'] ) && 'user/login' !== $endpoint ) {
			$args['headers'] = array(
				'Authorization' => 'Bearer ' . $credentials['auth_key'],
			);
		} elseif ( ! empty( $credentials['username'] ) && ! empty( $credentials['password'] ) ) {
			$args['body'] = array(
				'email'    => $credentials['username'],
				'password' => $credentials['password'],
			);
		}

		if ( ! empty( $query ) ) {
			$args['body'] = array_merge( $args['body'], $query );
		}
		$url         = 'https://api.wilapp.com/v1/' . $endpoint;
		$result      = wp_remote_request( $url, $args );
		$result_body = wp_remote_retrieve_body( $result );
		$body        = json_decode( $result_body, true );

		if ( isset( $body['status'] ) && 400 === $body['status'] ) {
			return array(
				'status' => 'error',
				'data'   => isset( $body['message'] ) ? $body['message'] : '',
			);
		} else {
			if ( ! empty( $body['auth_key'] ) ) {
				$settings             = get_option( 'wilapp_options' );
				$settings['auth_key'] = $body['auth_key'];
				update_option( 'wilapp_options', $settings );
			}
			return array(
				'status' => 'ok',
				'data'   => isset( $body ) ? $body : '',
			);
		}
	}

	/**
	 * Login settings
	 *
	 * @param array $username Username login.
	 * @param array $password Password login.
	 * @return array
	 */
	public function login( $username = '', $password = '' ) {
		if ( empty( $username ) || empty( $password ) ) {
			$settings = get_option( 'wilapp_options' );
			$username = isset( $settings['username'] ) ? $settings['username'] : '';
			$password = isset( $settings['password'] ) ? $settings['password'] : '';
		}

		return $this->api(
			array(
				'username' => $username,
				'password' => $password,
			),
			'user/login',
			'POST'
		);
	}

	/**
	 * Get available schedules from professional and service
	 *
	 * @param array $professional Professional data.
	 * @param array $service      Sevice data.
	 * @return array
	 */
	public function get_schedules( $professional, $service, $date = '', $worker = '' ) {
		if ( empty( $date ) ) {
			$result_schedule = $this->api(
				array(),
				'schedule/?filter[professional_id]=' . $professional['id'],
				'GET'
			);

			if ( 'ok' === $result_schedule['status'] ) {
				return array(
					'day'      => ! empty( $service['day'] ) ? $service['day'] : '1,1,1,1,1,1,0',
					'init'     => isset( $result_schedule['data'][0]['init'] ) ? $result_schedule['data'][0]['init'] : '09:00:00',
					'end'      => isset( $result_schedule['data'][0]['end'] ) ? $result_schedule['data'][0]['end'] : '20:00:00',
					'duration' => ! empty( $service['duration'] ) ? $service['duration'] : 30,
				);
			}
		} else {
			$result_schedule = $this->api(
				array(),
				'professional/schedule',
				'POST',
				array(
					'date'            => $date,
					'professional_id' => $professional['id'],
					'worker_id'       => $worker,
				)
			);
			$schedule_hours  = array();
			if ( ! empty( $result_schedule['data'] ) ) {
				foreach ( $result_schedule['data'] as $key => $value ) {
					if ( 1 === $value ) {
						$schedule_hours[] = $key;
					}
				}
			}

			return array(
				'hours' => $schedule_hours,
			);

		}

		return false;
	}

	/**
	 * Send appointment to Wilapp
	 *
	 * @param array $professional Professional to log.
	 * @param array $query Query of API.
	 * @return array
	 */
	public function post_appointment( $professional, $query ) {
		return $this->api(
			array(
				'auth_key' => $professional['auth_key'],
			),
			'appointment/create',
			'POST',
			$query
		);
	}

	/**
	 * Get available schedules from professional and service
	 *
	 * @param array $professional Professional data.
	 * @return array
	 */
	public function get_workers( $professional ) {
		$result_workers = $this->api(
			array(
				'auth_key' => $professional['auth_key'],
			),
			'worker',
			'GET'
		);

		if ( 'ok' === $result_workers['status'] && ! empty( $result_workers['data'] ) ) {
			$workers           = $result_workers['data'];
			$available_workers = array();
			foreach ( $workers as $worker ) {
				if ( $worker['professional_id'] === $professional['id'] && 1 === $worker['status'] ) {
					$available_workers[] = $worker;
				}
			}
			return $available_workers;
		}

		return false;
	}

	/**
	 * Filters services by category_id
	 *
	 * @param array  $services Services to access.
	 * @param string $category_id Category of filter.
	 * @return array
	 */
	public function filter_services( $services, $category_id ) {
		$filtered_services = array();
		foreach ( $services as $service ) {
			if ( isset( $service['category_id'] ) && $service['category_id'] === $category_id ) {
				$filtered_services[] = $service;
			}
		}
		return $filtered_services;
	}

	/**
	 * Filters services by category_id
	 *
	 * @param array  $services Services to access.
	 * @param string $service_id Service ID.
	 * @return array
	 */
	public function filter_service( $services, $service_id ) {
		$filtered_service = array();
		foreach ( $services as $service ) {
			if ( isset( $service['id'] ) && $service['id'] === $service_id ) {
				$filtered_service = $service;
			}
		}

		return $filtered_service;
	}

	/**
	 * Convert week to EU format.
	 *
	 * @param int $week_day Week day.
	 * @return int
	 */
	public function convert_week( $week_day ) {
		$european_day = array(
			0 => 6,
			1 => 0,
			2 => 1,
			3 => 2,
			4 => 3,
			5 => 4,
			6 => 5,
		);

		return isset( $european_day[ $week_day ] ) ? $european_day[ $week_day ] : 0;
	}

	/**
	 * Get week name.
	 *
	 * @param int $euro_week_day Euro week day.
	 * @return string
	 */
	public function get_week_name( $euro_week_day ) {
		$week_names = array(
			0 => __( 'Monday', 'wilapp' ),
			1 => __( 'Tuesday', 'wilapp' ),
			2 => __( 'Wednesday', 'wilapp' ),
			3 => __( 'Thursday', 'wilapp' ),
			4 => __( 'Friday', 'wilapp' ),
			5 => __( 'Saturday', 'wilapp' ),
			6 => __( 'Sunday', 'wilapp' ),
		);

		return isset( $week_names[ $euro_week_day ] ) ? $week_names[ $euro_week_day ] : '';
	}
}

$helpers_wilapp = new Helpers_Wilapp();
