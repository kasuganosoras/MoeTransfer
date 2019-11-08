#!/bin/bash
API="http://t.mcr.moe:812"
base64 | curl -s "${API}/send/$1" > /dev/null -d @-
curl -s "${API}/close/$1" > /dev/null
