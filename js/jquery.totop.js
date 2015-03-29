(function ($) {
    $.fn.totop = function () {
        var totopBtn = this;
        totopBtn.hide();
        $(document).on('scroll.totop', function () {
            var y = $(document).scrollTop();
            if (y > 200) {
                totopBtn.fadeIn();
                totopBtn.data('prev-y', null);
            }
            else {
                if (!totopBtn.data('prev-y')) {
                    totopBtn.fadeOut();
                }
            }
        });

        totopBtn.on('mouseover.totop', function () {
            totopBtn.addClass('active');
        });

        totopBtn.on('mouseout.totop', function () {
            totopBtn.removeClass('active');
        });

        totopBtn.on('click.totop', function () {
            if (!totopBtn.data('prev-y')) {
                var y = $(document).scrollTop();
                totopBtn.data('prev-y', y);
                window.scrollTo(0, 0);
            }
            else {
                window.scrollTo(0, totopBtn.data('prev-y'));
            }
        });
    };
})(jQuery);