<?php
/**
 * Profil De Groupes Profile Data class.
 *
 * @package ProfilDeGroupes\inc\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_XProfile_ProfileData' ) ) :
/**
 * The Profile Data setup.
 *
 * @since 1.0.0
 */
class Profil_De_Groupes_Group_Data extends BP_XProfile_ProfileData {
	/**
	 * Field Data ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int $id
	 */
	public $id;

	/**
	 * Group ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int $group_id
	 */
	public $group_id;

	/**
	 * Field ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int $field_id
	 */
	public $field_id;

	/**
	 * Field value.
	 *
	 * @since 1.0.0
	 *
	 * @var string $value
	 */
	public $value;

	/**
	 * The constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct( $field_id = null, $user_id = null ) {
		global $wpdb;

		$this->db = sprintf( '%sprofil_de_groupes_data', $wpdb->base_prefix );

		if ( $field_id ) {
			$this->field_id = (int) $field_id;
		}

		if ( $user_id ) {
			$this->group_id = (int) $user_id;
		}
	}

	/**
	 * Unused methods.
	 */
	public function populate( $field_id, $user_id ) {}
	public static function get_last_updated( $user_id ) {}
	public static function delete_data_for_user( $user_id ) {}
	public static function get_random( $user_id, $exclude_fullname ) {}
	public static function get_fullname( $user_id = 0 ) {}
	public static function get_data_for_user( $user_id, $field_ids ) {}
	public static function get_all_for_user( $user_id ) {}
	public static function get_fielddataid_byid( $field_id, $user_id ) {}
	public static function get_value_byid( $field_id, $user_ids = null ) {}

	/**
	 * Utility to override the $wpdb->query when needed.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $query The SQL query.
	 * @return string        The SQL query.
	 */
	public static function edit_wpdb_query( $query = '' ) {
		global $wpdb;

		$db = sprintf( '%sprofil_de_groupes_data', $wpdb->base_prefix );

		if ( preg_match( '/' . $db . '/', $query, $tb_name ) ) {
			$tb_name = reset( $tb_name );

			if ( $db === $tb_name ) {
				$query = str_replace( 'user_id', 'group_id', $query );
			}
		}

		return $query;
	}

	/**
	 * Check if there is data already for the group.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function exists() {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$this->db} WHERE group_id = %d AND field_id = %d", $this->group_id, $this->field_id ) );
	}

	/**
	 * Save the data for the XProfile field.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;

		if ( ! $this->is_valid_field() ) {
			return false;
		}

		$defaults = array(
			'id'           => 0,
			'group_id'     => 0,
			'field_id'     => 0,
			'value'        => '',
		);

		$d = wp_parse_args(
			array_intersect_key( get_object_vars( $this ), $defaults ),
			array_merge( $defaults, array( 'last_updated' => bp_core_current_time() ) )
		);

		$exists = $this->exists();

		if ( $exists && strlen( trim( $d['value'] ) ) ) {
			$result = $wpdb->update( $this->db,
				array_slice( $d, 3, 2 ),
				array_slice( $d, 1, 2 ),
				array( '%s', '%s' ),
				array( '%d', '%d' )
			);

		} elseif ( $exists && empty( $d['value'] ) ) {
			$result = $this->delete();

		} else {
			array_shift( $d );
			$result   = $wpdb->insert( $this->db, $d, array( '%d', '%d', '%s', '%s' ) );
			$this->id = $wpdb->insert_id;
		}

		if ( false === $result ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete the field data.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function delete() {
		global $wpdb;

		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db} WHERE field_id = %d AND group_id = %d", $this->field_id, $this->group_id ) );

		if ( empty( $deleted ) ) {
			return false;
		}

		return true;
	}

	/** Static Methods ********************************************************/

	/**
	 * Get a group's profile data for a set of fields.
	 *
	 * @since 1.0.0
	 *
	 * @param  integer $group_id  ID of group whose data is being queried.
	 * @param  array   $field_ids Array of field IDs to query for.
	 * @return array
	 */
	public static function get_data_for_group( $group_id = 0, $field_ids = array() ) {
		global $wpdb;
		$db = sprintf( '%sprofil_de_groupes_data', $wpdb->base_prefix );

		$field_ids_sql = implode( ',', wp_parse_id_list( $field_ids ) );
		$data          = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$db} WHERE field_id IN ({$field_ids_sql}) AND group_id = %d",
			$group_id
		) );

		// Integer casting.
		foreach ( (array) $data as $key => $d ) {
			if ( isset( $data[ $key ]->id ) ) {
				$data[ $key ]->id = (int) $data[ $key ]->id;
			}
			if ( isset( $data[ $key ]->user_id ) ) {
				$data[ $key ]->user_id  = (int) $data[ $key ]->user_id;
			}

			$data[ $key ]->field_id = (int) $data[ $key ]->field_id;
		}

		return $data;
	}

	/**
	 * Delete field.
	 *
	 * @since 1.0.0
	 *
	 * @param  integer $field_id ID of the field to delete.
	 * @return bool
	 */
	public static function delete_for_field( $field_id ) {
		global $wpdb;
		$db = sprintf( '%sprofil_de_groupes_data', $wpdb->base_prefix );

		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$db} WHERE field_id = %d", $field_id ) );

		if ( empty( $deleted ) || is_wp_error( $deleted ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets Group's profile field values by field name and user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param  array|string      $fields  Field(s) to get.
	 * @param  integer|null      $user_id Group ID to get field data for.
	 * @return string|array|bool          The field value, an array of field valuers.
	 *                                    False when no values were found.
	 */
	public static function get_value_byfieldname( $fields, $user_id = null ) {
		global $wpdb;

		$group_id = $user_id;

		if ( empty( $group_id ) ) {
			$group_id = bp_get_current_group_id();
		}

		if ( ! $group_id ) {
			return false;
		}

		// Gets the BuddyPress main instance.
		$bp = buddypress();

		// Stores the original xProlile table name.
		$tb_name_reset = $bp->profile->table_name_data;

		// Override the xProfile
		$bp->profile->table_name_data = sprintf( '%sprofil_de_groupes_data', $wpdb->base_prefix );

		// Temporarly filters the query.
		add_filter( 'query', array( __CLASS__, 'edit_wpdb_query' ), 10, 1 );

		$field_values = parent::get_value_byfieldname( $fields, $group_id );

		// Removes the temporary filter on the query.
		remove_filter( 'query', array( __CLASS__, 'edit_wpdb_query' ), 10, 1 );

		// Restores the xProlile table name.
		$bp->profile->table_name_data = $tb_name_reset;

		return $field_values;
	}
}

endif;
