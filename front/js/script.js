jQuery( document ).ready( function( $ ) {
    $('.madeit-forms-ajax').submit(function(e) {
        e.preventDefault();
        submitMadeitForm($(this).attr('id'));
    });

    $('.madeit-forms-noajax').submit(function() {
        var formId = $(this).attr('id');
        console.log('Submitting form with ID:', formId);
        if(!formId) {
            console.error('Form ID is required for non-ajax forms to prevent multiple submissions.');
            return true;
        }

        if (isSubmitLoading(formId)) {
            console.log('Form submission is already in progress for form ID:', formId);
            return false;
        }

        setSubmitLoading(formId, true);
        return true;
    });

    if($('.madeit-forms-quiz-container').length) {
        //Has quiz!

        $('.madeit-forms-quiz-question-button-prev').click(function(e) {
            e.preventDefault();
            var container = $(this).parents('.madeit-forms-quiz-container');
            container.find('hide-question').removeClass('hide-question');

            var steps = container.attr('data-steps');
            var currentStep = container.attr('data-current-step');
            if(currentStep > 1) {
                currentStep--;
            }
            container.attr('data-current-step', currentStep);
            
            container.find('.madeit-forms-quiz-question').addClass('hide-question');
            container.find('.madeit-forms-quiz-question[data-question=' + currentStep + ']').removeClass('hide-question');

			var event = new CustomEvent("madeit-forms-quiz-prev", { "detail": currentStep });
			document.dispatchEvent(event);
        });

        $('.madeit-forms-quiz-question-button-next').click(function(e) {
            e.preventDefault();
            var container = $(this).parents('.madeit-forms-quiz-container');
            container.find('hide-question').removeClass('hide-question');

            var steps = container.attr('data-steps');
            var currentStep = container.attr('data-current-step');
            if(currentStep < steps) {
                currentStep++;
            }

            container.attr('data-current-step', currentStep);
            
            container.find('.madeit-forms-quiz-question').addClass('hide-question');
            container.find('.madeit-forms-quiz-question[data-question=' + currentStep + ']').removeClass('hide-question');
            
			var event = new CustomEvent("madeit-forms-quiz-next", { "detail": currentStep });
			document.dispatchEvent(event);
        });
    }
});

function getFormJSON(form) {
    const data = new FormData(form);
    return Array.from(data.keys()).reduce((result, key) => {
        var newKey = key;
        if(newKey.endsWith('[]')) {
            newKey = newKey.substring(0, newKey.length - 2);
        }

        if (result[newKey]) {
            result[newKey] = data.getAll(key)
            return result
        }
        
        result[newKey] = data.get(key);
        return result;
    }, {});
  };

function submitMadeitForm(formId) {
    if(jQuery('#' + formId).hasClass('madeit-forms-ajax')) {
        if (isSubmitLoading(formId)) {
            return;
        }

        setSubmitLoading(formId, true);

        /*if(! jQuery('#' + formId).checkValidity()) {
            console.log('Validation failed');
            jQuery('#' + formId).find(':submit').click();
            grecaptcha.reset();
        }*/
    
        jQuery('.madeit-form-success, .madeit-form-error').remove();
        var form = document.querySelector('#' + formId);
        const result = getFormJSON(form);

        result.action = 'madeit_forms_submit';
        jQuery.post('/wp-admin/admin-ajax.php', result, function(data) {
            setSubmitLoading(formId, false);

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
		    
                jQuery('html, body').animate({
                    scrollTop: jQuery('.madeit-form-error').offset().top
                }, 1000);
                grecaptcha.reset();
            }
        }, 'json').fail(function() {
            setSubmitLoading(formId, false);
        });
    }
    else {
        setSubmitLoading(formId, true);
        document.getElementById(formId).submit();
    }
}

function getSubmitButton(formId) {
    return jQuery('#' + formId).find('button.wp-block-madeitforms-submit-button__link').first();
}

function isSubmitLoading(formId) {
    return getSubmitButton(formId).hasClass('madeit-submit-loading');
}

function setSubmitLoading(formId, isLoading) {
    var submitButton = getSubmitButton(formId);
    console.log('Setting submit loading state for form ID:', formId, 'Loading:', isLoading);
    if(!submitButton.length) {
        console.error('Submit button not found for form ID:', formId);
        return;
    }

    var isButtonElement = submitButton.is('button');
    var originalLabel = submitButton.data('madeit-original-label');
    if(typeof originalLabel === 'undefined') {
        originalLabel = isButtonElement ? submitButton.html() : submitButton.val();
        submitButton.data('madeit-original-label', originalLabel);
    }

    if(isLoading) {
        submitButton.addClass('madeit-submit-loading');
        submitButton.prop('disabled', true);

        if(isButtonElement) {
            submitButton.html('<span class="madeit-submit-loading-indicator" aria-hidden="true"></span><span class="madeit-submit-loading-text">Bezig...</span>');
        } else {
            submitButton.val('Bezig...');
        }
    } else {
        submitButton.removeClass('madeit-submit-loading');
        submitButton.prop('disabled', false);

        if(isButtonElement) {
            submitButton.html(originalLabel);
        } else {
            submitButton.val(originalLabel);
        }
    }
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
