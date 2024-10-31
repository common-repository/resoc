<?php

class Resoc_Template {
  const TEMPLATE_SETTINGS = 'resoc_template_settings';
  const TEMPLATE_LIST = 'resoc_template_list';
  const WELL_KNOWN_PARAMETER_VALUES = "resoc_well_known_parameter_values";
  const ACTIVE_TEMPLATE_NAME = 'resoc_active_template_name';

  const CURRENT_IMAGE_ENGINE_URL = 'resoc_current_image_engine_url';
  const SHOULD_LOAD_IMAGE_ENGINE = 'resoc_should_load_image_engine';

  const DEFAULT_IMAGE_ENGINE_URL = 'https://free-engine.resoc.io/';

  public static function templates_url( $imageEngineUrl ) {
    return "{$imageEngineUrl}/templates";
  }

  public static function load_template_list( $templatesUrl ) {
    try {
      return Resoc_Template::load_json( $templatesUrl );
    }
    catch(Exception $e) {
      throw new Exception( "Cannot load template list at {$templatesUrl}: {$e->getMessage()}" );
    }
  }

  public static function template_manifest_url( $imageEngineUrl, $templateName ) {
    return "{$imageEngineUrl}/templates/{$templateName}/content/resoc.manifest.json";
  }

  public static function load_template_manifest( $manifestUrl ) {
    try {
      return Resoc_Template::load_json( $manifestUrl );
    }
    catch(Exception $e) {
      throw "Cannot load template manifest at {$templatesUrl}: {$e->getMessage()}";
    }
  }

  public static function template_manifest_option_name( $templateName ) {
    return "resoc_template_{$templateName}_manifest";
  }

  public static function template_values_option_name( $templateName ) {
    return "resoc_template_{$templateName}_values";
  }

  public static function collect_well_known_preset_parameters( $templates ) {
    $well_known_parameters = array();

    foreach( $templates as $template ) {
      $manifestOptionName = Resoc_Template::template_manifest_option_name( $template['name'] );
      $manifest = json_decode( get_option( $manifestOptionName ), true );

      foreach( $manifest['parameters'] as $parameter ) {
        if ( Resoc_Template::is_well_known_parameter( $parameter['name']) &&
             Resoc_Template::is_preset_parameter( $parameter['name'] ) &&
             !isset( $well_known_parameters[ $parameter['name'] ] ) ) {
          $well_known_parameters[ $parameter['name'] ] = $parameter;
        }
      }
    }

    return array_values( $well_known_parameters );
  }

  public static function load_image_engine( $imageEngineUrl ) {
    Resoc_Utils::log( "Load data from image engine {$imageEngineUrl}" );

    if ( ! self::should_load_image_engine() ) {
      Resoc_Utils::log( "Image engine does not need to be loaded - Do nothing" );
      return;
    }

    $templates = Resoc_Template::load_template_list( Resoc_Template::templates_url( $imageEngineUrl ) );
    $length = count($templates);
    Resoc_Utils::log( "Got {$length} templates" );
    update_option( Resoc_Template::TEMPLATE_LIST, $templates );

    if ( $length > 0 ) {
      update_option( Resoc_Template::ACTIVE_TEMPLATE_NAME, $templates[0]['name'] );
    }

    $well_known_parameter_values = array();

    foreach( $templates as $template ) {
      Resoc_Utils::log( "Get manifest for template {$template['name']}" );

      $manifest = Resoc_Template::load_template_manifest(
        Resoc_Template::template_manifest_url( $imageEngineUrl, $template['name'] )
      );

      update_option(
        Resoc_Template::template_manifest_option_name( $template['name'] ),
        json_encode( $manifest )
      );

      $demo_values = array();
      foreach( $manifest['parameters'] as $parameter ) {
        if ( Resoc_Template::is_last_minute_parameter( $parameter['name'] ) ) {
          // Do not store anything for these parameters:
          // they are filled as last-minute values,
          // during the image generation process
        } else if ( Resoc_Template::is_well_known_parameter( $parameter['name'] ) ) {
          if ( ! isset( $well_known_parameter_values[ $parameter['name'] ] ) ) {
            if ( $parameter['name'] == 'brandName' ) {
              $site_name = get_bloginfo( 'name' );
              $well_known_parameter_values[ $parameter['name'] ] = $site_name ?: 'My brand';
            } else if ( $parameter['name'] == 'logoUrl' ) {
              $custom_logo_id = get_option( 'site_icon' );
              $well_known_parameter_values[ $parameter['name'] ] = $custom_logo_id ?: NULL;
            }

            // Use a demo value... but not with image URL, which are supposed to come from
            // the media library
            if ( ! isset( $well_known_parameter_values[ $parameter['name'] ] ) &&
                $parameter[ 'type' ] != 'imageUrl' ) {
              $well_known_parameter_values[ $parameter['name'] ] = $parameter['demoValue'];
            }
          }
        } else {
          // Parameter specific to this template
          $demo_values[ $parameter['name'] ] = $parameter['demoValue'];
        }
      }
      update_option(
        Resoc_Template::template_values_option_name( $template['name'] ),
        $demo_values
      );
    }

    // Set or patch the well-known parameter values
    $existing_values = self::get_well_known_parameter_values();
    foreach( array_keys( $well_known_parameter_values ) as $parameter_name ) {
      if ( ! isset( $existing_values[ $parameter_name ] ) ) {
        $existing_values[ $parameter_name ] = $well_known_parameter_values[ $parameter_name ];
      }
    }
    update_option( Resoc_Template::WELL_KNOWN_PARAMETER_VALUES, $existing_values );

    self::mark_image_engine_as_loaded();
  }

