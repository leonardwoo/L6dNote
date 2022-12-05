// ==UserScript==
// @name         Disable grayscale
// @namespace    all-round
// @version      0.2
// @description  Disable css filter grayscale
// @author       Leonard Woo
// @license      MIT
// @match        *://*/*
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
