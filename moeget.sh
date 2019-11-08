#!/bin/bash
API="http://t.mcr.moe:812"
curl -s "${API}/get" | base64 -d
