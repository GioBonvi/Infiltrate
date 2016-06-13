function createCookie() {
    var d = new Date();
    d.setTime(d.getTime() + (30*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = "cookies_accepted=1; " + expires;
}

function getCookie() {
    var name = "cookies_accepted=";
    var ca = document.cookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length,c.length);
        }
    }
    return "";
}

$(document).ready(function() {
    if (getCookie() != 1)
    {
        $("body").prepend('<div id="accept-cookie">' + getResource('cookie-warning') + '</div>');
        $("#accept-cookie button").click(function() {
            $("#accept-cookie").remove();
            createCookie();
        });
    }
});
