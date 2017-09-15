<?php
/**
 * Profil De Groupes display template.
 *
 * @package ProfilDeGroupes\templates
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( profil_de_groupes_has_profile() ) :

	while ( profil_de_groupes_profiles() ) : profil_de_groupes_profile();

		while ( profil_de_groupes_fields() ) : profil_de_groupes_field(); ?>

			<div<?php profil_de_groupes_field_css_class( 'editfield' ); ?>>
				<fieldset>

					<?php profil_de_groupes_edit_field() ;?>

				</fieldset>
			</div>

		<?php endwhile; ?>

		<input type="hidden" name="field_ids" id="field_ids" value="<?php profil_de_groupes_field_ids(); ?>" />

<?php endwhile; endif;
