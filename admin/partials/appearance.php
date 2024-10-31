<?php
  if ( isset( $_GET['settings-updated'] ) ) {
    add_settings_error( 'resoc_messages', 'resoc_message',
      '<p>Settings Saved! To see your template in action:</p><ul><li>Edit an existing post or start a new one</li><li>Make sure it has a featured image</li><li>Submit its URL to the <a href="https://developers.facebook.com/tools/debug/" target="_blank">Facebook debugger</a></li></ul>',
      'updated' );
  }

  settings_errors( 'resoc_messages' );
?>

<div class="wrap">
  <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

  <form action="options.php" method="post">
    <?php
      settings_fields( 'resoc' );
      do_settings_sections( 'resoc' );
      submit_button( 'Save Settings' );
    ?>
  </form>
</div>
