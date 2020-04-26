CC=gcc
CFLAGS=-O2 --machine-arch=native -pipe

# https://curl.haxx.se
CFLAGS+=$(shell pkg-config --cflags json-c)
LIBS+=$(shell pkg-config --libs json-c)

# https://github.com/json-c/json-c
LIBS+=$(shell curl-config --libs)
CFLAGS+=$(shell curl-config --cflags)

all: selfpaste.c
	$(CC) $(CFLAGS) -o selfpaste selfpaste.c $(LIBS)
