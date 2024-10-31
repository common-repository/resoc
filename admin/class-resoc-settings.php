<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://resoc.io
 * @since      1.0.0
 *
 * @package    Resoc
 * @subpackage Resoc/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Resoc
 * @subpackage Resoc/admin
 * @author     Philippe Bernard <philippe@resoc.io>
 */
class Resoc_Settings {

  const OPTIONS = 'resoc_options';

  const TEMPLATE         = 'template';
  const BACKGROUND_COLOR = 'background-color';
  const TEXT_COLOR       = 'text-color';
  const BRAND_NAME       = 'brand-name';
  const LOGO             = 'logo';
  const TEXT_DIRECTION   = 'textDirection';

  const SHOW_PLUGIN_EXPLANATIONS = 'resoc_show_plugin_explanations';

  /**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $resoc    The ID of this plugin.
	 */
	private $resoc;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $resoc       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $resoc, $version ) {
		$this->resoc = $resoc;
		$this->version = $version;
  }

  public static function migrate_old_settings() {
    Resoc_Utils::log( "Migrate old options system" );

    $old_options = get_option( self::OPTIONS );
    if ( ! $old_options ) {
      Resoc_Utils::log( "No old options - skip" );
      return;
    }

    if ( isset( $old_options[ self::TEMPLATE ] ) && $old_options[ self::TEMPLATE ] ) {
      Resoc_Template::set_active_template_name( $old_options[ self::TEMPLATE ] );
    }

    $well_known_parameter_values = Resoc_Template::get_well_known_parameter_values();
    $old_new = array(
      self::BACKGROUND_COLOR => 'backgroundColor',
      self::TEXT_COLOR       => 'textColor',
      self::BRAND_NAME       => 'brandName',
      self::LOGO             => 'logoUrl',
      self::TEXT_DIRECTION   => 'textDirection',
    );
    foreach( array_keys( $old_new ) as $old_name ) {
      if ( isset( $old_options[ $old_name ] ) && $old_options[ $old_name ] ) {
        $well_known_parameter_values[ $old_new[ $old_name ] ] = $old_options[ $old_name ];
      }
    }
    Resoc_Template::set_well_known_parameter_values( $well_known_parameter_values );

    Resoc_Template::set_load_image_engine( true );

    delete_option( self::OPTIONS );
  }
}