  public static function parameter_settings_schema( $parameters, $settings_name ) {
    $values_schema = array();
    foreach( $parameters as $parameter ) {
      $values_schema[ $parameter['name'] ] =
        array( 'type' => $parameter['type'] == 'imageUrl'
          ? array( 'integer', 'null' ) // Images are actually IDs of WordPress media, and are not mandatory
          : 'string'
      );
    }

    return array(
      'type' => 'object',
      'show_in_rest' => array(
        'name' => $settings_name,
        'schema' => array(
          'type'  => 'object',
          'properties' => $values_schema
        )
      ),
      'default' => '[]',
    );
  }

  public static function init_settings_from_image_engine_options() {
    Resoc_Utils::log( "Register settings from image engine options" );

    register_setting(
      Resoc_Template::TEMPLATE_SETTINGS,
      Resoc_Template::TEMPLATE_LIST,
      array(
        'type' => 'array',
        'show_in_rest' => array(
          'name' => Resoc_Template::TEMPLATE_LIST,
          'schema' => array(
            'type'  => 'array',
            'items' => array(
              'type' => 'object',
              'properties' => array(
                'name' => array(
                  'type' => 'string',
                ),
              )
            )
          )
        ),
        'default' => '[]',
      )
    );

    register_setting(
      Resoc_Template::TEMPLATE_SETTINGS,
      Resoc_Template::ACTIVE_TEMPLATE_NAME,
      array(
        'type' => 'string',
        'show_in_rest' => true
      )
    );

    register_setting(
      Resoc_Template::TEMPLATE_SETTINGS,
      Resoc_Template::SHOULD_LOAD_IMAGE_ENGINE,
      array(
        'type' => 'boolean',
        'show_in_rest' => true,
        'default' => self::should_load_image_engine()
      )
    );

    register_setting(
      Resoc_Template::TEMPLATE_SETTINGS,
      Resoc_Template::CURRENT_IMAGE_ENGINE_URL,
      array(
        'type' => 'string',
        'show_in_rest' => true,
        'default' => self::get_image_engine_url()
      )
    );

    $templates = get_option( Resoc_Template::TEMPLATE_LIST, array() );
    if ( ! $templates ) {
      Resoc_Utils::log( "No registered templates, skip settings registration" );
      return;
    }

    $well_known_parameters = Resoc_Template::collect_well_known_preset_parameters( $templates );
    register_setting(
      Resoc_Template::TEMPLATE_SETTINGS,
      Resoc_Template::WELL_KNOWN_PARAMETER_VALUES,
      Resoc_Template::parameter_settings_schema(
        $well_known_parameters, Resoc_Template::WELL_KNOWN_PARAMETER_VALUES
      )
    );

    foreach( $templates as $template ) {
      Resoc_Utils::log( "Register settings for template {$template['name']}" );

      $manifestOptionName = Resoc_Template::template_manifest_option_name( $template['name'] );
      $manifest = json_decode( get_option( $manifestOptionName ), true );

      register_setting(
        Resoc_Template::TEMPLATE_SETTINGS,
        $manifestOptionName,
        array(
          'type' => 'string',
          'show_in_rest' => true
        )
      );

      $values_settings_name = Resoc_Template::template_values_option_name( $template['name'] );
      register_setting(
        Resoc_Template::TEMPLATE_SETTINGS,
        $values_settings_name,
        Resoc_Template::parameter_settings_schema( $manifest['parameters'], $values_settings_name )
      );
    }
  }

