<?php
  if ( isset( $_GET['settings-updated'] ) ) {
    add_settings_error( 'resoc_messages', 'resoc_message', 'Settings Saved', 'updated' );
  }

  settings_errors( 'resoc_messages' );
?>

<div class="wrap">
  <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

  <p>
    Resoc is about doing this:
  </p>

  <?php
    include_once( plugin_dir_path(__FILE__) . 'before-after.php' );
  ?>

  <?php
    Resoc_Compatibility::notify_conflict();
  ?>
</div>
