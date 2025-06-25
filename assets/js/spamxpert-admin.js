/**
 * SpamXpert Admin JavaScript
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Add smooth fade-in animation on page load
        $('.wrap').css('opacity', '0').animate({ opacity: 1 }, 300);

        // Animate dashboard cards on load
        if ($('.spamxpert-dashboard-cards').length) {
            $('.spamxpert-dashboard-cards .card').each(function (index) {
                $(this)
                    .css('opacity', '0')
                    .delay(index * 100)
                    .animate({ opacity: 1 }, 500);
            });
        }

        // Initialize tooltips if available
        if ($.fn.tooltip) {
            $('.spamxpert-tooltip').tooltip();
        }

        // Confirm dialogs for destructive actions
        $('.spamxpert-confirm').click(function (e) {
            var message = $(this).data('confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        // Initialize tabs - hide all except first with fade effect
        if ($('.spamxpert-settings-tabs').length) {
            $('.spamxpert-settings-tabs .tab-content').hide();
            $('.spamxpert-settings-tabs .tab-content:first').fadeIn(300);
        }

        // Settings Page Tab Switching with animation
        var $tabsContainer = $('.spamxpert-settings-tabs');
        var $tabs = $tabsContainer.find('.nav-tab');
        var $tabContents = $tabsContainer.find('.tab-content');
        var isAnimating = false;

        // Prevent double-tap zoom on mobile
        var lastTouchTime = 0;

        // Tab click handler - support both click and touch
        $tabs.on('click touchend', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Prevent double-tap on touch devices
            var currentTime = new Date().getTime();
            var tapLength = currentTime - lastTouchTime;
            if (tapLength < 500 && tapLength > 0) {
                return false;
            }
            lastTouchTime = currentTime;

            if (isAnimating) return;

            var $clickedTab = $(this);

            // Don't do anything if already active
            if ($clickedTab.hasClass('nav-tab-active')) {
                return;
            }

            isAnimating = true;

            // Add loading state
            $clickedTab.css('position', 'relative');

            // Update active tab
            $tabs.removeClass('nav-tab-active').attr('aria-selected', 'false');
            $clickedTab
                .addClass('nav-tab-active')
                .attr('aria-selected', 'true')
                .focus();

            // Show corresponding content with smooth animation
            var targetTab = $clickedTab.attr('href');
            var $targetContent = $(targetTab);
            var $currentContent = $tabContents.filter(':visible');

            // Add changing class for animation
            $currentContent.addClass('tab-changing');

            setTimeout(function () {
                $currentContent.hide().removeClass('tab-changing');
                $targetContent.show().addClass('tab-changing');

                // Trigger reflow to ensure transition works
                $targetContent[0].offsetHeight;

                $targetContent.removeClass('tab-changing');
                isAnimating = false;

                // Update URL hash without jumping
                if (history.replaceState) {
                    history.replaceState(null, null, targetTab);
                }
            }, 150);
        });

        // Keyboard navigation for tabs
        $tabs.on('keydown', function (e) {
            var $currentTab = $(this);
            var $allTabs = $tabs.filter(':visible');
            var currentIndex = $allTabs.index($currentTab);
            var newIndex;

            switch (e.key) {
                case 'ArrowLeft':
                case 'ArrowUp':
                    e.preventDefault();
                    newIndex = currentIndex - 1;
                    if (newIndex < 0) newIndex = $allTabs.length - 1;
                    $allTabs.eq(newIndex).click();
                    break;

                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    newIndex = currentIndex + 1;
                    if (newIndex >= $allTabs.length) newIndex = 0;
                    $allTabs.eq(newIndex).click();
                    break;

                case 'Home':
                    e.preventDefault();
                    $allTabs.first().click();
                    break;

                case 'End':
                    e.preventDefault();
                    $allTabs.last().click();
                    break;
            }
        });

        // Set initial ARIA attributes
        $tabs.attr('role', 'tab').attr('aria-selected', 'false');
        $tabs.filter('.nav-tab-active').attr('aria-selected', 'true');
        $tabContents.attr('role', 'tabpanel');

        // Handle direct hash navigation
        if (window.location.hash && $(window.location.hash).length) {
            var $hashTab = $tabs.filter(
                '[href="' + window.location.hash + '"]'
            );
            if ($hashTab.length) {
                $hashTab.click();
            }
        }

        // Debug Mode Warning
        $('input[name="spamxpert_debug_mode"]').change(function () {
            if ($(this).is(':checked')) {
                var warningMessage = spamxpert_admin_l10n
                    ? spamxpert_admin_l10n.debug_warning
                    : 'Warning: Debug mode will make honeypot fields visible and may reduce spam protection effectiveness. Continue?';
                if (!confirm(warningMessage)) {
                    $(this).prop('checked', false);
                }
            }
        });

        // Logs Page Check All Functionality
        $('#spamxpert-logs-table .check-column input[type="checkbox"]')
            .first()
            .change(function () {
                var checked = $(this).is(':checked');
                $(
                    '#spamxpert-logs-table tbody .check-column input[type="checkbox"]'
                ).prop('checked', checked);
            });

        // Alternative selector for logs check all (for compatibility)
        $('.wp-list-table .check-column input[type="checkbox"]')
            .first()
            .change(function () {
                var checked = $(this).is(':checked');
                $(this)
                    .closest('table')
                    .find('tbody .check-column input[type="checkbox"]')
                    .prop('checked', checked);
            });

        // Live stats update on dashboard
        if ($('#spamxpert-dashboard').length) {
            // Could implement AJAX updates here in the future
        }

        // Settings page form validation
        $(
            '.spamxpert-settings-form, form[action*="spamxpert-settings"]'
        ).submit(function () {
            var honeypotCount = $(
                'input[name="spamxpert_honeypot_count"]'
            ).val();
            if (honeypotCount && (honeypotCount < 1 || honeypotCount > 5)) {
                alert('Honeypot count must be between 1 and 5');
                return false;
            }

            var timeThreshold = $(
                'input[name="spamxpert_time_threshold"]'
            ).val();
            if (timeThreshold && (timeThreshold < 1 || timeThreshold > 60)) {
                alert('Time threshold must be between 1 and 60 seconds');
                return false;
            }

            return true;
        });

        // Export functionality
        $('#spamxpert-export-logs, a[href*="spamxpert_export_logs"]').click(
            function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                window.location.href = url;
            }
        );

        // Add page identifier classes to body for specific styling
        if ($('.wrap h1').text().indexOf('SpamXpert Dashboard') > -1) {
            $('body').addClass('spamxpert-dashboard-page');
        }
        if ($('.wrap h1').text().indexOf('SpamXpert Settings') > -1) {
            $('body').addClass('spamxpert-settings-page');
        }
        if ($('.wrap h1').text().indexOf('Spam Logs') > -1) {
            $('body').addClass('spamxpert-logs-page');
        }

        // Clean up notices on SpamXpert pages
        if (
            $('body').hasClass('spamxpert-dashboard-page') ||
            $('body').hasClass('spamxpert-settings-page') ||
            $('body').hasClass('spamxpert-logs-page')
        ) {
            // Remove any dynamically added notices
            var cleanNotices = function () {
                // Remove notices that don't have our class
                $(
                    '.notice:not(.spamxpert-notice), .error:not(.spamxpert-notice), .updated:not(.spamxpert-notice), .update-nag:not(.spamxpert-notice)'
                ).remove();

                // Remove specific plugin notice containers
                $(
                    '.woocommerce-layout__notice-list, .woocommerce-admin-notice, .notice-houzez, .houzez-admin-notice'
                ).remove();
            };

            // Clean on page load
            cleanNotices();

            // Clean again after a short delay to catch late-loading notices
            setTimeout(cleanNotices, 500);
            setTimeout(cleanNotices, 1000);

            // Watch for new notices being added
            var observer = new MutationObserver(function (mutations) {
                cleanNotices();
            });

            // Start observing the body for added nodes
            observer.observe(document.body, {
                childList: true,
                subtree: true,
            });
        }
    });
})(jQuery);
