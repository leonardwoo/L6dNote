// ==UserScript==
// @name         Disable grayscale
// @namespace    all-round
// @version      0.4
// @description  Disable css filter grayscale
// @author       Leonard Woo
// @license      MIT
// @match        *://*/*
// @grant        none
// ==/UserScript==

(function() {
    'use strict';

    var css = [
	"* {",
	"    -webkit-filter: grayscale(0);",
	"    -moz-filter: grayscale(0);",
	"    -ms-filter: grayscale(0);",
	"    -o-filter: grayscale(0);",
	"    filter: grayscale(0);",
	"}",
].join("\n");
 
    var node = document.createElement("style");
	node.type = "text/css";
	node.appendChild(document.createTextNode(css));
	document.head.appendChild(node);
})();
