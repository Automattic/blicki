jQuery(function( $ ) {
	$( 'div.post-wrapper' ).each( function() {
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

		var list = $( 'ol.blicki__toc', post_div );
		headings.forEach( function( elem ) {
			var listItem = $('<li>').addClass('toc-level' + elem.level);
			var link = $('<a>').attr('href', elem.target).text(elem.text);

			listItem.append(link);
			list.append(listItem);
		});
	});
});
