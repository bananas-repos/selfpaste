#!/usr/bin/env bash

# This program is free software: you can redistribute it and/or modify
# it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
# You should have received a copy of the
# COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
# along with this program.  If not, see http://www.sun.com/cddl/cddl.html
#
# 2019 https://www.bananas-playground.net/projekt/selfpaste

if [ $# -lt 1 ]; then
	echo "You need to provide a file to paste";
	echo "selfpaste.sh /path/to/file";
	exit 2;
fi;

FILENAME="$1";
ENDPOINT="";

if [[ -r $FILENAME  ]]; then
  echo "";
else
  echo "Provided file is not accessable."
  exit 3;
fi;