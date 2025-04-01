<?php
/**
 * UserSubmitsSpecificFieldGravityForm.
 * php version 5.6
 *
 * @category UserSubmitsSpecificFieldGravityForm
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\GravityForms\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'UserSubmitsSpecificFieldGravityForm' ) ) :

	/**
	 * UserSubmitsSpecificFieldGravityForm
	 *
	 * @category UserSubmitsSpecificFieldGravityForm
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class UserSubmitsSpecificFieldGravityForm {


		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'GravityForms';


		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'user_submits_specific_field_gravityform';

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
		 * Register action.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {

			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'Form Submitted with Specific Field', 'suretriggers' ),
				'action'        => 'user_submits_specific_field_gravityform',
				'common_action' => 'gform_after_submission',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;

		}

		/**
		 * Trigger listener
		 *
		 * @param array $entry The entry that was just created.
		 * @param array $form The current form.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $entry, $form ) {

			if ( empty( $entry ) || 'spam' === rgar( $entry, 'status' ) ) {
				return;
			}
			$user_id = ap_get_current_user_id();

			$context['gravity_form']          = (int) $form['id'];
			$context['form_title']            = $form['title'];
			$context['entry_id']              = $entry['id'];
			$context['user_ip']               = $entry['ip'];
			$context['entry_source_url']      = $entry['source_url'];
			$context['entry_submission_date'] = $entry['date_created'];
			
			$field_values = [];
			foreach ( $form['fields'] as $field ) {
				$inputs = $field->get_entry_inputs();
				if ( is_array( $inputs ) ) {
					foreach ( $inputs as $input ) {
						$label_key = strtolower( str_replace( ' ', '_', $input['label'] ) );
						if ( ! isset( $input['isHidden'] ) || ( isset( $input['isHidden'] ) && ! $input['isHidden'] ) ) {
							if ( ( 'fileupload' == $field['type'] && 1 == $field['multipleFiles'] ) || 'multiselect' == $field['type'] ) {
								$json_string = rgar( $entry, (string) $input['id'] );
								$array       = json_decode( $json_string );
								if ( is_array( $array ) ) {
									$comma_separated                       = implode( ',', $array );
									$context[ 'form_field_' . $label_key ] = $comma_separated;
								}
							}
							if ( isset( $field_values[ $label_key ] ) ) {
								if ( ! is_array( $field_values[ $label_key ] ) ) {
									$field_values[ $label_key ] = [ $field_values[ $label_key ] ];
								}
								$field_values[ $label_key ][] = rgar( $entry, (string) $input['id'] );
							} else {
								$field_values[ $label_key ] = rgar( $entry, (string) $input['id'] );
							}
						}
					}
				} else {
					$label_key = strtolower( str_replace( ' ', '_', $field['label'] ) );
					if ( ( 'fileupload' == $field['type'] && 1 == $field['multipleFiles'] ) || 'multiselect' == $field['type'] ) {
						$json_string = rgar( $entry, (string) $field->id );
						$array       = json_decode( $json_string );
						if ( is_array( $array ) ) {
							$comma_separated                       = implode( ',', $array );
							$context[ 'form_field_' . $label_key ] = $comma_separated;
						}
					}
					if ( isset( $field_values[ $label_key ] ) ) {
						if ( ! is_array( $field_values[ $label_key ] ) ) {
							$field_values[ $label_key ] = [ $field_values[ $label_key ] ];
						}
						$field_values[ $label_key ][] = rgar( $entry, (string) $field->id );
					} else {
						$field_values[ $label_key ] = rgar( $entry, (string) $field->id );
					}
				}
			}
			$context = array_merge( $context, $field_values );
			
			foreach ( $form['fields'] as $field ) {
				$inputs = $field->get_entry_inputs();
				if ( is_array( $inputs ) ) {
					foreach ( $inputs as $input ) {
						if ( ! isset( $input['isHidden'] ) || ( isset( $input['isHidden'] ) && ! $input['isHidden'] ) ) {
							$context['field_id']    = $input['id'];
							$context['field_value'] = rgar( $entry, (string) $input['id'] );
							AutomationController::sure_trigger_handle_trigger(
								[
									'trigger' => $this->trigger,
									'context' => $context,
								]
							);
						}
					}
				} else {
					$context['field_id']    = $field->id;
					$context['field_value'] = rgar( $entry, (string) $field->id );
					AutomationController::sure_trigger_handle_trigger(
						[
							'trigger' => $this->trigger,
							'context' => $context,
						]
					);
				}
			}
		}

	}

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	UserSubmitsSpecificFieldGravityForm::get_instance();

endif;
