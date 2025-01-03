<?php
/**
 * AddUserRole.
 * php version 5.6
 *
 * @category AddUserRole
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WordPress\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( '\SureTriggers\Integrations\WordPress\Triggers\AddUserRole' ) ) :

	/**
	 * AddUserRole
	 *
	 * @category AddUserRole
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class AddUserRole {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'WordPress';

		/**
		 * Action name.
		 *
		 * @var string
		 */
		public $trigger = 'add_user_role';

		use SingletonLoader;

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
		}

		/**
		 * Register a action.
		 *
		 * @param array $triggers actions.
		 * @return array
		 */
		public function register( $triggers ) {
			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'Role: Add a new role to the users roles', 'suretriggers' ),
				'action'        => 'add_user_role',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;

		}

		/**
		 * Trigger listener
		 *
		 * @param int    $user_id user id.
		 * @param string $role role.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $user_id, $role ) {
			$context         = WordPress::get_user_context( $user_id );
			$context['role'] = $role;

			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	AddUserRole::get_instance();

endif;




