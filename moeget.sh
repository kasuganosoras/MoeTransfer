#!/bin/bash
API="http://t.mcr.moe:812"
curl -s "${API}/get" | base64 -d
# 可选，解决缺少换行问题
# echo ""
