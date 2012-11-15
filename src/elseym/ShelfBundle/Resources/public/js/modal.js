define(['jquery'], function($) {

    var heading = $('<h3>').text("asdf"),
        header  = $('<div>').addClass("modal-header")
                            .append($('<button>').attr({
                                                    type: 'button',
                                                    'data-dismiss': 'modal'})
                                                 .addClass('close')
                                                 .text('x'))
                            .append(heading),
        body    = $('<div>').addClass("modal-body"),
        footer  = $('<div>').addClass("modal-footer"),
        modal   = $('<div>').addClass("modal hide fade")
                            .attr({role: 'dialog'})
                            .append(header, body, footer)
                            .modal({ show: false })
                            .appendTo($('body')),
        bar     = $('<div>').addClass("progress progress-striped active")
                            .append($('<div>').addClass("bar").width("100%"));

        modal
            .on("hidden", function() {
                heading.add(footer).empty();
                body.append(bar.clone());
            });

    var showModal = function(dataSource) {
        body.load(dataSource, {}, function() {
                var controls = $(this).find(".controls").detach(),
                    headingText = $(this).find("h3").first().remove().text();

                controls.children().each(function(i, e) {
                    if (typeof $(e).data('targetform') !== "undefined") {
                        $(e).on("click", function() {
                            $('form#' + $(e).data('targetform')).submit();
                        })
                    }
                });

                heading.text(headingText);
                footer.append(controls);
            });

        modal.modal("show");
    }

    var hideModal = function() {
        modal.modal("hide");
    }

    return {
        element: modal,
        show: showModal,
        hide: hideModal
    };
});