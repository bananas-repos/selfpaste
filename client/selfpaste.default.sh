#!/usr/bin/env bash

# This program is free software: you can redistribute it and/or modify
# it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
# You should have received a copy of the
# COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
# along with this program.  If not, see http://www.sun.com/cddl/cddl.html
#
# 2019 - 2020 https://://www.bananas-playground.net/projekt/selfpaste

command -v curl >/dev/null 2>&1 || { echo >&2 "I require curl (https://curl.haxx.se/) but it's not installed.  Aborting."; exit 1; }
command -v jq >/dev/null 2>&1 || { echo >&2 "I require jq (https://stedolan.github.io/jq/) but it's not installed.  Aborting."; exit 1; }

if [ $# -lt 1 ]; then
	echo "You need to provide a file to paste";
	echo "selfpaste.sh /path/to/file";
	exit 2;
fi;

ENDPOINT="http://your.tld/selfpaste/webroot/";
SELFPASTE_UPLOAD_SECRET="PLEASE CHANGE YOUR SECRET TO SOMTHING";

FILENAME="$1";

if [[ -r $FILENAME  ]]; then
  # add --verbose if you need some more information
  RESPONSE=$(curl -sS --header "Content-Type:multipart/form-data" --form "pasty=@$FILENAME" --form "dl=$SELFPASTE_UPLOAD_SECRET" $ENDPOINT);
  # uncomment the following line for more debug info
  #echo "$RESPONSE";
  RESPONSE_STATUS=$(echo "$RESPONSE" | jq -r .status);
  RESPONSE_MESSAGE=$(echo "$RESPONSE" | jq -r .message);

  if [[ $RESPONSE_STATUS == 200 ]]; then
    echo "$RESPONSE_MESSAGE";
  else
    echo "ERROR. Either your request is invalid (size, type or secret) or something on the endpoint went wrong.";
    echo "Response message: $RESPONSE_MESSAGE";
    exit 4;
  fi;
else
  echo "Provided file is not accessable."
  exit 3;
fi;