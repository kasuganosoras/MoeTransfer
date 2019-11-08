#!/bin/bash
API="http://t.mcr.moe:812"
# 注释掉上面，改为下面这个即可使用 https 加密传输
# API="https://t.mcr.moe"
cat $2 | base64 | curl -s "${API}/send/$1" -d @- > /dev/null
curl -s "${API}/close/$1" > /dev/null
