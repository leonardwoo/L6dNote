function ParseJwtPayload (token="") {
    if (token == null || token.length == 0) {
        return null;
    }

    var jwt_part = token.split('.');

    var base64 = jwt_part[1].replace(/-/g, '+').replace(/_/g, '/');
    var jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));

    return JSON.parse(jsonPayload);
}
