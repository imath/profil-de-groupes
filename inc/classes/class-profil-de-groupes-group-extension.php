<?php
/**
 * Profil De Groupes functions.
 *
 * @package ProfilDeGroupes\inc\classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_Group_Extension' ) ) :
/**
 * The Group Extension class.
 *
 * @since  1.0.0
 */
class Profil_De_Groupes_Group_Extension extends BP_Group_Extension {
	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::init(  array(
			'slug'              => profil_de_groupes_get_slug(),
			'name'              => __( 'A propos de nous', 'altctrl-public-group' ),
			'visibility'        => 'public',
			'nav_item_position' => 14,
			'enable_nav_item'   => true,
			'screens'           => array(
				'admin' => array(
					'enabled' => false,
				),
				'create' => array(
					'enabled' => false,
					'position' => 14,
				),
				'edit' => array(
					'enabled' => true,
				),
			),
		) );
	}

	/**
	 * Unused
	 */
	public function create_screen( $group_id = null ) {}
	public function create_screen_save( $group_id = null ) {}
	public function admin_screen( $group_id = null ) {}
	public function admin_screen_save( $group_id = null ) {}
	public function display( $group_id = null ) {}

	public function widget_display() {}

	/**
	 * Displays edit screen.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $group_id The group ID.
	 */
	public function edit_screen( $group_id = null ) {
		if ( bp_has_profile( 'profile_group_id=2' ) ) :
			while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

				<form action="" method="post" id="profile-edit-form" class="standard-form">

				<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

					<div<?php bp_field_css_class( 'editfield' ); ?>>
						<fieldset>

							<?php
							/**
							 * Generate the field edit output.
							 */
							$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
							$field_type->edit_field_html(); ?>

						</fieldset>
					</div>

				<?php endwhile; ?>

					<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

				</form>

		<?php endwhile; endif;
	}

	/**
	 * Save the settings of the group.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $group_id The group ID.
	 */
	public function edit_screen_save( $group_id = null ) {}
}

endif;
