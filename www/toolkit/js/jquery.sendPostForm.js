jQuery.extend({
	sendPostForm: function(form, element, callback) {
		var url = jQuery(form).attr('action');
		var params = jQuery(form).serializeArray();
		jQuery(element).load(url, params, callback);
		return true;
	},
	
	sendPost: function(form, element, link, callback, selector) {
		if (typeof link != 'string') {
			url = jQuery(link).attr('action');
			if (!url)
				url = jQuery(link).attr('href');
			if (!url)
				throw "UndefinedLinkObject";
			link = url;
		}
		
		form = jQuery(form);
		var next = form.next();
		var parent = form.parent();
		
		if (form) {
			var sendForm = jQuery('<form method="post"/>')
				.append(jQuery(form))
				.attr('action', link);

			var params = jQuery(sendForm).serializeArray();
			next.length ? next.before(sendForm.children()) : parent.append(sendForm.children());
			sendForm.remove();
		} else {
			var params = null;
		}
		element = jQuery(element);
		
		jQuery.ajax({
			url: link,
			type: 'POST',
			dataType: "html",
			cache: false,
			data: params,
			complete: function( jqXHR, status, responseText ) {
				responseText = jqXHR.responseText;
				if ( jqXHR.isResolved() ) {
					jqXHR.done(function( r ) {
						responseText = r;
					});
					// See if a selector was specified
					element.html( selector ?
						jQuery("<div>")
							.append(responseText)
							.find(selector) :

						responseText );
					
					if ( callback ) {
						element.each( callback, [ responseText, status, jqXHR ] );
					}
				}

			}
		});
		return true;
	}
});


