(function($){
	$.fn.extend({
		disableSelection: function() {
			this.each(function() {
				this.onselectstart = function() {
				    return false;
				};
				this.unselectable = "on";
				$(this).css('-moz-user-select', 'none');
				$(this).css('-webkit-user-select', 'none');
				$(this).css('-ms-user-select', 'none');
				$(this).css('user-select', 'none');
			});
			return this;
		}
	});

	$(document).on( 'mouseenter', 'div.user-reactions-button', function(e){
		$(this).addClass('reaction-show');
	});

	$(document).on('mouseleave', 'div.user-reactions-button', function(e){
		$(this).removeClass('reaction-show');
	});

	$(document).on('taphold','div.user-reactions-button',function(e){
		e.preventDefault();
		$(this).addClass('reaction-show');
		$(this).disableSelection();
	});

	$('div.user-reactions-button').disableSelection();

	$(document).on('click', '.user-reaction', function(e){
		e.preventDefault();

		var t = $(this), $class = t.attr('class'), main = t.parent().parent().parent(), vote_type = main.attr('data-type'), voted = main.attr('data-vote'), text = t.find('strong').text();
		
		res = $class.split(' ');
		type = res[1].split('-');

		$('div.user-reactions-button').removeClass('reaction-show');

		$.ajax({
			url: user_reaction.ajax,
			dataType: 'json',
			type: 'POST',
			data: {
				action: 'user_reaction_save_action',
				nonce: main.data('nonce'),
				type: type[2],
				post: main.data('post'),
				voted: voted
			},
			success: function(data) {
				if ( data.success ) {
					$('.user-reactions-post-'+main.data('post')).find('.user-reactions-count').html(data.data.html);
					$('.user-reactions-post-'+main.data('post')).find('.user-reactions-main-button').attr('class','user-reactions-main-button').addClass('user_reaction_'+type[2]).text(text);
					main.parent().find('.user-reactions').attr('data-type', 'unvote');
					main.parent().find('.user-reactions').attr('data-vote', 'yes');
				}
			}
		});
	});

	$(document).on('click','.user-reactions-main-button', function(e) {
		e.preventDefault();

		var t = $(this), parent = t.parent().parent();
		type = parent.attr('data-type');
		text = t.parent().find('.user-reaction-like strong').text();

		$.ajax({
			url: user_reaction.ajax,
			dataType: 'json',
			type: 'POST',
			data: {
				action: 'user_reaction_save_action',
				nonce: parent.data('nonce'),
				type: 'like',
				post: parent.data('post'),
				vote_type: type,
				voted: parent.attr('data-voted')
			},
			success: function(data) {
				if ( data.success ) {
					if ( data.data.type == 'unvoted' ) {
						$('.user-reactions-post-'+parent.data('post')).find('.user-reactions-main-button').attr('class', 'user-reactions-main-button').text(text);
						parent.attr('data-type', 'vote');
					} else {
						$('.user-reactions-post-'+parent.data('post')).find('.user-reactions-main-button').addClass('user_reaction_like');
						parent.attr('data-type', 'unvote');
					}
					$('.user-reactions-post-'+parent.data('post')).find('.user-reactions-count').html(data.data.html);
				}
			}
		});
	})
})(jQuery);