define(['module', 'socket'],
function(module,   io) {
    function Agathe(iobase) {
        this.iobase = iobase || module.config().iobase;
        this.changeStatus("connecting", "/");

        var that = this;
        io.connect(this.iobase).on("connect", function() {
            that.changeStatus("connected", this.socket.sessionid);
            this.on(this.socket.sessionid + "-setup-complete", function() {
                that.changeStatus("setup-complete", this.socket.sessionid);
                that.ready = true;
                that.connectAll();
            });
        });
    }

    Agathe.prototype = {
        namespaces: {},
        ready: false,
        statusCallbacks: [],
        onStatusChange: function(cb) {
            this.statusCallbacks.push(cb);
            return this;
        },
        of: function(namespace) {
            if (!(namespace in this.namespaces)) {
                this.changeStatus("new-namespace", namespace);
                this.namespaces[namespace] = new AgatheNS(namespace, this);
            }
            return this.namespaces[namespace];
        },
        connectAll: function() {
            if (!this.ready) return false;
            var success = true;
            for (var i in this.namespaces) {
                if (!this.namespaces.hasOwnProperty(i)
                    || typeof this.namespaces[i] !== "object") continue;
                if (!this.namespaces[i].connected) {
                    this.changeStatus("connecting", this.namespaces[i]);
                    success = success && this.namespaces[i].connect();
                }
            }
            return success;
        },
        changeStatus: function() {
            console.log(arguments);
            for (var i in this.statusCallbacks) {
                if (!this.statusCallbacks.hasOwnProperty(i)
                    || typeof this.statusCallbacks[i] !== "function") continue;
                this.statusCallbacks[i].apply(arguments);
            }
        }
    };

    function AgatheNS(namespace, parent) {
        if (!(namespace.match(/^\//))) throw new Error("Tried to subscribe to malformed namespace '" + namespace + "'.");
        this.name = namespace;
        this.parent = parent;
        this.uri = this.parent.iobase + namespace;
    }

    AgatheNS.prototype = {
        name: '',
        socket: null,
        connected: false,
        uri: '',
        parent: null,
        callbacks: {
            "requested": [],
            "created": [],
            "modified": [],
            "deleted": []
        },
        on: function(verb, callback) {
            if (verb in this.callbacks) {
                if (this.connected) {
                    this.socket.on(verb, callback);
                } else {
                    this.callbacks[verb].push(callback);
                }
            }
            return this;
        },
        checkVerb: function(verb) {
            if (!(verb.match(/((crea|dele|reques)t|modifi)ed/))) throw new Error("Tried to listen to unsupported verb '" + verb + "'.");
        },
        connect: function(uri) {
            if (!this.parent.ready) return false;
            this.socket = io.connect(uri || this.uri);
            var success = this.connected || (this.connected = (!!(this.socket)));
            if (success) {
                for (var verb in this.callbacks) {
                    if (!this.callbacks.hasOwnProperty(verb)) continue;
                    var cb;
                    while (cb = this.callbacks[verb].pop()) {
                        this.socket.on(verb, cb);
                    }
                }
            }
            return success;
        }
    };

    return new Agathe();
});