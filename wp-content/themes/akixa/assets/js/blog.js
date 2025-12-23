$(document).ready(function() {
    $('body').on('click', '.pagination .btn-paginate', function() {
        let btn = $(this);
        let action = btn.attr('data-action');
        let pagination = btn.parent().find('.list-page');
        let currentPage = pagination.find('.item.active').attr('data-page');
        let url = btn.parent().attr('data-url');
        let excludeIds = btn.parent().attr('data-ids');
        let nextPage;
        let btnHtml = btn.html();

        if (action == 'prev') {
            if (currentPage == 1) return;
            nextPage = Number(currentPage) - 1;
        }
        else {
            if (pagination.find('.item.active').hasClass('last')) return;
            nextPage = Number(currentPage) + 1;
        }

        btn.attr('disabled', true);
		btn.html('<i class="fas fa-spinner fa-pulse"></i>');

        $.ajax({
			url: url,
			type: "GET",
			data: {isAjax: 1, nextPage, excludeIds},
			success: function(rs) {
                btn.attr('disabled', false);
				btn.html(btnHtml);

				if (rs.success) {
                    $('.list-news .list').html(rs.data);
                    pagination.find('.item').removeClass('active');
                    pagination.find(`.item[data-page="${nextPage}"]`).addClass('active');
				}
			}
		});
    });

    $('body').on('submit', '.form-contact form', function(e) {
        e.preventDefault();
        saveCustomForm($(this));

        return false;
    });
});