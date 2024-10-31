<?php

// For is_plugin_active to be available
include_once(ABSPATH.'wp-admin/includes/plugin.php');

class Resoc_Compatibility {
  /**
   * Returns the name of the plugin which might cause a conflict.
   * Returns NULL if there is no such conflicting plugin.
   */
  public static function conflicting_plugin() {
    if ( is_plugin_active( 'business-directory-plugin/business-directory-plugin.php' ) ) {
      return "Business Directory Plugin";
    }
    if ( is_plugin_active( 'wonderm00ns-simple-facebook-open-graph-tags/wonderm00n-open-graph.php' ) ) {
      return "Open Graph for Facebook, Google+ and Twitter Card Tags";
    }

    // TODO: Add additional conflicting plugins

    return NULL;
  }

  public static function is_yoast_seo_active() {
    return is_plugin_active( 'wordpress-seo/wp-seo.php' );
  }

  // All In One SEO Pack
  public static function is_aiosp_active() {
    return is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' );
  }

  public static function is_seopress_active() {
    return is_plugin_active( 'wp-seopress/seopress.php' );
  }

  public static function is_blog2social_active() {
    return is_plugin_active( 'blog2social/blog2social.php' );
  }

  public static function is_blog2social_og_tags_enabled() {
    $blog2social_options = get_option( 'B2S_PLUGIN_GENERAL_OPTIONS' );
    if ( $blog2social_options && !$blog2social_options[ 'og_active' ] ) {
      return false;
    }

    return true;
  }

  public static function notify_conflict() {
    $plugin = Resoc_Compatibility::conflicting_plugin();

    $email_subject = "Please make Resoc compatible with " . $plugin;
    $email_subject = str_replace( '+', '%20', urlencode( $email_subject ) );
    $email_body = <<<EOF
Hi,

I cannot use the Resoc WordPress plugin because it is not compatible with PLUGIN_NAME.

Could you update Resoc to fix this and let me know when the update is available?

Regards
EOF;
    $email_body =
    str_replace( 'PLUGIN_NAME', $plugin,
      str_replace( '+', '%20', urlencode( $email_body ) )
    );
?>
<div>
<div class="resoc-narrow-section">
  <h3 class="resoc-title-error">
    You cannot use Resoc yet
  </h3>

  <p>
    The WordPress ecosystem is very rich and a lot of plugins
    manage the social networks metadata, as Resoc does.
    You are currently using <?php echo $plugin ?>, which
    Resoc is not compatible with yet.
  </p>

  <p>
    How to solve this? That's easy. Simply ask for it. Seriously.
    We are willing to make Resoc usable in all situations,
    so clicking the button below is all it takes to ask us to
    fix this.
  </p>

  <a
    href="mailto:contact@resoc.io?subject=<?php echo $email_subject ?>&body=<?php echo $email_body ?>"
    class="button button-primary"
  >
    Please make Resoc compatible with <?php echo $plugin ?>
  </a>

  <p>
    Note: You do <i>not</i> have to deactivate Resoc.
    For now it is in idle mode, not affecting your visitors's experience
    and not causing any conflict. You will be able to start using it
    as soon as an update is released.
  </p>
</div>
<?php
  }
}
