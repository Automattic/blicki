<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Acts as an audit log to know what events occurred for each entry.
 */
class Blicki_History {

    /**
     * Event types.
     * @var array
     */
    private static $event_types = array(
        'submitted',
        'updated',
        'contributed',
    );

    /**
     * Log event for entry.
     */
    public static function log_event( $entry_id, $type, $data = array() ) {
        global $wpdb;

        if ( in_array( $type, self::$event_types ) ) {
            $data = wp_parse_args( $data, array(
				'user_id'         => 0,
				'revision_id'     => 0,
				'user_name'       => '',
				'user_email'      => '',
				'event_timestamp' => time(),
            ) );

			if ( empty( $data['user_id'] ) && ( empty( $data['user_email'] ) || empty( $data['user_name'] ) ) ) {
				return;
			}

            $wpdb->insert(
                $wpdb->prefix . 'blicky_history',
                array(
					'entry_id'        => absint( $entry_id ),
					'user_id'         => $data['user_id'],
					'revision_id'     => $data['revision_id'],
					'user_name'       => $data['user_name'],
					'user_email'      => $data['user_email'],
					'event'           => $type,
					'event_timestamp' => date( 'Y-m-d H:i:s', $data['event_timestamp'] ),
                )
            );
        }
    }

    /**
     * Get events.
     */
    public static function get_events( $entry_id, $type = 'all' ) {
        global $wpdb;

        if ( 'all' === $type ) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}blicky_history WHERE entry_id = %d",
                $entry_id
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}blicky_history WHERE entry_id = %d AND event = %s",
                $entry_id,
                $type
            );
        }

        return $wpdb->get_results( $query );
    }
}
