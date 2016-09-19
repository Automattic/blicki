jQuery(function( $ ) {

	$('.blicki__actions-history').on( 'click', function( e ) {
		e.preventDefault();
		$( '.blicky-edit' ).hide();
		$( '.blicky-history' ).slideToggle( 150 );
	} );
	$('.blicki__actions-edit, .blicki__edit-cancel').on( 'click', function( e ) {
		e.preventDefault();
		$( '.blicky-history' ).hide();
		$( '.blicky-edit' ).slideToggle( 150 );
	} );
	$('.blicki__edit-cancel').on( 'click', function( e ) {
		e.preventDefault();
		$( '.blicky-edit' ).slideUp();
	} );
	$( document ).on( 'click', '.blicki__toc-toggle', function( e ) {
		e.preventDefault();
		var toggle = $(this),
				tocList = toggle.siblings( '.blicki__toc' );
		if ( tocList.is( ':visible' ) ) {
			toggle.text( 'Show' );
			tocList.slideUp( 150 );
		} else {
			toggle.text( 'Hide' );
			tocList.slideDown( 150 );
		}
	} );

	$( '.blicky-entry-content' ).each( function() {
		var post_div = $( this );
		var headings = [];
		var prefix = post_div.attr( 'id' )
		$( 'h1, h2, h3, h4, h5, h6', post_div ).each( function( index ) {
			switch($(this).prop('nodeName').toLowerCase()) {
				case 'h1':
					headings.push({ level: 1, text: $(this).text(), target: '#' + prefix + index });
					break;
				case 'h2':
					headings.push({ level: 2, text: $(this).text(), target: '#' + prefix + index });
					break;
				case 'h3':
					headings.push({ level: 3, text: $(this).text(), target: '#' + prefix + index });
					break;
				case 'h4':
					headings.push({ level: 4, text: $(this).text(), target: '#' + prefix + index });
					break;
				case 'h5':
					headings.push({ level: 5, text: $(this).text(), target: '#' + prefix + index });
					break;
				case 'h6':
					headings.push({ level: 6, text: $(this).text(), target: '#' + prefix + index });
					break;
			}
			$(this).attr('id', prefix + index);
		});

		if ( ! headings.length ) {
			return;
		}

		var toc_div = $( '<div class="blicki__toc-container"><strong>' + blicki_js_params.toc + '</strong></div>' );
		var tocToggle = $( '<a href="javascript:;" class="blicki__toc-toggle">Show</a>' );
		var list = $( '<ol class="blicki__toc"></ol></div>' );
		headings.forEach( function( elem ) {
			var listItem = $('<li>').addClass('toc-level' + elem.level);
			var link = $('<a>').attr('href', elem.target).text(elem.text);

			listItem.append(link);
			list.append(listItem);
		});
		var contributorsLink = $( '<li><a href="#bcontributors">Contributors</a></li>');
		list.append( contributorsLink );
		toc_div.append( tocToggle, list );
		post_div.prepend( toc_div )
	});
});
