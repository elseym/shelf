define(['module', 'socket'],
function(module,   io) {

    var namespaces = {},
        iobase = module.config().iobase;

    function Äns(namespace) {
        if (!(namespace.match(/^\//))) throw new Error("Tried to subscribe to malformed namespace '" + namespace + "'.");
        this.namespace = namespace;
        this.sock = io.connect(iobase + namespace);
    }

    Äns.prototype = {
        namespace: '',
        sock: null,
        on: function(verb, callback) {
            this.checkVerb(verb);
            this.sock.on(verb, callback);
            return this;
        },
        off: function(verb, callback) {
            this.checkVerb(verb);
            this.sock.removeListener(verb, callback);
            return this;
        },
        checkVerb: function(verb) {
            if (!(verb.match(/((crea|dele|reques)t|modifi)ed/))) throw new Error("Tried to listen to unsupported verb '" + verb + "'.");
        }
    };

    var getÄns = function(namespace) {
        if (!(namespace in namespaces)) {
            namespaces[namespace] = new Äns(namespace);
        }
        return namespaces[namespace];
    };

    return {
        namespace: getÄns
    };
});