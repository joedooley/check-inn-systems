(function (document, $) {

	'use strict';

	/* global PrimaryBackstretchHero, SecondaryBackstretchHero */
	let primaryBsHero = PrimaryBackstretchHero;
	let secondaryBsHero = SecondaryBackstretchHero;

	if (primaryBsHero)
		$('.primary-hero').backstretch(primaryBsHero.primary_hero);

	if (secondaryBsHero)
		$('.secondary-hero').backstretch(secondaryBsHero.secondary_hero);

})(document, jQuery);
