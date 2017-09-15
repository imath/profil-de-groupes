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

		if ( profil_de_groupes_has_fields() ) : ?>

			<div class="bp-widget <?php bp_the_profile_group_slug(); ?>">

				<table class="profile-fields">

					<?php while ( profil_de_groupes_fields() ) : profil_de_groupes_field(); ?>

						<?php if ( profil_de_groupes_field_has_data() ) : ?>

							<tr<?php profil_de_groupes_field_css_class(); ?>>

								<td class="label"><?php profil_de_groupes_field_name(); ?></td>

								<td class="data"><?php profil_de_groupes_field_data(); ?></td>

							</tr>

						<?php endif;

					endwhile; ?>

				</table>
			</div>

		<?php endif;

	endwhile;

endif;

if ( profil_de_groupes_empty_profile() ) : ?>

	<div id="message" class="info">
		<p><?php esc_html_e( 'Ce groupe n\'a pas encore publié ses informations de profil, repassez un peu plus tard !', 'profil-de-groupes' ); ?></p>
	</div>

<?php endif;
