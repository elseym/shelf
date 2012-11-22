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
function( $,        modal,   agathe) {

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

    function getPayload(e) {
        if (e['meta:hasPayload']) {
            return JSON.parse(e.payload);
        }
        return false;
    }

    function getSlug(e) {
        if (e['meta:hasPayload']) {
            return JSON.parse(e.payload).slug;
        }
        return false;
    }

    function addToRealTimeFeed(desc, data) {
        $(data).each(function(i, e) {
            var bookTitle = "a book", payload = getPayload(e);
            if (payload) bookTitle = "'" + payload.title + "'";
            $('footer#realtime-feed i.icon-info-sign')
                .after($("<span>").addClass("rtf-item").text(desc.replace(/\{title\}/g, bookTitle)));
        });
    }

    function Book() {
        this.slug = arguments[0];

        if (arguments.length == 3) {
            this.e = $('<article>')
                .addClass("book well")
                .attr({ id: this.slug })
                .append($('<span>').addClass("author").text(arguments[1]))
                .append($('<span>').addClass("title").text(arguments[2]))
        } else {
            this.e = $('#' + this.slug);
        }
    }

    Book.prototype = {
        e: null,
        slug: "",
        highlight: function() {
            $(this.e).effect("highlight");
        },
        remove: function() {
            $(this.e).hide(1500);
        },
        shelve: function() {
            $('#books').append($(this.e));
        }
    }

    function highlightBook(data) {
        $(data).each(function(i, e) {
            var payload = getPayload(e);
            var book = new Book(payload.slug);
            book.highlight();
        });
    }

    function removeBook(data) {
        $(data).each(function(i, e) {
            var payload = getPayload(e);
            var book = new Book(payload.slug);
            book.remove();
        });
    }

    function addBook(data) {
        $(data).each(function(i, e) {
            $.getJSON("/app_dev.php/book/" + getSlug(e) + "/show").success(function(book) {
                var book = new Book(book.slug, book.author, book.title);
                book.shelve();
                book.highlight();
            });
        });
    }

    agathe
    .onStatusChange(function() {
        console.log("agathe status change: ", arguments, "\n===============\n");
    })
    .of('/book')
        .on("created", function(data) {
            addBook(data);
            addToRealTimeFeed("someone added {title} :D", data);
        })
        .on("deleted", function(data) {
            removeBook(data);
            addToRealTimeFeed("someone deleted {title} :'(", data);
        })
        .on("modified", function(data) {
            addToRealTimeFeed("someone modified {title} o.ó", data);
        })
        .on("requested", function(data) {
            highlightBook(data);
            addToRealTimeFeed("someone requested {title} -_-", data);
        });
//    .parent
//    .of('/magazine')
//        .on("created", function(data) {
//            // nothing
//        });
});