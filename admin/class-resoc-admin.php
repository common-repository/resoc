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
class Resoc_Admin {

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

    add_action( 'save_post',
      array( $this, 'post_saved' ) );

    add_action( 'updated_post_meta',
      array( $this, 'metadata_updated' ), 10, 3 );

    add_action( 'admin_notices', array( $this, 'finish_setup_notice' ));

    add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2);

    add_action( 'admin_init', array( $this, 'register_preview_script' ) );

    add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_preview_script' ) );

    add_action( 'social_image_generation', array( $this, 'generate_image_for_post' ), 10, 1 );

    add_action( 'admin_menu', array( $this, 'add_option_menu' ) );

    // Options / Settings
    add_action( 'rest_api_init', array( $this, 'register_settings' ) );

    add_action('wp_ajax_resoc_load_image_engine', array( $this, 'load_image_engine' ) );

    add_action( 'admin_init', 'Resoc_Settings::migrate_old_settings' );
  }

  function load_image_engine() {
    Resoc_Utils::log( "Load image engine" );
    Resoc_Template::load_image_engine( Resoc_Template::get_image_engine_url() );
  }

  function register_settings() {
    Resoc_Template::init_settings_from_image_engine_options();

    register_setting(
      Resoc_Template::TEMPLATE_SETTINGS,
      Resoc_Settings::SHOW_PLUGIN_EXPLANATIONS,
      array(
        'type' => 'boolean',
        'show_in_rest' => true,
        'default' => true
      )
    );
  }

  public function options_assets() {
    wp_enqueue_style( 'wp-components' );

    wp_enqueue_script( 'options-js' );

    wp_enqueue_media();

    wp_localize_script('options-js', 'globalSettings', array(
      'imagesBaseDir' => esc_attr( plugin_dir_url( __FILE__ ) . 'img' ),
      'showPluginExplanations' => get_option( Resoc_Settings::SHOW_PLUGIN_EXPLANATIONS, true ),
      'ajaxUrl' => admin_url( 'admin-ajax.php', isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' )
    ));
  }

  public function menu_callback() {
    include_once( plugin_dir_path(__FILE__) . 'partials/appearance-container.php' );
  }

  public function add_option_menu() {
    wp_register_script(
      'options-js',
      plugins_url( 'js/options.js', __FILE__ ),
      array( 'wp-api', 'wp-components', 'wp-element', 'wp-edit-post', 'wp-data', 'wp-compose' ),
      RESOC_VERSION,
      true
    );

    $page_hook_suffix = add_theme_page(
      'Social images',
      'Social images',
      'manage_options',
      'resoc_social_images_appearance_menu',
      array( $this, 'menu_callback' )
    );

    add_action( "admin_print_scripts-{$page_hook_suffix}", array( $this, 'options_assets' ) );
  }

  public function register_preview_script() {
    wp_register_script(
      'preview-metabox-js',
      plugins_url( 'js/preview-metabox.js', __FILE__ ),
      array( 'wp-plugins', 'wp-edit-post', 'wp-element' )
    );
  }

  public function enqueue_preview_script() {
    global $wp_scripts;

    if ( $wp_scripts->query( 'wp-edit-widgets', 'enqueued' ) ||
         $wp_scripts->query( 'wp-customize-widgets', 'enqueued' )
    ){
      // This is to prevent an error triggered by wp_check_widget_editor_deps in WordPress 5.8+
      // When this code is run, we are not in the editor anyway but in Appearance > Widgets.
      return;
    }

    wp_enqueue_script( 'preview-metabox-js' );

    $template_name = Resoc_Template::get_active_template_name();

    $scriptData = array(
      'pluginConfigured' => Resoc_Utils::is_plugin_configured(),
      'appearanvePageUrl' => admin_url( '/themes.php?page=resoc_social_images_appearance_menu' ),
      'templatesBaseUrl' => Resoc_Template::get_image_engine_url(),
      'templateName' => $template_name,
      'templateManifest' => Resoc_Template::get_template_manifest( $template_name ),
      'presetParamValues' => json_encode( Resoc_Template::get_preset_parameter_values( $template_name ) )
    );

    wp_localize_script('preview-metabox-js', 'template_options', $scriptData);
  }

  public function plugin_action_links( $links, $file ) {
    static $this_plugin;

    if ( ! $this_plugin ) {
        $this_plugin = resoc_plugin_base_name();
    }

    if ( $file == 'resoc/resoc.php' ) {
      $settings_link = '<a href="' . admin_url( '/themes.php?page=resoc_social_images_appearance_menu' ) . '">' . 'Settings' . '</a>';
      array_unshift( $links, $settings_link );
    }

    return $links;
  }

  public function metadata_updated( $meta_ID, $post_ID, $meta_key ) {
    if ( $meta_key == '_thumbnail_id' ) {
      Resoc_Utils::log( "Thumbnail image of post " . $post_ID  . " was updated, regenerate its social image" );
      $this->schedule_image_generation_for_post( $post_ID );
    }
  }

  public function post_saved( $post_ID ) {
    Resoc_Utils::log( "Post $post_ID saved, maybe generate its social image" );

    $post = get_post( $post_ID );
    if ( $post->post_type == 'revision' ) {
      Resoc_Utils::log( "Post $post_ID is a revision, skip" );
      return;
    }

    $this->schedule_image_generation_for_post( $post_ID );
  }

  public function schedule_image_generation_for_post( $post_ID ) {
    Resoc_Utils::log( "Schedule social image generation for post $post_ID" );
    wp_schedule_single_event( time(), 'social_image_generation', array( $post_ID ) );
  }

  public function generate_image_for_post( $post_ID ) {
    Resoc_Utils::log( "Generate social image for post $post_ID" );

    $post = get_post( $post_ID );

    $feature_image_url = Resoc_Utils::get_post_featured_image_url( $post->ID );
    if ( !$feature_image_url ) {
      Resoc_Utils::log( "Cannot get featured image for post " . $post->ID );
      return;
    }

    $featured_image = Resoc_Utils::image_url_to_base64( $feature_image_url );
    if ( !$featured_image ) {
      Resoc_Utils::log( "Cannot turn featured image URL (" . $feature_image_url . ") to Base64" );
      return;
    }

    if ( !Resoc_Utils::is_plugin_configured() ) {
      Resoc_Utils::log( "Plugin is not configured" );
      return;
    }

    $request = array(
      'template' => Resoc_Template::get_active_template_name(),
      'parameters' => array_merge(
        Resoc_Template::get_preset_parameter_standalone_values( Resoc_Template::get_active_template_name() ),
        array(
          'title' => $post->post_title,
          'mainImageUrl' => $featured_image,
        )
      )
    );

    $current_request_hash = Resoc_Utils::hash_request( $request );
    $previous_request_hash = get_post_meta( $post->ID, Resoc::POST_META_LATEST_REQUEST_HASH, true );
    if ( $previous_request_hash && $previous_request_hash == $current_request_hash ) {
      Resoc_Utils::log( "No change since the last time the image was generated, do nothing" );
      return;
    }

    $social_image = Resoc_Utils::create_social_image( $request['template'], $request['parameters'] );
    if ( !$social_image ) {
      Resoc_Utils::log( "Cannot generate social image for post " . $post->ID );
      return;
    }

    // Get existing OpenGraph image to update it as an attachement
    $existing_og_image_id = get_post_meta( $post->ID, Resoc::POST_META_OG_IMAGE_ID, true );

    $social_image_id = Resoc_Utils::add_image_to_media_library( $social_image, $post->ID, $existing_og_image_id );
    if ( !$social_image_id ) {
      Resoc_Utils::log( "Cannot store/attach social image for post " . $post->ID );
      return;
    }

    update_post_meta( $post->ID, Resoc::POST_META_OG_IMAGE_ID, $social_image_id );

    update_post_meta( $post->ID, Resoc::POST_META_LATEST_REQUEST_HASH, $current_request_hash );

    Resoc_Utils::log( "OpenGraph image " . $social_image_id . " attached to post " . $post->ID );
  }

  public function finish_setup_notice() {
    if ( Resoc_Utils::is_plugin_configured() ) {
      return;
    }

    $settings_page_url = admin_url( '/themes.php?page=resoc_social_images_appearance_menu' );
    if ( Resoc_Utils::current_url() == $settings_page_url ) {
      return;
    }

    ?>
    <div class="notice notice-warning is-dismissible">
      <p>
        <strong>Resoc Social Images</strong> is almost ready!
        <a href="<?php echo $settings_page_url ?>">Choose and customize your template</a>
        to complete the setup.
      </p>
    </div>
    <?php
  }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Resoc_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Resoc_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->resoc, plugin_dir_url( __FILE__ ) . 'css/resoc-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Resoc_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Resoc_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->resoc, plugin_dir_url( __FILE__ ) . 'js/resoc-admin.js', array( 'jquery' ), $this->version, false );
  }

}
