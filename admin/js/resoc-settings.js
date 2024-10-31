(function( $ ) {
	'use strict';

  jQuery(document).ready(function() {
    jQuery('.resoc-color-picker').wpColorPicker({
      palettes: true
    });

    jQuery('.resoc-media-field').each(function() {
      var field = $(this);
      var button = field.find('.media-picker-button');

      var media = wp.media({
        button: {
          text: 'Use as logo'
        }
      });

      media.on('select', function() {
        var attachment = media.state().get('selection').first().toJSON();
        field.find(".media-id").val(attachment.id);
        field.find('.media-thumbnail').attr('src', attachment.url);
        field.find('.media-thumbnail').removeClass('hidden-media-thumbnail');
      });

      button.click(function() {
        media.open(button);
        return false;
      });
    });
  });
})( jQuery );
