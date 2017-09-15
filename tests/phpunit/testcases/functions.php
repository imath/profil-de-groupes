<?php
/**
 * @group functions
 */
class Profil_De_Groupes_Functions_Tests extends BP_UnitTestCase {
	protected $groups = array();
	protected $fields = array();

	public function setUp() {
		parent::setUp();

		foreach ( array( 'foo', 'bar' ) as $field_name ) {
			$this->fields[ $field_name ] = $this->factory->xprofile_field->create( array(
				'field_group_id' => profil_de_groupes_get_fields_group(),
				'name'           => $field_name,
			) );
		}

		foreach ( array( 'groupa', 'groupb' ) as $group_name ) {
			$this->groups[ $group_name ] = $this->factory->group->create( array(
				'name' => $group_name,
			) );
		}
	}

	public function test_profil_de_groupes_set_field_data() {
		profil_de_groupes_set_field_data( $this->fields['foo'], $this->groups['groupa'], 'foo' );

		$this->assertTrue( 'foo' === profil_de_groupes_get_field_data( 'foo', $this->groups['groupa'] ) );
	}
}
