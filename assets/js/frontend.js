/**
 * Deimos Lost & Found Animals - Frontend JS (Simplified)
 *
 * @author  Wojtek Kobylecki / Bella Design Studio
 * @version 1.0.6
 */

(function($) {
    'use strict';

    // FILTERS
    var LFA_Filters = {
        init: function() {
            var self = this;
            this.$grid = $('#lfa-grid');
            this.$cards = this.$grid.find('.lfa-card');
            this.$status = $('#lfa-filter-status');
            this.$gender = $('#lfa-filter-gender');
            this.$sort = $('#lfa-sort');
            this.$reset = $('#lfa-reset');
            this.$count = $('#lfa-count');
            this.$noResults = $('#lfa-no-results');

            if (!this.$grid.length) return;

            this.$status.on('change', function() { self.apply(); });
            this.$gender.on('change', function() { self.apply(); });
            this.$sort.on('change', function() { self.apply(); });
            this.$reset.on('click', function() { self.reset(); });
        },

        apply: function() {
            var status = this.$status.val();
            var gender = this.$gender.val();
            var sort = this.$sort.val();
            var visible = [];

            this.$cards.each(function() {
                var $card = $(this);
                var show = true;
                if (status && $card.data('status') !== status) show = false;
                if (gender && $card.data('gender') !== gender) show = false;
                if (show) {
                    $card.show();
                    visible.push($card);
                } else {
                    $card.hide();
                }
            });

            visible.sort(function(a, b) {
                var $a = $(a), $b = $(b);
                switch (sort) {
                    case 'oldest':
                        return new Date($a.data('date')) - new Date($b.data('date'));
                    case 'name-asc':
                        return ($a.data('name') || '').localeCompare($b.data('name') || '');
                    case 'name-desc':
                        return ($b.data('name') || '').localeCompare($a.data('name') || '');
                    default:
                        return new Date($b.data('date')) - new Date($a.data('date'));
                }
            });

            var self = this;
            visible.forEach(function($card) { self.$grid.append($card); });
            this.$count.text(visible.length);

            if (visible.length === 0 && this.$cards.length > 0) {
                this.$noResults.show();
            } else {
                this.$noResults.hide();
            }
        },

        reset: function() {
            this.$status.val('');
            this.$gender.val('');
            this.$sort.val('newest');
            this.apply();
        }
    };

    $(document).ready(function() {
        LFA_Filters.init();
    });
})(jQuery);
