<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://resoc.io
 * @since      1.0.0
 *
 * @package    Resoc
 * @subpackage Resoc/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Resoc
 * @subpackage Resoc/public
 * @author     Philippe Bernard <philippe@resoc.io>
 */
class Resoc_Public {

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
	 * @param      string    $resoc       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $resoc, $version ) {

		$this->resoc = $resoc;
		$this->version = $version;

    // Disable Jetpack Open Graph markups
    add_filter( 'jetpack_enable_open_graph', '__return_false' );

    $image_patched = false;

    if ( Resoc_Compatibility::is_yoast_seo_active() ) {
      add_filter(
        'wpseo_add_opengraph_images',
        array( $this, 'get_yoast_og_image' )
      );
      Resoc_Utils::log( "Yoast is active, override its OpenGraph image" );
      $image_patched = true;
    }

    if ( Resoc_Compatibility::is_aiosp_active() ) {
      add_filter(
        'aioseo_facebook_tags',
        array( $this, 'override_aiosp_image_meta' ),
        10, 1
      );
      Resoc_Utils::log( "All in One SEO is active, override its OpenGraph image" );
      $image_patched = true;
    }

    if ( Resoc_Compatibility::is_blog2social_active() && Resoc_Compatibility::is_blog2social_og_tags_enabled() ) {
      add_filter(
        'b2s_og_meta_image',
        array( $this, 'override_blog2social_image' ),
        10, 1
      );
      Resoc_Utils::log( "Blog2Social is active, override its OpenGraph image" );
      $image_patched = true;
    }

    if ( Resoc_Compatibility::is_seopress_active() ) {
      add_filter(
        'seopress_social_og_thumb',
        array( $this, 'override_seopress_image' ),
        10, 1
      );
      Resoc_Utils::log( "SEOPress is active, override its OpenGraph image" );
      $image_patched = true;
    }

    if ( $image_patched ) {
      Resoc_Utils::log( "OpenGraph image of another plugin has been overriden, nothing to do" );
      return;
    }

    $conflicting_plugin = Resoc_Compatibility::conflicting_plugin();

    if ( $conflicting_plugin ) {
      Resoc_Utils::log( "Conflict with " . $conflicting_plugin . ", do nothing" );
      return;
    }

    add_action( 'wp_head', array( $this, 'add_opengraph_markups' ) );
	}

  public function get_yoast_og_image( $wpseo_opengraph_image ) {
    $post_id = get_the_ID();
    $specific_image_id = get_post_meta(
      $post_id,
      Resoc::POST_META_OG_IMAGE_ID,
      true
    );
    if ( $specific_image_id ) {
      $wpseo_opengraph_image->add_image_by_id( $specific_image_id );
    }
  }

  public function override_blog2social_image( $blog2social_image ) {
    $specific_image_id = get_post_meta(
      get_the_ID(),
      Resoc::POST_META_OG_IMAGE_ID,
      true
    );
    if ( $specific_image_id ) {
      $image_data = wp_get_attachment_metadata( $specific_image_id );

      if ( is_array( $image_data ) ) {
        return wp_get_attachment_image_url( $specific_image_id, 'full' );
      }
    }

    return $blog2social_image;
  }

  public function override_aiosp_image_meta( $facebookMeta ) {
    $specific_image_id = get_post_meta(
      get_the_ID(),
      Resoc::POST_META_OG_IMAGE_ID,
      true
    );
    if ( $specific_image_id ) {
      $image_data = wp_get_attachment_metadata( $specific_image_id );

      if ( is_array( $image_data ) ) {
        $image_data['url'] = wp_get_attachment_image_url( $specific_image_id, 'full' );
        $facebookMeta['og:image'] = $image_data['url'];

        if ( $image_data['width'] && $image_data['height'] ) {
          $facebookMeta['og:image:width'] = $image_data['width'];
          $facebookMeta['og:image:height'] = $image_data['height'];
        }
      }
    }

    return $facebookMeta;
  }

  public function override_seopress_image( $seopress_image ) {
    $specific_image_id = get_post_meta( get_the_ID(), Resoc::POST_META_OG_IMAGE_ID, true );

    if ( $specific_image_id ) {
      $image_data = wp_get_attachment_metadata( $specific_image_id );

      if ( is_array( $image_data ) ) {
        $image_data['url'] = wp_get_attachment_image_url( $specific_image_id, 'full' );

        $resoc_image = '<meta property="og:image" value="' . esc_attr( $image_data['url'] ) . '" />' . "\n";

        if ( $image_data['width'] && $image_data['height'] ) {
          $resoc_image .= '<meta property="og:image:width" value="' . esc_attr( $image_data['width'] ) . '" />' . "\n";
          $resoc_image .= '<meta property="og:image:height" value="' . esc_attr( $image_data['height'] ) . '" />' . "\n";
        }

        return $resoc_image;
      }
    }

    return $seopress_image;
  }

  public function add_opengraph_markups() {
    Resoc_Utils::log( "Inject OpenGraph markups" );

    $post_ID = get_the_ID();

    // Type
    echo '<meta property="og:type" content="website" />' . "\n";

    // Title
    $post_title = get_the_title( $post_ID );
    if ( $post_title ) {
      echo '<meta property="og:title" content="' . esc_attr( $post_title ) . '" />' . "\n";
    }

    // Description
    $post_excerpt = get_the_excerpt( $post_ID );
    if ( $post_excerpt ) {
      echo '<meta property="og:description" content="' . esc_attr( $post_excerpt ) . '" />' . "\n";
    }

    // Url
    $post_url = wp_get_canonical_url( $post_ID );
    if ( $post_url ) {
      echo '<meta property="og:url" content="' . esc_attr( $post_url ) . '" />' . "\n";
    }

    // Image
    $og_image_id = get_post_meta( $post_ID, Resoc::POST_META_OG_IMAGE_ID, true );

    if ( !$og_image_id ) {
      Resoc_Utils::log( "No OpenGraph image for post " . $post_ID );
      return;
    }

    Resoc_Utils::log( "OpenGraph image for post " . $post_ID . " is " . $og_image_id );

    $image_data = wp_get_attachment_metadata( $og_image_id );

    if ( is_array( $image_data ) ) {
      $image_data['url'] = wp_get_attachment_image_url( $og_image_id, 'full' );

      echo '<meta property="og:image" value="' . esc_attr( $image_data['url'] ) . '" />' . "\n";
      if ( $image_data['width'] && $image_data['height'] ) {
        echo '<meta property="og:image:width" value="' . esc_attr( $image_data['width'] ) . '" />' . "\n";
        echo '<meta property="og:image:height" value="' . esc_attr( $image_data['height'] ) . '" />' . "\n";
      }
    } else {
      Resoc_Utils::log( "Could not get metadata of image " . $og_image_id );
    }
  }
}
