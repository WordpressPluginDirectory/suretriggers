<?php
/**
 * SendDirectMessage.
 * php version 5.6
 *
 * @category SendDirectMessage
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Voxel\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Integrations\WordPress\WordPress;
use Exception;

/**
 * SendDirectMessage
 *
 * @category SendDirectMessage
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SendDirectMessage extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'Voxel';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'voxel_send_direct_message';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Send Direct Message', 'suretriggers' ),
			'action'   => 'voxel_send_direct_message',
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id user_id.
	 * @param int   $automation_id automation_id.
	 * @param array $fields fields.
	 * @param array $selected_options selectedOptions.
	 * 
	 * @throws Exception Exception.
	 * 
	 * @return bool|array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$content     = $selected_options['content'];
		$sender_id   = $selected_options['wp_user_email'];
		$receiver_id = $selected_options['receiver_email'];

		if ( ! class_exists( 'Voxel\Direct_Messages\Message' ) || ! class_exists( 'Voxel\User' ) || ! class_exists( 'Voxel\Events\Direct_Messages\User_Received_Message_Event' ) ) {
			return false;
		}

		if ( is_email( $sender_id ) || is_email( $receiver_id ) ) {
			$sender   = get_user_by( 'email', $sender_id );
			$receiver = get_user_by( 'email', $receiver_id );
			if ( $sender ) {
				if ( $receiver ) {
					$sender_id   = $sender->ID;
					$receiver_id = $receiver->ID;
					global $wpdb;
					// Get the sender user.
					$sender_user = \Voxel\User::get( $sender_id );

					// Get the receiver user.
					$receiver_user = \Voxel\User::get( $receiver_id );
					
					// check if users have blocked each other.
					if ( $sender_user->get_follow_status( 'user', $receiver_user->get_id() ) === -1 || $receiver_user->get_follow_status( 'user', $sender_user->get_id() ) === -1 ) {
						throw new Exception( 'You cannot message the user.' );
					}

					$message = \Voxel\Direct_Messages\Message::create(
						[
							'sender_type'      => 'user',
							'sender_id'        => $sender_id,
							'sender_deleted'   => 0,
							'receiver_type'    => 'user',
							'receiver_id'      => $receiver_id,
							'receiver_deleted' => 0,
							'content'          => $content,
							'seen'             => 0,
						] 
					);

					$receiver_user->set_inbox_activity( true );
					$receiver_author = $receiver_user;
					if ( $receiver_author ) {
						$receiver_author->update_inbox_meta(
							[
								'unread' => true,
							]
						);
					}

					$message->update_chat();

					$has_recently_received_message = ! ! $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM {$wpdb->prefix}voxel_messages
							WHERE
								sender_type = %s AND sender_id = %d
								AND receiver_type = %s AND receiver_id = %d
								AND created_at > %s
								AND id != %d
							LIMIT 1",
							'user',
							$sender_id,
							'user',
							$receiver_id,
							gmdate( 'Y-m-d H:i:s', time() - ( 15 * MINUTE_IN_SECONDS ) ),
							$message->get_id()
						) 
					);
					// Dispatch the message.
					if ( ! $has_recently_received_message ) {
						( new \Voxel\Events\Direct_Messages\User_Received_Message_Event() )->dispatch( $message->get_id() );
					}
					return [
						'sender'   => WordPress::get_user_context( $message->get_sender_id() ),
						'receiver' => WordPress::get_user_context( $message->get_receiver_id() ),
						'message'  => [
							'id'          => $message->get_id(),
							'time'        => $message->get_time_for_display(),
							'chat_time'   => $message->get_time_for_chat_display(),
							'seen'        => $message->is_seen(),
							'has_content' => ! empty( $message->get_content() ),
							'content'     => $message->get_content_for_display(),
							'excerpt'     => $message->get_excerpt( true ),
							'is_deleted'  => false,
							'is_hidden'   => false,
						],
					];
				} else {
					throw new Exception( 'Please enter valid receiver.' );
				}
			} else {
				throw new Exception( 'Please enter valid sender.' );
			}
		} else {
			throw new Exception( 'Please enter valid email address.' );
		}
	}

}

SendDirectMessage::get_instance();
