<?php

class Create_Delete_Languages_Test extends PLL_UnitTestCase {

	public function test_add_and_delete_language() {
		// first language
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 2,
		);

		$this->assertTrue( self::$model->add_language( $args ) );

		$lang = self::$model->get_language( 'en' );

		$this->assertEquals( 'English', $lang->name );
		$this->assertEquals( 'en', $lang->slug );
		$this->assertEquals( 'en_US', $lang->locale );
		$this->assertEquals( 0, $lang->is_rtl );
		$this->assertEquals( 2, $lang->term_group );

		// second language (rtl)
		$args = array(
			'name'       => 'العربية',
			'slug'       => 'ar',
			'locale'     => 'ar',
			'rtl'        => 1,
			'flag'       => 'arab',
			'term_group' => 1,
		);

		$this->assertTrue( self::$model->add_language( $args ) );

		$lang = self::$model->get_language( 'ar' );

		$this->assertEquals( 'العربية', $lang->name );
		$this->assertEquals( 'ar', $lang->slug );
		$this->assertEquals( 'ar', $lang->locale );
		$this->assertEquals( 1, $lang->is_rtl );
		$this->assertEquals( 1, $lang->term_group );

		// check default language
		$this->assertEquals( 'en', self::$model->options['default_lang'] );

		// check language order
		$this->assertEqualSetsWithIndex( array( 'ar', 'en' ), self::$model->get_languages_list( array( 'fields' => 'slug' ) ) );

		// attempt to create a language with the same slug as an existing one
		self::$model->add_language( array( 'slug' => 'en-gb', 'locale' => 'en_GB' ) );
		$lang = self::$model->get_language( 'en' );
		$this->assertEquals( 'en_US', $lang->locale );
		$this->assertFalse( self::$model->get_language( 'en_GB' ) );
		$this->assertEquals( 2, count( self::$model->get_languages_list() ) );

		// delete 1 language
		$lang = self::$model->get_language( 'en_US' );
		self::$model->delete_language( $lang->term_id );
		$this->assertEquals( 'ar', self::$model->options['default_lang'] );

		// delete the last language
		$lang = self::$model->get_language( 'ar' );
		self::$model->delete_language( $lang->term_id );
		$this->assertEquals( array(), self::$model->get_languages_list() );
	}

	/**
	 * Bug fixed in 2.3.
	 */
	public function test_unique_language_code_if_same_as_locale() {
		// First language
		$args = array(
			'name'       => 'العربية',
			'slug'       => 'a', // Intentional mistake
			'locale'     => 'ar',
			'rtl'        => 1,
			'flag'       => 'arab',
			'term_group' => 1,
		);

		$this->assertTrue( self::$model->add_language( $args ) );

		$lang = self::$model->get_language( 'ar' );
		$args['lang_id'] = $lang->term_id;
		$args['slug'] = 'ar';
		$this->assertTrue( self::$model->update_language( $args ) );

		self::$model->delete_language( $lang->term_id );
	}

	public function test_invalid_languages() {
		global $wp_settings_errors;

		$args = array(
			'name'       => '',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 1,
		);

		$this->assertWPError( self::$model->add_language( $args ), 'The language must have a name' );

		$args['name'] = 'English';
		$args['locale'] = 'EN';

		$this->assertWPError( self::$model->add_language( $args ), 'Enter a valid WordPress locale' );

		$args['locale'] = 'en-US';

		$this->assertWPError( self::$model->add_language( $args ), 'Enter a valid WordPress locale' );

		$args['locale'] = 'en_US';
		$args['slug'] = 'EN';

		$this->assertWPError( self::$model->add_language( $args ), 'The language code contains invalid characters' );

		$args['slug'] = 'en';
		$args['flag'] = 'en';

		$this->assertWPError( self::$model->add_language( $args ), 'The flag does not exist' );
	}

	/**
	 * Issue #910
	 */
	public function test_language_properties_in_transient() {
		$args = array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => 0,
			'flag'       => 'us',
			'term_group' => 2,
		);

		self::$model->add_language( $args );
		self::$model->get_languages_list(); // Saves the transient.

		$properties = array(
			'term_id',
			'name',
			'slug',
			'term_group',
			'term_taxonomy_id',
			'count',
			'tl_term_id',
			'tl_term_taxonomy_id',
			'tl_count',
			'locale',
			'is_rtl',
			'w3c',
			'facebook',
			'home_url',
			'search_url',
			'host',
			'mo_id',
			'page_on_front',
			'page_for_posts',
			'flag_code',
			'flag_url',
			'flag',
			'custom_flag_url',
			'custom_flag',
		);

		$languages = get_transient( 'pll_languages_list' );
		$this->assertEqualSets( $properties, array_keys( reset( $languages ) ) );
	}
}
