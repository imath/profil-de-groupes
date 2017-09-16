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

	/**
	 * @group cache
	 */
	public function test_profil_de_groupes_set_field_data() {
		profil_de_groupes_set_field_data( $this->fields['foo'], $this->groups['groupa'], 'foo' );

		$this->assertTrue( 'foo' === profil_de_groupes_get_field_data( 'foo', $this->groups['groupa'] ) );
	}

	public function test_cache_profil_de_groupes_get_field_data() {
		foreach ( array_keys( $this->fields ) as $name ) {
			profil_de_groupes_set_field_data(
				$this->fields[ $name ],
				$this->groups['groupb'],
				$name. '_' . $this->groups['groupb']
			);

			if ( 'bar' === $name ) {
				continue;
			}

			profil_de_groupes_set_field_data(
				$this->fields[ $name ],
				$this->groups['groupa'],
				$name. '_' . $this->groups['groupa']
			);
		}

		$dataa = profil_de_groupes_get_field_data( array( 'foo', 'bar' ), $this->groups['groupa'] );
		$datab = profil_de_groupes_get_field_data( array( 'foo', 'bar' ), $this->groups['groupb'] );

		$foo_cache    = wp_cache_get( 'foo', 'profil_de_groupes' );
		$foo_expected = array(
			$this->groups['groupa'] => 'foo_' . $this->groups['groupa'],
			$this->groups['groupb'] => 'foo_' . $this->groups['groupb'],
		);

		$this->assertSame( $foo_cache, $foo_expected );

		$bar_cache    = wp_cache_get( 'bar', 'profil_de_groupes' );
		$bar_expected = array(
			$this->groups['groupb'] => 'bar_' . $this->groups['groupb'],
		);

		$this->assertSame( $bar_cache, $bar_expected );
	}
}
