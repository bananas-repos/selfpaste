CC=gcc
CFLAGS=-O2 --machine-arch=native -pipe

# https://github.com/json-c/json-c
CFLAGS+=$(shell pkg-config --cflags json-c)
LIBS+=$(shell pkg-config --libs json-c)

# https://curl.haxx.se
LIBS+=$(shell curl-config --libs)
CFLAGS+=$(shell curl-config --cflags)

all: selfpaste.c
	$(CC) $(CFLAGS) -o selfpaste selfpaste.c $(LIBS)
