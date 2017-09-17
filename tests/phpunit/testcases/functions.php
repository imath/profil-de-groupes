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

	// Dummy filter to test the cache
	public function edit_wpdb_query( $query ) {
		return "SHOW TABLES LIKE 'dummy'";
	}

	public function test_profil_de_groupes_set_field_data() {
		profil_de_groupes_set_field_data( $this->fields['foo'], $this->groups['groupa'], 'foo' );

		$this->assertTrue( 'foo' === profil_de_groupes_get_field_data( 'foo', $this->groups['groupa'] ) );
	}

	/**
	 * @group cache
	 */
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

	/**
	 * @group cache
	 */
	public function test_after_loop_cache_profil_de_groupes_get_field_data() {
		foreach ( array_keys( $this->fields ) as $name ) {
			profil_de_groupes_set_field_data(
				$this->fields[ $name ],
				$this->groups['groupa'],
				$name
			);

			profil_de_groupes_set_field_data(
				$this->fields[ $name ],
				$this->groups['groupb'],
				$name
			);
		}

		global $group;
		$pdg = profil_de_groupes();

		$reset_group = $group;
		$pdg->current_group_id = $this->groups['groupa'];

		profil_de_groupes_has_profile();
		while ( profil_de_groupes_profiles() ) {
			profil_de_groupes_profile();
		}

		add_filter( 'query', array( $this, 'edit_wpdb_query' ), 20, 1 );

		$dataa = profil_de_groupes_get_field_data( 'foo', $this->groups['groupa'] );
		$datab = profil_de_groupes_get_field_data( 'foo', $this->groups['groupb'] );

		remove_filter( 'query', array( $this, 'edit_wpdb_query' ), 20, 1 );

		$foo_cache = wp_cache_get( 'foo', 'profil_de_groupes' );

		$this->assertTrue( 'foo' === $foo_cache[ $this->groups['groupa'] ] );
		$this->assertFalse( isset( $foo_cache[ $this->groups['groupb'] ] ) );

		unset( $pdg->current_group_id );
		$group = $reset_group;
	}

	/**
	 * @group cache
	 */
	public function test_after_update_profil_de_groupes_fields_data_caches() {
		foreach ( array_keys( $this->fields ) as $name ) {
			profil_de_groupes_set_field_data(
				$this->fields[ $name ],
				$this->groups['groupb'],
				$name
			);
		}

		global $group;
		$pdg = profil_de_groupes();

		$reset_group = $group;
		$pdg->current_group_id = $this->groups['groupb'];

		profil_de_groupes_has_profile();
		while ( profil_de_groupes_profiles() ) {
			profil_de_groupes_profile();
		}

		$datal = wp_filter_object_list( $group->fields, array(), 'and', 'data' );
		$datab = profil_de_groupes_get_field_data( array( 'foo', 'bar' ), $this->groups['groupb'] );

		profil_de_groupes_set_field_data(
			$this->fields['foo'],
			$this->groups['groupb'],
			'updated'
		);

		$caches = array( wp_cache_get( 'group_fields', 'profil_de_groupes' ), wp_cache_get( 'foo', 'profil_de_groupes' ) );

		$this->assertEmpty( array_filter( $caches ) );

		unset( $pdg->current_group_id );
		$group = $reset_group;
	}

	/**
	 * @group cache
	 */
	public function test_after_deleted_field_profil_de_groupes_fields_data_caches() {
		set_current_screen( 'groups_bp-profile-setup-groupe' );

		$this->fields['new'] = $this->factory->xprofile_field->create( array(
			'field_group_id' => profil_de_groupes_get_fields_group(),
			'name'           => 'new',
		) );

		foreach ( array_keys( $this->fields ) as $name ) {
			profil_de_groupes_set_field_data(
				$this->fields[ $name ],
				$this->groups['groupa'],
				$name
			);
		}

		global $group;
		$pdg = profil_de_groupes();

		$reset_group = $group;
		$pdg->current_group_id = $this->groups['groupa'];

		profil_de_groupes_has_profile();
		while ( profil_de_groupes_profiles() ) {
			profil_de_groupes_profile();
		}

		$datal = wp_filter_object_list( $group->fields, array(), 'and', 'data' );
		$datab = profil_de_groupes_get_field_data( array( 'foo', 'bar', 'new' ), $this->groups['groupa'] );

		// Simulate the delete_field admin mode
		$field = xprofile_get_field( $this->fields['new'] );
		xprofile_delete_field( $this->fields['new'] );
		add_action( 'xprofile_fields_deleted_field', 'profil_de_groupes_admin_delete_field_data', 10, 1 );
		do_action( 'xprofile_fields_deleted_field', $field );


		$caches = array( wp_cache_get( 'group_fields', 'profil_de_groupes' ), wp_cache_get( 'new', 'profil_de_groupes' ) );

		$this->assertEmpty( array_filter( $caches ) );

		unset( $pdg->current_group_id );
		$group = $reset_group;
		set_current_screen( 'front' );
	}
}
