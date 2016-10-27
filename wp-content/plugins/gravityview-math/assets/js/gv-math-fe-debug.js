/**
 * Additional debugging functionality
 *
 * @package   GravityView Math
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.1.0
 *
 * globals jQuery
 */
jQuery(document).ready(function ($) {
    $('.gv-math-debug-more').on( 'click', function (e) {
        e.preventDefault();
        $( this ).closest( '.gv-math-debug-msg' ).toggle();
    });
});
