/**
 * Subscription Countdown Timer
 * Fetches subscription status and displays countdown for monthly payments
 */

(function($) {
    'use strict';

    var syotimerInitialized = false;

    // Initialize on document ready
    $(document).ready(function() {
        initSubscriptionCountdown();
    });

    function initSubscriptionCountdown() {
        fetchSubscriptionStatus();
        
        // Refresh every 1 hour
        setInterval(fetchSubscriptionStatus, 3600000);
    }

    function fetchSubscriptionStatus() {
        $.ajax({
            url: 'partials/subscription-countdown/ajax/php/get-subscription-status.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    updateCountdownDisplay(response.data);
                } else {
                    console.log('Subscription status:', response.message);
                    hideCountdown();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching subscription status:', error);
                hideCountdown();
            }
        });
    }

    function updateCountdownDisplay(data) {
        if (!data) {
            hideCountdown();
            return;
        }

        var days = (data.days_until_payment === null || data.days_until_payment === undefined)
            ? null
            : parseInt(data.days_until_payment, 10);

        if (!data.next_due_date || !data.next_due_date_formatted || days === null || Number.isNaN(days)) {
            hideCountdown();
            return;
        }

        if (days <= 10) {
            showCountdown(days, data.next_due_date);
        } else {
            hideCountdown();
        }
    }

    function showCountdown(days, nextDueDateIso) {
        var $wrapper = $('#subscription-timer-wrapper');
        var $badge = $('#subscription-badge');

        // We are switching to SyoTimer UI; ensure old badge stays hidden.
        if ($badge.length) {
            $badge.addClass('d-none');
        }

        if (!$wrapper.length) {
            return;
        }

        // Ensure plugin is available
        if (!$.fn.syotimer) {
            console.error('SyoTimer plugin not loaded');
            $wrapper.addClass('d-none');
            return;
        }

        // Parse YYYY-MM-DD into a JS Date at end of day
        var parts = String(nextDueDateIso).split('-');
        if (parts.length !== 3) {
            $wrapper.addClass('d-none');
            return;
        }
        var y = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10) - 1;
        var d = parseInt(parts[2], 10);
        var deadline = new Date(y, m, d, 23, 59, 59);

        if (!syotimerInitialized) {
            $('#subscription-syotimer').syotimer({
                date: deadline,
                layout: 'dhms',
                doubleNumbers: true,
                effectType: 'opacity',
                lang: 'eng'
            });
            syotimerInitialized = true;
        } else {
            $('#subscription-syotimer').syotimer('setOption', 'date', deadline);
        }

        // Show (no blinking)
        $wrapper.removeClass('d-none');
        $wrapper.css('animation', 'none');
    }

    function hideCountdown() {
        var $wrapper = $('#subscription-timer-wrapper');
        if ($wrapper.length) {
            $wrapper.addClass('d-none');
            $wrapper.css('animation', 'none');
        }

        // Keep old badge hidden
        var $badge = $('#subscription-badge');
        if ($badge.length) {
            $badge.addClass('d-none');
        }
    }

    // Expose functions globally if needed
    window.SubscriptionCountdown = {
        refresh: fetchSubscriptionStatus,
        hide: hideCountdown
    };

})(jQuery);
