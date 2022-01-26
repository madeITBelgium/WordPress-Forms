jQuery( document ).ready( function( $ ) {
    $('.madeit-forms-ajax').submit(function(e) {
        e.preventDefault();
        submitMadeitForm($(this).attr('id'));
    });
});

function submitMadeitForm(formId) {
    console.log(formId);
    if(jQuery('#' + formId).hasClass('madeit-forms-ajax')) {
        console.log('Found');
        /*if(! jQuery('#' + formId).checkValidity()) {
            console.log('Validation failed');
            jQuery('#' + formId).find(':submit').click();
            grecaptcha.reset();
        }*/
    
        console.log('Remove messages');
        jQuery('.madeit-form-success, .madeit-form-error').remove();
        var formData = madeitObjectifyForm(jQuery('#' + formId).serializeArray());
        formData.action = 'madeit_forms_submit';
        jQuery.post('/wp-admin/admin-ajax.php', formData, function(data) {
            console.log(data);
            if(data.success) {
                jQuery('#' + formId).before('<div class="madeit-form-success">' + data.message + '</div>');
                jQuery('#' + formId).hide();
                jQuery('body').append(data.html);
            } else {
                var cls = jQuery('#' + formId).find('[type=submit]').attr('class');
                var val = jQuery('#' + formId).find('[type=submit]').attr('value');
                var id = jQuery('#' + formId).find('[type=submit]').attr('id');
                jQuery('#' + formId).find('[type=submit]').addClass('delete-submit');
                jQuery('#' + formId).find('[type=submit]').before('<input name="btn_submit" id="' + id + '" type="submit" class="' + cls + '" value="' + val + '">');
                jQuery('#' + formId).find('.delete-submit').remove();
                
                jQuery('#' + formId).before('<div class="madeit-form-error">' + data.message + '</div>');
                grecaptcha.reset();
            }
        }, 'json');
    }
    else {
        document.getElementById(formId).submit();
    }
}

function madeitObjectifyForm(formArray) {//serialize data function
    var returnArray = {};
    for (var i = 0; i < formArray.length; i++){
        returnArray[formArray[i]['name']] = formArray[i]['value'];
    }
    return returnArray;
}

document.querySelectorAll(".range-wrap").forEach(wrap => {
    const range = wrap.querySelector(".range");
    const bubble = wrap.querySelector(".range-bubble");

    range.addEventListener("input", () => {
        setBubble(range, bubble);
    });
    setBubble(range, bubble);
});

function setBubble(range, bubble) {
    const val = range.value;
    const min = range.min ? range.min : 0;
    const max = range.max ? range.max : 100;
    const newVal = Number(((val - min) * 100) / (max - min));
    bubble.innerHTML = val;

    // Sorta magic numbers based on size of the native UI thumb
    bubble.style.left = `calc(${newVal}% + (${8 - newVal * 0.15}px))`;
}