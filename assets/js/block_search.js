$(function()
{

	//Localscroll plugin (scrolls nicely when jumping to a section)
	$.localScroll({duration: 200, hash: true, offset: -60 });

	//Make the box on the left follow as you scroll
	if ($('#resultsNav').length > 0) {
		var scrollFixedAfter = $('#resultsNav').offset().top - 60;

		$(document).on('scroll', function()
		{
			if (window.scrollY >= scrollFixedAfter) {
				$('#resultsNav').addClass('fixed');
			} else {
				$('#resultsNav').removeClass('fixed');
			}
		});
	}

});
