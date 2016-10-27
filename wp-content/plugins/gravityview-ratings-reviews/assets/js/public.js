/**
 * Part of GravityView_Ratings_Reviews plugin. This script is enqueued from
 * front-end view that has ratings-reviews setting enabled.
 *
 * globals jQuery, GV_RATINGS_REVIEWS, _
 */
( function( $ ){
	"use strict";

	var self = $.extend({
		'fieldClass'     : '.comment-form-gv-review,.gv-star-rate-holder,.gv-vote-rating',
		'respondId'      : '#respond',
		'respondTempId'  : '#gv-ratings-reviews-temp-respond',
		'commentParentId': '#comment_parent',
		'commentId'      : '#comment',
		'commentLabel'   : 'label[for="comment"]:first',
		'commentSubmit'  : 'input[name="submit"]:first',
		'replyTextId'    : '#reply-title',
		'cancelId'       : '#cancel-comment-reply-link'
	}, GV_RATINGS_REVIEWS);

	/**
	 * Initialization when DOM is ready.
	 */
	self.init = function() {
		self._holder = '.gv-star-rate-holder, .gv-vote-rate-holder';
		self._star = '.gv-star-rate';
		self._vote = '.gv-vote-up, .gv-vote-down';
		self._input = '.gv-star-rate-field';
		self._text = '.gv-vote-rating-text';
		self._mutated = '.gv-rate-mutated';

		self.$field = $( self._input );

		self.reply = $('.comment-reply-login, .comment-reply-link');
		self.reply.removeAttr('onclick');
		self.reply.on('click', self.moveForm);

		self.starHolder = $( self._holder );
		self.star = self.starHolder.find( self._star );
		self.star
			.on('click', self.starRateReview)
			.on('mouseover', self.mouseOverStar)
			.on('mouseout', self.mouseOutStar);


		if ( 0 !== parseInt( self.$field.val(), 10 ) ){
			self.star.eq( parseInt( self.$field.val(), 10 ) - 1 ).trigger( 'click' );
		}


		self.voteHolder = $('.gv-vote-rate-holder');
		self.vote = $('a.gv-vote-up, a.gv-vote-down', self.voteHolder);
		self.vote.on('click', self.voteRateReview);

		self.originalReplyText = self.getText($(self.replyTextId));
		self.originalCancelReplyText = self.getText($(self.cancelId));
		self.originalCommentLabelText = self.getText($(self.commentLabel));
		self.originalCommentSubmitText = $(self.commentSubmit).val();
	};

	/**
	 * Handler when user clicks comment to a review.
	 */
	self.moveForm = function(e) {
		var review = $(e.target).parent().parent(),
				respond = $(self.respondId),
				temp = $(self.respondTempId),
				parent = $(self.commentParentId),
				reviewFields = $(self.fieldClass);

		if ( ! temp.length) {
			temp = $('<div></div>');
			temp.attr('id', self.respondTempId.substr(1)); // substr to remove the hash.
			temp.hide();
			respond.parent().append(temp);
		}

		review.append(respond);
		parent.val(review.data('review_id'));

		reviewFields.hide();
		self.replaceReplyText(
			self.comment_to_review_text,
			self.cancel_comment_to_review_text,
			self.comment_label_when_reply,
			self.comment_submit_when_reply
		);

		$(self.cancelId).show().on('click', self.cancelReply);
		$(self.commentId).focus();

		return false;
	};

	/**
	 * Get the text of an element, without any children elements screwing it up
	 * @param  {[type]} el [description]
	 * @return {[type]}    [description]
	 */
	self.getText = function(el) {
		el = $(el);

		return el.clone().children().remove().end().text();
	};

	self.replaceReplyText = function(replyText, cancelText, commentLabelText, commentSubmitText) {
		var reply = $(self.replyTextId),
				cancel = $(self.cancelId),
				commentLabel = $(self.commentLabel),
				commentSubmit = $(self.commentSubmit);

		reply.contents().each(function() {
			if (this.nodeType === this.TEXT_NODE) {
				this.nodeValue = this.nodeValue.trim();
				if (this.nodeValue.length) {
					this.nodeValue = replyText;
				}
			}
		});

		cancel.text(cancelText);
		commentLabel.text(commentLabelText);
		commentSubmit.val(commentSubmitText);
	};

	/**
	 * Handler when user clicks cancel to reply.
	 */
	self.cancelReply = function(e) {
		var temp = $(self.respondTempId),
				respond = $(self.respondId),
				parent = $(self.commentParentId),
				replyText = $(self.replyTextId),
				cancel = $(e.target),
				commentLabel = $(self.commentLabel),
				commentSubmit = $(self.commentSubmit),
				reviewFields = $(self.fieldClass);

		if ( ! temp.length || ! respond.length ) return;

		parent.val(0);

		temp.parent().append(respond);
		temp.remove();

		self.replaceReplyText(
			self.originalReplyText,
			self.originalCancelReplyText,
			self.originalCommentLabelText,
			self.originalCommentSubmitText
		);

		cancel.hide();
		cancel.off('click', self.cancelReply);

		reviewFields.show();

		return false;
	};

	/**
	 * When hovering over a star rating field, process display if there's an existing rating
	 *
	 * If there's no existing rating, only CSS is used.
	 *
	 * 1. If the hovered star is the same value as the current star rating, return
	 * 2. If the hovered star is less than, regenerate the star display
	 *
	 * @param  {[type]} e [description]
	 * @return {[type]}   [description]
	 */
	self.mouseOverStar = function(e) {
		var $star = $( this ),
			$holder = $star.parents( self._holder ),
			$siblings = $holder.find( self._star ),
			index = $siblings.index( $star );

		$siblings.removeClass( 'gv-rate-mutated' ).each( function ( i, el ){
			var $el = $( el );

			// Dont fill the star if bigger then the one hovering
			if ( i >= index + 1 ){
				return;
			}

			$el.addClass( 'gv-rate-mutated' );
		} );
	};

	/**
	 * When mousing out, we always want to display the current rating.
	 * @param  {[type]} e [description]
	 * @return {void}
	 */
	self.mouseOutStar = function(e) {
		var $star = $( this ),
			$holder = $star.parents( self._holder ),
			$siblings = $holder.find( self._star ),
			index = $siblings.index( $star );

		$siblings.removeClass( 'gv-rate-mutated' ).filter( '[data-selected="1"]' ).addClass( 'gv-rate-mutated' );
	};

	/**
	 * Save star rating
	 * @param  {jQuery} e
	 * @return {boolean}   false
	 */
	self.starRateReview = function(e) {
		var $star = $( this ),
			$holder = $star.parents( self._holder ),
			$siblings = $holder.find( self._star ),
			$field = $holder.siblings( self._input ),
			index = $siblings.index( $star );

		$field.val( index + 1 );

		$siblings.removeClass( 'gv-rate-mutated' ).attr( 'data-selected', 0 ).each( function ( i, el ){
			var $el = $( el );
			// Dont fill the star if bigger then the one cliked
			if ( i >= index + 1 ){
				return;
			}

			$el.addClass( 'gv-rate-mutated' ).attr( 'data-selected', 1 );
		} );
	};

	self.voteRateReview = function(e) {
		var $vote = $( this ),
			$holder = $vote.parents( self._holder ),
			$siblings = $holder.find( self._vote ),
			$field = $holder.siblings( self._input ),
			$text = $holder.find( self._text ),
			$current = $siblings.filter( self._mutated ),

			vote = ( $vote.is( $current ) ? 0 : ( $vote.hasClass( 'gv-vote-up' ) ? 1 : -1 ) ),
			text = ( 0 === vote ? self.vote_zero : ( 1 === vote ? self.vote_up : self.vote_down ) ),
			title = ( 0 === vote ? self.vote_zero : _.template( self.vote_text_format )( { number: vote } ) );

		$siblings.removeClass( 'gv-rate-mutated' );

		if ( 0 !== vote ){
			$vote.addClass( 'gv-rate-mutated' );
		}
		$text.text( text ).attr( 'title', title );
		$field.val( vote );

		return false;
	};

	// Init!
	$(self.init);

}( jQuery, _ ) );
