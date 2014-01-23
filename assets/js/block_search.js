$(function()
{

	//Localscroll plugin (scrolls nicely when jumping to a section)
	$.localScroll({duration: 200, hash: true, offset: -35 });

	//Make the box on the left follow as you scroll
	if ($('#resultsNav').length > 0) {
		var scrollFixedAfter = $('#resultsNav').offset().top - 35;
		
		$('#resultsNav').css('width', $('#resultsNav').width());
	
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