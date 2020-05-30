:: This program is free software: you can redistribute it and/or modify
:: it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
:: You should have received a copy of the
:: COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
:: along with this program.  If not, see http://www.sun.com/cddl/cddl.html
::
:: 2019 - 2020 https://://www.bananas-playground.net/projekt/selfpaste
:: 
:: !WARNING!
:: This is a very simple, with limited experience written, bat file to execute the build.
:: Use at own risk and feel free to improve.
:: 
:: for requirements and how to build it, read the README

@echo off
cls
gcc -Lcurl/lib/ -Icurl/include -Iargtable/ -Lcjson/ -Icjson/ selfpaste-win.c argtable/argtable3.c cjson/cJSON.c -o bin/selfpaste.exe -lcurl