#!/usr/bin/env python3
# -*- coding: utf-8 -*-
__author__ = 'Leonard Woo'

import requests
import base64
from urllib.parse import urlparse
from urllib.parse import unquote
import re

def req(url=''):
    headers = {
        'Accept': '*/*',
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache',
        'User-Agent': 'Mozilla/5.0 (X11; Linux i686; rv:91.0) Gecko/20100101 Firefox/91.0',
        'Cookie': ''
    }
    # If you don’t need a proxy, delete that parameter
    ret = requests.get(url, headers=headers)
    return ret.text


def find(regex='', input=''):
    return re.search(regex, input)


def main():
    SUBSCRIBE = ""
    data = req(SUBSCRIBE)
    data = base64.b64decode(data).decode('utf8')
    sb = []

    for line in data.splitlines():
        urlobj = urlparse(line)
        lineName = unquote(urlobj.fragment)
        lastBracket = lineName.rfind("[")
        if lastBracket <= 0 :
            lastBracket = len(lineName)
        lineName = lineName[0:lastBracket]
        authInfo = urlobj.username
        authInfo = base64.b64decode(authInfo).decode('utf8')
        host = urlobj.hostname
        if find("^$", lineName) or find("", lineName) or host.lower() == "":
            continue
        
        lineName = "✈️" + lineName
        ss = "ss://{0}@{1}#{2}".format(authInfo, host, lineName)
        sb.append(base64.b64encode(ss.encode('utf8')).decode('utf8'))
        sb.append('\n')
    
    sub = ''.join(sb)
    sub = base64.b64encode(sub.encode('utf8')).decode('utf8')
    with open("ss-m.txt", "w+") as file:
        file.write(sub)


if __name__ == '__main__':
    main()
