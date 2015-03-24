(function ($) {
    function notify(message, options) {

        var options = $.extend({
            type: 'default',
            delay: 5000,
            speed: 500,
            position: 'top-left',
        }, options);

        var position = options.position;

        // Get or create notify-wr element
        if (position != 'top-left' && position != 'top-right' && position != 'bottom-left' && position != 'bottom-right') {
            position = 'top-left';
        }

        var $notifyWr = $('#notify-wr-' + position);
        if (!$notifyWr.size()) {
            $notifyWr = $('<div id="notify-wr-' + position + '" class="notify-wr" ></div>');
            setPositionOfNotifyWr($notifyWr, position);
            $notifyWr.css({
                position: 'fixed',
            });
            $('body').append($notifyWr);
        }

        // Add notify message
        var $notify = $('<div class="notify"><div class="notify-message"></div><div class="notify-close"></div></div>');
        if (options.type) {
            $notify.addClass('notify-' + options.type);
        }
        $notify.hide();
        if (position == 'top-left' || position == 'top-right') {
            $notifyWr.prepend($notify);
        }
        if (position == 'bottom-left' || position == 'bottom-right') {
            $notifyWr.append($notify);
        }
        $notify.children('.notify-message').html(message);
        $notify.children('.notify-close').on('click', function () {
            $(this).parent().fadeOut(options.fast);
        });
        $notify.fadeIn(options.speed);

        var timeout = setTimeout(function () {
            $notify.fadeOut(options.speed, function () {
                $notify.remove();
            });
        }, options.delay);

    }

    $.extend({ notify: notify });

    function setPositionOfNotifyWr($element, position) {
        switch (position) {
            case 'top-left' :
                $element.css({
                    top: 0,
                    left: 0,
                });
                break;
            case 'top-right' :
                $element.css({
                    top: 0,
                    right: 0,
                });
                break;
            case 'bottom-left' :
                $element.css({
                    bottom: 0,
                    left: 0,
                });
                break;
            case 'bottom-right' :
                $element.css({
                    bottom: 0,
                    right: 0,
                });
                break;
        }
    }
})(jQuery);