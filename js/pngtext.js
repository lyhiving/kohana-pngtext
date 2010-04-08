/**
 * PNGText
 *
 * @package    PNGText Kohana Module
 * @author     John Heathco <jheathco@gmail.com>
 */
jQuery.fn.pngtext = function(font, hover)
{
	// PNGText server URL based upon this javascript src URL
	var url = $('#pngtext').attr('src').replace('pngtext.js', '');

	return this.each(function()
	{
		// CSS styles supported
		var params = {
			'text':        $(this).html(),
			'font-family': font,
			'font-size':   $(this).css('font-size'),
			'color':       $(this).css('color'),
			'line-height': $(this).css('line-height'),
			'text-align':  $(this).css('text-align'),
			'width':       $(this).width(),
		};

		if (hover)
		{
			// Changed styles supported for hover
			$(this).addClass(hover);
			params['hover-color'] = $(this).css('color');
			$(this).removeClass(hover);
		}

		var src = url+'?';

		for (param in params)
		{
			// Determine URL
			src += param+'='+escape(params[param])+'&';
		}

		// Create the image tag
		var img = $('<img>');

		// Store source element
		var source = $(this);

		if (hover)
		{
			// Using hover, so we need to be tricky and use a div tag with background-style property
			var tag = $('<div>');

			img.load(function(){
				// Resize div based upon image dimensions
				var width = $(this).attr('width');
				var height = $(this).attr('height') / 2;
				
				tag.css('width', width);
				tag.css('height', height);

				tag.css('backgroundColor', 'transparent');
				tag.css('backgroundImage', 'url('+src+')');

				// Set the hover style to adjust background position accordingly
				tag.parent().hover(function(){ tag.css('backgroundPosition', '0 -'+height+'px'); }, function(){ tag.css('backgroundPosition', '0 0'); });

				source.html(tag);
			});					
		}
		else
		{
			img.load(function(){
				// No hover, so just set the HTML to the image tag
				source.html(img);
			});
		}

		// Load the image... once it's loaded, the replacements will occur
		img.attr('src', src);
	});
};