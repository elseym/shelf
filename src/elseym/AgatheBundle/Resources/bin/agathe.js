#!/usr/bin/env node

String.prototype.uriToKey = function() { return this.replace(/\//, '').replace(/\//g, ':'); }
String.prototype.keyToUri = function() { return '/' + this.replace(/\:/g, '/'); }
String.prototype.keyGetParent = function() { return this.split(":").slice(0, -1).join(":"); }
String.prototype.SIDFromCookieString = function() { return (this.match(/PHPSESSID=([^\;]+);?/i) || [,""])[1]; }

var DATABASE = 0,
    io = require("socket.io").listen(8081),
    redis = require("redis"),
    rd = redis.createClient(),  // redis data
    rc = redis.createClient();  // redis pubsub

if (0 !== DATABASE) rd.select(DATABASE, function() { /* ... */ });

rc
    .on("pmessage", function(pattern, evt, data) {
        var evtParams = evt.match(/e:(ctrl|data):(.+)/);
        if (evtParams == null) {
            console.error("stupid inbound message via redis pubsub.");
        } else {
            if (evtParams[1] == "data") {
                handleDataEvent(evtParams[2], data);
            } else if (evtParams[1] == "ctrl") {
                handleControlEvent(evtParams[2], data);
            }
        }
    })
    .psubscribe("e:*");

function handleControlEvent(evt, data) {
    var evtParams = evt.match(/([^:]+)+/g);
    switch (evtParams[0]) {
        case "client":
            switch (evtParams[1]) {
                case "new": registerNamespaces(data); break;
                console.log("NEW CLIENT!");
//                case "modified": checkNamespaces(data); break;
//                case "removed": registerNamespaces(data); break;
                default:
            }
            break;
        case "setup-complete":
            break;
        case "general":
        default:
    }
}

function handleDataEvent(evt, key) {
    console.log("data-event:", arguments);
    rd.hgetall(key, function(err, res) {
        console.log("redis-data:", arguments);
        var myns = key.keyGetParent().keyToUri();
        io.of(myns).emit(evt, res);
    });
}

function registerNamespaces(sid) {
    rd.smembers(sid, function(err, res) {
        for (var i = 0; i < res.length; ++i) {
            if (res[i] in io.namespaces) continue;
            setupNamespace(res[i]);
        }
        rd.get("client-waiting:" + sid, function(err, res) {
            if (res) notifyConnect(res);
        });
        rd.sadd("setup-complete", sid);
        rd.del(sid); // let php know that the namespaces are set up.
    });
}

function setupNamespace(ns) {
    io
        .of(ns)
        .on("connection", function(sock) {
            console.log("new connection to " + ns + "/\n\t" + sock.id + "\n\tsid:" + SIDFromHandshakeData(sock.handshake));
        })
        .authorization(function(hd, cb) {
            console.log("step in: authorization", arguments);
            var sid = SIDFromHandshakeData(hd);
            rd.sismember("ns:" + ns.uriToKey(), sid, function(err, res) {
                console.log("sismember", arguments);
                return cb(null, false);
            });
        });
}

function notifyConnect(sockid) {
    var mysock = io.sockets.sockets[sockid];
    if (typeof mysock != "undefined") {
        mysock.emit(sockid + "-setup-complete");
    }
}

function SIDFromHandshakeData(hd) {
    if (hd && hd.headers && hd.headers.cookie) {
        return (hd.headers.cookie.match(/PHPSESSID=([^\;]+);?/i) || [,""])[1];
    } else return "";
}

io
    .on("connection", function(sock) {
        var sid = SIDFromHandshakeData(sock.handshake);
        //console.log("new connection to /:\n\tsockid:" + sock.id + "\n\tsid:" + sid);
        rd.srem("setup-complete", sid, function(err, res) {
            if (res >= 1) notifyConnect(sock.id);
        });
    });
