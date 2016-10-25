jQuery(function ($) {

	function positionProduct(e) {

		var winHeight = window.innerHeight;
		var headerHeight = $('.site-header').outerHeight();
		var footerTop = $('.footer-widgets').position().top;
		var headerTop = $('.site-header').position().top;
		var paddingTop = parseInt($('.images').css('padding-top'));
		var wcMessage = $('.woocommerce-message').outerHeight();
		var wpAdminBar = $('#wpadminbar').outerHeight();

		var scrollHeight = headerHeight + wcMessage + wpAdminBar;

		var leftHeight = winHeight - headerHeight;

		var currScroll = $(window).scrollTop();

		var imageHeight = leftHeight - paddingTop;

		if (window.matchMedia("(min-width: 1024px)").matches) {

			//Set Media Query
			//if (Modernizr.mq('only screen and (min-width: 769px)')) {

			//set left height
			$('.images').height(leftHeight);

			//set image height
			$('.product-img-box').height(imageHeight);

			//check if fixed position or not
			if (footerTop < currScroll + winHeight - scrollHeight) {
				$('.images').css({
					'position': 'absolute',
					'top': footerTop - leftHeight - scrollHeight - 15
				});
			} else {
				$('.images').css({
					'position': 'fixed',
					'top': scrollHeight > currScroll ? scrollHeight - currScroll : 0
				});
			}
		} else {

			//Remove styling for mobile
			$('.images').attr('style', '');
			$('.product-img-box').attr('style', '');
		}
	}

	$(window).on('resize', positionProduct);

	$(window).load(function () {
		setTimeout(positionProduct, 200);
	});

	$(window).on('scroll', function (e) {
		positionProduct(e);
	});
});
//# sourceMappingURL=scrolltofixed-init.js.map