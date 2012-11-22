define(['jquery'],
function($) {
    var books = {};

    var templates = {
        'book': $('<article>')
            .addClass("book well")
            .attr({ id: "book-template" })
            .append($('<span>').addClass("author"))
            .append($('<span>').addClass("title"))
    };

    var
        addBook = function(book) {
            if (book.slug in books) return false;
            books[book.slug] = book;
            var bookElement = templates.book.clone();
            setBookData(bookElement, book)
                .appendTo('#shelf');
            return bookElement;
        },

        syncBook = function(slug) {
            $.getJSON("/app_dev.php/book/" + slug).success(function(book) {
                addBook(book) || setBookData("#" + book.slug, book);
            });
        },

        setBookData = function(bookElement, book) {
            books[book.slug] = book;
            return $(bookElement)
                .find(".author").text(book.author).parent()
                .find(".title").text(book.title).parent();
        };

    return {
        'books': books,
        'addBook': addBook,
        'syncBook': syncBook
    };
});