<?php
/**
 * Some utilities and helpers.
 *
 * @link       https://resoc.io
 * @since      1.0.0
 *
 * @package    Resoc
 * @subpackage Resoc/admin
 */
class Resoc_Utils {
  public static function get_post_featured_image_url( $post_ID ) {
    $featured_image_id = get_post_thumbnail_id( $post_ID );
    if ( !$featured_image_id ) {
      return;
    }

    $featured_image = wp_get_attachment_image_src( $featured_image_id, 'full' );
    if ( is_null( $featured_image ) || empty( $featured_image ) ) {
      return NULL;
    }

    return $featured_image[0];
  }

  public static function image_url_to_base64( $image_url ) {
		$result = wp_remote_get( $image_url );
		if ( is_wp_error( $result ) ) {
      Resoc_Utils::log( "Cannot download image: " . $result->get_error_message() );
			return NULL;
		}
    $mimeType = $result['headers']['content-type'];

		$image = wp_remote_retrieve_body( $result );

    return 'data:' . $mimeType . ';base64,' . base64_encode( $image );
  }

  public static function image_id_to_base64( $image_id ) {
    $image_url = wp_get_attachment_url( $image_id );
    if ( ! $image_url ) {
      return;
    }

    return Resoc_Utils::image_url_to_base64( $image_url );
  }

  public static function get_logo_as_base64() {
    $options = get_option( Resoc_Settings::OPTIONS );
    if ( !$options ) {
      return;
    }

    $logo_id = $options[ Resoc_Settings::LOGO ];
    if ( !$logo_id ) {
      return;
    }

    return Resoc_Utils::image_id_to_base64( $logo_id );
  }

  public static function create_social_image( $template, $paramValues ) {
    $post_url = Resoc_Template::get_image_engine_url() . "/templates/$template/images/open-graph.jpg";

    $response = wp_remote_post( $post_url, array(
      'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
      'body'    => json_encode( $paramValues ),
      'timeout' => 30 // Image generation can take 10-20 seconds, depending on the engine
    ));

    if ( is_wp_error( $response ) ) {
      Resoc_Utils::log( "Error while generating: " . $response->get_error_message() );
      return NULL;
    }

    if ( $response['response']['code'] != 200 ) {
      Resoc_Utils::log( "Error while generating with status " . $response['response']['code'] . ": " . $response['body'] );
      return NULL;
    }

    return $response['body'];
  }

  public static function og_image_filename( $post_ID ) {
    return 'og-image-' . $post_ID . '.jpg';
  }

  public static function add_image_to_media_library( $image_data, $post_ID, $attach_id = NULL, $filename = NULL) {
    $upload_dir = wp_upload_dir();

    if ( !$filename ) {
      $filename = Resoc_Utils::og_image_filename( $post_ID );
    }

    // If an existing attachement exists, take its file path and name.
    // This is because using wp_update_attachment_metadata
    // with new file path and name does not affect
    // wp_get_attachment_image_url, which still returns the previous
    // file path and name.
    $file = NULL;
    if ( $attach_id ) {
      $file = get_attached_file( $attach_id );
    }

    if ( ! $file ) {
      if ( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
      } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
      }
    }

    file_put_contents( $file, $image_data );

    if ( !$attach_id ) {
      // Create new attachement if there is none
      // (else, the image is attached to the existing attachement)
      $wp_filetype = wp_check_filetype( $filename, null );
      $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name( $filename ),
        'post_content' => '',
        'post_status' => 'inherit'
      );

      $attach_id = wp_insert_attachment( $attachment, $file, $post_ID );
    }

    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    return $attach_id;
  }

  public static function is_plugin_configured() {
    return Resoc_Template::get_active_template_name() &&
      Resoc_Template::get_well_known_parameter_values() &&
      ( ! Resoc_Template::should_load_image_engine() );
  }

  public static function current_url() {
    if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) {
      $prefix = "https://";
    }
    else {
      $prefix = "http://";
    }

    return $prefix . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  }

  public static function log( $value ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
      return;
    }

    $prefix = "[Resoc] ";
    if ( is_array( $value ) || is_object( $value ) ) {
      error_log( $prefix . print_r( $value, true ) );
    } else {
      error_log( $prefix . $value );
    }
  }

  public static function hash_request( $request ) {
    return sha1( print_r( $request, true ) );
  }
}
