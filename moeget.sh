#!/bin/bash
API="http://t.mcr.moe:812"
# 注释掉上面，改为下面这个即可使用 https 加密传输
# API="https://t.mcr.moe"
curl -s "${API}/get" | base64 -d
# 可选，解决缺少换行问题
# echo ""
