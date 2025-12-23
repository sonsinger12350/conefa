$(document).ready(function () {
	$('.why-choose .services .tab .item').on('click', function() {
        if ($(this).hasClass('active')) return false;
        let tab = $(this).attr('data-tab');

        $('.why-choose .services .tab .item').removeClass('active');
        $('.why-choose .services .tab-content .item').removeClass('active');

        $(this).addClass('active');
        $(`.why-choose .services .tab-content .${tab}`).addClass('active');
    });
});