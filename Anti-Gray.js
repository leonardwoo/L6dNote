// ==UserScript==
// @name         Anti-Gray
// @namespace    http://l6d.me/
// @version      0.1
// @description  fuck gray page
// @author       LW
// @include      http://*/*
// @include      https://*/*
// @grant        none
// ==/UserScript==

(function() {
    'use strict';

    var elements = [
        document.documentElement,
        document.body
    ];
    for (var k in elements) {
        var element = elements[k]
        element.style.filter = "none"
    }
})();
