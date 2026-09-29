/**
 * Deimos Lost & Found Animals - Frontend JS
 *
 * @package   Deimos_Lost_Found_Animals
 * @author    Wojtek Kobylecki
 * @copyright Copyright (c) 2026 Wojtek Kobylecki
 * @license   GPL-2.0-or-later
 */

(function ($) {
	'use strict';

	/**
	 * Filter/sort controller for one shortcode instance.
	 *
	 * Every lookup is scoped to the container element, so any number of grids
	 * can live on the same page without interfering with each other.
	 *
	 * @param {Element} container Shortcode container element.
	 */
	function deimlofoGrid(container) {
		var $container = $(container);
		var $grid = $container.find('.deimlofo-grid').first();

		if (!$grid.length) {
			return;
		}

		var $cards = $grid.find('.deimlofo-card');
		var $status = $container.find('.deimlofo-filter-status');
		var $gender = $container.find('.deimlofo-filter-gender');
		var $sort = $container.find('.deimlofo-sort');
		var $count = $container.find('.deimlofo-count-value');
		var $noResults = $container.find('.deimlofo-no-results');

		function apply() {
			var status = $status.val() || '';
			var gender = $gender.val() || '';
			var sort = $sort.val() || 'newest';
			var visible = [];

			$cards.each(function () {
				var $card = $(this);
				var show = true;

				if (status && String($card.data('status')) !== status) {
					show = false;
				}
				if (gender && String($card.data('gender')) !== gender) {
					show = false;
				}

				if (show) {
					$card.show();
					visible.push($card);
				} else {
					$card.hide();
				}
			});

			visible.sort(function (a, b) {
				switch (sort) {
					case 'oldest':
						return new Date(a.data('date')) - new Date(b.data('date'));
					case 'name-asc':
						return String(a.data('name') || '').localeCompare(String(b.data('name') || ''));
					case 'name-desc':
						return String(b.data('name') || '').localeCompare(String(a.data('name') || ''));
					default:
						return new Date(b.data('date')) - new Date(a.data('date'));
				}
			});

			visible.forEach(function ($card) {
				$grid.append($card);
			});

			$count.text(visible.length);

			if (visible.length === 0 && $cards.length > 0) {
				$noResults.show();
			} else {
				$noResults.hide();
			}
		}

		function reset() {
			$status.val('');
			$gender.val('');
			$sort.val('newest');
			apply();
		}

		$status.on('change', apply);
		$gender.on('change', apply);
		$sort.on('change', apply);
		$container.on('click', '.deimlofo-reset, .deimlofo-reset-trigger', reset);
	}

	/**
	 * Progressive enhancement for the Back link.
	 *
	 * The link carries a real archive URL in its href, so it still works with
	 * JavaScript disabled; history.back() is only used when there is somewhere
	 * to go back to.
	 */
	function deimlofoBackLink() {
		$(document).on('click', '.deimlofo-back', function (event) {
			if (window.history.length > 1) {
				event.preventDefault();
				window.history.back();
			}
		});
	}

	$(function () {
		$('.deimlofo-container').each(function () {
			deimlofoGrid(this);
		});
		deimlofoBackLink();
	});
})(jQuery);
