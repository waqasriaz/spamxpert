/**
 * SpamXpert Public JavaScript
 * Keep minimal for performance (< 5KB)
 */

(function ($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function () {
        // Debug mode
        var isDebug = $('body').hasClass('spamxpert-debug');

        // Reinforce honeypot field protection
        $('.spamxpert-hp-field input').each(function () {
            $(this).attr('tabindex', '-1').attr('autocomplete', 'off').val(''); // Ensure empty

            // Prevent any interaction
            $(this).on('focus click', function (e) {
                e.preventDefault();
                $(this).blur();
            });
        });

        // Form submission tracking
        $('form').each(function () {
            var $form = $(this);
            var formStartTime = Date.now();

            $form.on('submit', function (e) {
                // Check if form has honeypot fields
                var $honeypots = $form.find('.spamxpert-hp-field input');

                if ($honeypots.length > 0) {
                    var filled = false;

                    $honeypots.each(function () {
                        if ($(this).val() !== '') {
                            filled = true;
                            if (isDebug) {
                                console.error(
                                    'SpamXpert: Honeypot field filled!',
                                    this
                                );
                                alert(
                                    'Debug: Honeypot field was filled. Form would be blocked.'
                                );
                                e.preventDefault();
                            }
                        }
                    });
                }

                // Log submission time in debug mode
                if (isDebug) {
                    var elapsed = (Date.now() - formStartTime) / 1000;
                    console.log(
                        'SpamXpert: Form submitted after',
                        elapsed,
                        'seconds'
                    );

                    if (elapsed < spamxpert_ajax.time_threshold) {
                        alert(
                            'Debug: Form submitted too quickly (' +
                                elapsed +
                                's). Would be blocked.'
                        );
                        e.preventDefault();
                    }
                }
            });
        });

        // AJAX form compatibility
        $(document).on('ajaxComplete', function (event, xhr, settings) {
            // Re-initialize for dynamically loaded forms
            setTimeout(function () {
                $('.spamxpert-hp-field input').val('').attr('tabindex', '-1');
            }, 100);
        });
    });
})(jQuery);
