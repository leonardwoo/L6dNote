#!/usr/bin/env python3
# -*- coding: utf-8 -*-
__author__ = 'Leonard Woo'

import requests
import os
import re

def req(url='', payload=''):
    headers = {
        'Accept': '*/*',
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache',
        'User-Agent': 'Mozilla/5.0 (X11; Linux i686; rv:102.0) Gecko/20100101 Firefox/102.0',
        'Cookie': ''
    }
    # If you don't need a proxy, delete that parameter
    ret = requests.post(url, headers=headers, data=payload)
    return ret.text


def find(regex='', input=''):
    return re.search(regex, input)


def splitFile(filepath):
    with open(filepath, 'r') as f:
        i = 1
        stories = f.readlines()
        lineNum = []
        for line in stories:
            i += 1
            if find("^第.{1,3}章", line) or find("^第.{1,3}节", line):
                lineNum = i
        #


def postChapter(content):
    FORUM_API = ""
    data = req(FORUM_API, content)


def getAllTextFile(path):
    filepaths = []
    for root, dirs, files in os.walk(path):
        for name in files:
            if '.txt' in name:
                filepaths.append(os.path.join(root, name))
    return filepaths


def main():
    fileDir = ""
    filenames = getAllTextFile(fileDir)
    for filename in filenames:
        dst = splitFile(filename)
        postChapter(dst)


if __name__ == '__main__':
    main()
