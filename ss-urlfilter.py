#!/usr/bin/env python3
# -*- coding: utf-8 -*-
__author__ = 'Leonard Woo'

import requests
import base64
from urllib.parse import urlparse
from urllib.parse import unquote
from urllib.parse import quote
import re
import os

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


def subscribe_filter(data=''):
    data = base64.b64decode(data.encode('utf8')).decode('utf8')
    sb = []
    for line in data.splitlines():
        urlobj = urlparse(line)
        lineName = unquote(urlobj.fragment)
       # lastBracket = lineName.rfind("[")
       # if lastBracket <= 0 :
       #     lastBracket = len(lineName)
       # lineName = lineName[0:lastBracket]
        authInfo = urlobj.username
        # authInfo = base64.b64decode(authInfo).decode('utf8')
        host = urlobj.hostname + ":" + str(urlobj.port)
        if find("^", lineName) or find("", host):
            continue
        
        lineName = "✈️" + lineName
        # lineName = quote(lineName)
        ss = "ss://{0}@{1}#{2}".format(authInfo, host, lineName)
        sb.append(ss)
        sb.append('\n')
    
    sub = ''.join(sb)
    return base64.b64encode(sub.encode('utf8')).decode('utf8')


def main():
    SUBSCRIBE = ""
    FILE_PATHNAME = "ss-m.txt"
    data = req(SUBSCRIBE)
    data = subscribe_filter(data)
    if os.path.exists(FILE_PATHNAME):
        os.remove(FILE_PATHNAME)
    
    with open(FILE_PATHNAME, "w+") as file:
        file.write(data)


if __name__ == '__main__':
    main()