  public static function load_json( $url ) {
    $response = wp_remote_get( $url );

    if ( is_wp_error( $response ) ) {
      throw new Exception( $result->get_error_message() );
    }
    if ( !is_array( $response ) ) {
      throw new Exception( "Unknown error" );
    }

    $doc = json_decode( $response['body'], true );
    if ( !$doc ) {
      throw new Exception( "Cannot parse JSON document" );
    }

    return $doc;
  }

  public static function is_well_known_parameter( $parameterName ) {
    return in_array( $parameterName, array(
      'title', 'mainImageUrl', 'brandName', 'logoUrl', 'backgroundColor', 'textColor', 'textDirection'
    ) );
  }

  public static function is_last_minute_parameter( $parameterName ) {
    return in_array( $parameterName, array(
      'title', 'mainImageUrl'
    ) );
  }

  public static function is_preset_parameter( $parameterName ) {
    return ! Resoc_Template::is_last_minute_parameter( $parameterName );
  }

  public static function get_preset_parameter_values( $template_name ) {
    return array_merge(
      get_option( Resoc_Template::WELL_KNOWN_PARAMETER_VALUES, array() ),
      get_option( Resoc_Template::template_values_option_name( $template_name ), array() )
    );
  }

  /**
   * Media are base64 inlines, so the values can be used anywhere,
   * not just in this WordPress isntance.
   */
  public static function get_preset_parameter_standalone_values( $template_name ) {
    $values = Resoc_Template::get_preset_parameter_values( $template_name );

    $manifest = json_decode( Resoc_Template::get_template_manifest( $template_name ), true );
    foreach( $manifest['parameters'] as $parameter ) {
      if ( Resoc_Template::is_preset_parameter( $parameter['name'] ) && $parameter['type'] == 'imageUrl' ) {
        $values[ $parameter['name'] ] = Resoc_Utils::image_id_to_base64( $values[ $parameter['name'] ] );
      }
    }

    return $values;
  }

  public static function get_template_manifest( $template_name ) {
    return get_option( Resoc_Template::template_manifest_option_name( $template_name ) );
  }

  // Getters and Setters

  public static function get_active_template_name() {
    return get_option( Resoc_Template::ACTIVE_TEMPLATE_NAME );
  }

  public static function set_active_template_name( $template_name ) {
    update_option( Resoc_Template::ACTIVE_TEMPLATE_NAME, $template_name );
  }

  public static function get_well_known_parameter_values() {
    return get_option( Resoc_Template::WELL_KNOWN_PARAMETER_VALUES ) ?: array();
  }

  public static function set_well_known_parameter_values( $well_known_parameter_values) {
    update_option( Resoc_Template::WELL_KNOWN_PARAMETER_VALUES, $well_known_parameter_values );
  }

  public static function get_image_engine_url() {
    return get_option( Resoc_Template::CURRENT_IMAGE_ENGINE_URL ) ?: Resoc_Template::DEFAULT_IMAGE_ENGINE_URL;
  }

  public static function set_image_engine_url( $url ) {
    if ( $url == self::get_image_engine_url() ) {
      return;
    }

    update_option( Resoc_Template::CURRENT_IMAGE_ENGINE_URL, $url );
    update_option( self::SHOULD_LOAD_IMAGE_ENGINE, true );
  }

  public static function should_load_image_engine() {
    return get_option( Resoc_Template::SHOULD_LOAD_IMAGE_ENGINE, true );
  }

  public static function set_load_image_engine( $load_it ) {
    update_option( self::SHOULD_LOAD_IMAGE_ENGINE, $load_it );
  }

  public static function mark_image_engine_as_loaded() {
    self::set_load_image_engine( false );
  }
}
