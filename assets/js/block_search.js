$(function()
{

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
	
});