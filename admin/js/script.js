jQuery(document).ready(function($) {
   $('#contact-form-editor').tabs({
        active: 0,
        activate: function(event, ui) {
            $('#active-tab').val(ui.newTab.index());
        }
    });
});

(function($) {
    'use strict';
    

    $('form.tag-generator-panel').submit(function(event) {
        return false;
    });
    
    $('')
})(jQuery);