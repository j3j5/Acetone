
backend default {
     .host = "127.0.0.1";
     .port = "8080";
}

acl purgers {
    "127.0.0.1";
    "localhost";
}

sub vcl_recv {
    if (req.method == "PURGE") {
        if (!client.ip ~ aclPurge) {
            return (synth(405, "This IP is not allowed to send PURGE requests. WTF dude!!"));
        }
        return (purge);
    }

    if (req.request == "BAN") {
        if (!client.ip ~ purgers) {
            return (synth(405, "This IP is not allowed to send BAN requests. WTF dude!!"));
        }

        ban("obj.http.x-url ~ " + req.http.x-ban-url);
        return (synth(200, "Ban added"));

    }

    if (req.request == "REFRESH") {
        if (!client.ip ~ purgers) {
            return (synth(405, "This IP is not allowed to send BAN requests. WTF dude!!"));
        }
        set req.request = "GET";
        set req.hash_always_miss = true;
    }
}

sub vcl_fetch {
    set beresp.http.x-url = req.url;
}
sub vcl_hit {

}
sub vcl_miss {

}
sub vcl_pass {

}

sub vcl_deliver {
    unset resp.http.x-url;

    #This is for debugging, remove for production site.
    if (obj.hits > 0) {
        set resp.http.X-Varnish-Cache = "HIT";
    }
    else {
        set resp.http.X-Varnish-Cache = "MISS";
    }
    return (deliver);
    #end debug
}

