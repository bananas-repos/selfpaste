:: This program is free software: you can redistribute it and/or modify
:: it under the terms of the GNU General Public License as published by
:: the Free Software Foundation, either version 3 of the License, or
:: (at your option) any later version.
::
:: This program is distributed in the hope that it will be useful,
:: but WITHOUT ANY WARRANTY; without even the implied warranty of
:: MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
:: GNU General Public License for more details.
::
:: You should have received a copy of the GNU General Public License
:: along with this program.  If not, see http://www.gnu.org/licenses/gpl-3.0.
::
:: 2019 - 2023 https://://www.bananas-playground.net/projekt/selfpaste
:: 
:: !WARNING!
:: This is a very simple, with limited experience written, bat file to execute the build.
:: Use at own risk and feel free to improve.
:: 
:: for requirements and how to build it, read the README

@echo off
cls
gcc -Lcurl/lib/ -Icurl/include -Iargtable/ -Lcjson/ -Icjson/ selfpaste-win.c argtable/argtable3.c cjson/cJSON.c -o bin/selfpaste.exe -lcurl