(function (protocol, hostname, ioport, iobase) {
    requirejs.config({
        config: {
            'agathe': {
                'protocol': protocol,
                'hostname': hostname,
                'ioport': ioport,
                'iobase': iobase = (protocol + "//" + hostname + ":" + ioport)
            }
        },
        paths: {
            'socket': iobase + '/socket.io/socket.io',
            'agathe': '../../elseymagathe/js/agathe'
        }
    });
})(location.protocol, location.hostname, 8081);

require(['jquery', 'modal', 'agathe'],
function( $,        modal,   ä) {


    $('#nav-addbook')
        .on("click", function() {
            modal.show("/app_dev.php/book/new");
        });

    $('article.book')
        .on("click", function() {
            modal.show("/app_dev.php/book/" + $(this).attr('id') + "/show");
        });

    $(document)
        .on("submit", ".modal form", function(event) {
            $(event.target).find("input").each(function(i, element) {console.log(element.name, element.value)});
            var action = $(this).attr("action"),
                method = $(this).attr("method").toLowerCase();

            if (method === "post") {
                $.post(action, $(this).serialize());
            } else if (method === "get") {
                $.get(action, $(this).serialize());
            }

            modal.hide();
            return false;
        });

    ä.namespace('/book')
        .on("created", function() {
            console.log("created:", arguments);
        })
        .on("deleted", function() {
            console.log("deleted:", arguments);
        })
        .on("modified", function() {
            console.log("modified:", arguments);
        })
        .on("requested", function() {
            console.log("requested:", arguments);
        });
});