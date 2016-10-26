(function (document, $) {

	'use strict';

	/* global PrimaryBackstretchHero, SecondaryBackstretchHero */
	let primaryBsHero = PrimaryBackstretchHero;
	let secondaryBsHero = SecondaryBackstretchHero;

    $('.primary-hero').backstretch(primaryBsHero.primary_hero);

    $('.secondary-hero').backstretch(secondaryBsHero.secondary_hero);

})(document, jQuery);
