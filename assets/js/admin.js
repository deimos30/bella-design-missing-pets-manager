/**
 * Deimos Lost & Found Animals - Admin JS
 *
 * @package   Deimos_Lost_Found_Animals
 * @author    Wojtek Kobylecki
 * @copyright Copyright (c) 2026 Wojtek Kobylecki
 * @license   GPL-2.0-or-later
 */

(function ($) {
	'use strict';

	$(function () {
		var $pickers = $('.deimlofo-color-picker');

		if ($pickers.length && $.fn.wpColorPicker) {
			$pickers.wpColorPicker();
		}
	});
})(jQuery);
