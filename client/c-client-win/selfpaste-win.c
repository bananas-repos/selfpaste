/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
 * You should have received a copy of the
 * COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
 * along with this program.  If not, see http://www.sun.com/cddl/cddl.html
 *
 * 2019 - 2020 https://://www.bananas-playground.net/projekt/selfpaste
 */

/**
 * !WARNING!
 * This is a very simple, with limited experience written, windows C program.
 * Use at own risk and feel free to improve.
 *
 * for requirements and how to build it, read the README
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <direct.h>
#include <dirent.h>
#include <errno.h>

/* https://www.argtable.org */
#include <argtable3.h>

/* https://github.com/curl/curl-for-win curl+openssl */
#include <curl/curl.h>

/* https://github.com/DaveGamble/cJSON */
#include <cJSON.h>

/**
 * global arg_xxx structs
 * https://www.argtable.org/
 */
struct arg_lit *verbose, *quiet, *help, *createConfigFile;
struct arg_file *fileToPaste;
struct arg_end *end;
const char *program_version = "1.1";
const char *program_bug_address = "https://://www.bananas-playground.net/projekt/selfpaste";

struct cmdArguments {
    int quiet, verbose, create_config_file;
    char *file_to_paste;
};

/**
 * Simple random string generation
 */
const char availableChars[] = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-_";
int intN(int n) { return rand() % n; }
char *randomString(int len) {
    char *rstr = malloc((len + 1) * sizeof(char));
    int i;
    for (i = 0; i < len; i++) {
        rstr[i] = availableChars[intN(strlen(availableChars))];
    }
    rstr[len] = '\0';
    return rstr;
}

/**
 * struct to hold the config options loaded from config file
 * Extend if the options file changes.
 */
struct configOptions {
    char *secret;
    char *endpoint;
};

/**
 * struct to hold the returned data from the http post call
 * done with curl
 * see: https://curl.haxx.se/libcurl/c/getinmemory.html
 */
struct MemoryStruct {
    char *memory;
    size_t size;
};

/**
 * callback function from the curl call
 * see: https://curl.haxx.se/libcurl/c/getinmemory.html
 */
static size_t
WriteMemoryCallback(void *contents, size_t size, size_t nmemb, void *userp) {
    struct MemoryStruct *mem = (struct MemoryStruct *)userp;
    size_t realsize = size * nmemb;

    char *ptr = realloc(mem->memory, mem->size + realsize + 1);
    if(ptr == NULL) {
    /* out of memory! */
    printf("not enough memory (realloc returned NULL)\n");
        return 0;
    }

    mem->memory = ptr;
    memcpy(&(mem->memory[mem->size]), contents, realsize);
    mem->size += realsize;
    mem->memory[mem->size] = 0;

    return realsize;
}

/**
 * make a post curl call to upload the given file
 * and receive the URL as a answer
 * see: https://curl.haxx.se/libcurl/c/getinmemory.html
 */
int uploadCall(struct configOptions cfgo, struct cmdArguments arguments) {
    CURL *curl_handle;
    CURLcode res;

    struct MemoryStruct chunk;

    chunk.memory = malloc(1);  /* will be grown as needed by the realloc above */
    chunk.size = 0; /* no data at this point */

    res = curl_global_init(CURL_GLOBAL_ALL);
	if(res != CURLE_OK) {
        printf("ERROR: curl_global_init() failed: %s\n", curl_easy_strerror(res));
        return 1;
    }

    /* init the curl session */
    curl_handle = curl_easy_init();
    /* specify URL to get */
    curl_easy_setopt(curl_handle, CURLOPT_URL, cfgo.endpoint);
    /* send all data to this function */
    curl_easy_setopt(curl_handle, CURLOPT_WRITEFUNCTION, WriteMemoryCallback);
    /* we pass our 'chunk' struct to the callback function */
    curl_easy_setopt(curl_handle, CURLOPT_WRITEDATA, (void *)&chunk);
    /* some servers don't like requests that are made without a user-agent */
    /* field, so we provide one */
    curl_easy_setopt(curl_handle, CURLOPT_USERAGENT, "selfpaseCurlAgent/1.0");

    /* add the POST data */
    /* https://curl.haxx.se/libcurl/c/postit2.html */
    curl_mime *form = NULL;
    curl_mimepart *field = NULL;

    form = curl_mime_init(curl_handle);
    field = curl_mime_addpart(form);
    curl_mime_name(field, "pasty");
    curl_mime_filedata(field, arguments.file_to_paste);

    field = curl_mime_addpart(form);
    curl_mime_name(field, "dl");
    curl_mime_data(field, cfgo.secret, CURL_ZERO_TERMINATED);

    curl_easy_setopt(curl_handle, CURLOPT_MIMEPOST, form);

    /* execute it! */
    res = curl_easy_perform(curl_handle);

    /* check for errors */
    if(res != CURLE_OK || chunk.size < 1) {
        printf("ERROR: curl_easy_perform() failed: %s\n", curl_easy_strerror(res));
        exit(1);
    }

    if (chunk.memory != NULL) {
        if(arguments.verbose) printf("%lu bytes retrieved\n", (unsigned long)chunk.size);
        if(arguments.verbose) printf("CURL returned:\n%s\n", chunk.memory);

        /* https://spacesciencesoftware.wordpress.com/2013/09/10/a-good-way-to-read-json-with-c/ */
		cJSON *json_root = cJSON_Parse(chunk.memory);
		if (json_root != NULL) {
			cJSON *json_result_status = cJSON_GetObjectItem(json_root, "status");
			if (json_result_status != NULL) {
				printf("Status: %s\n", json_result_status->valuestring);
			} else {
				printf("ERROR: Invalid payload returned. Missing 'status'\n%s\n", chunk.memory);
			}
			cJSON *json_result_message = cJSON_GetObjectItem(json_root, "message");
			if (json_result_message != NULL) {
				printf("Message: %s\n", json_result_message->valuestring);
			} else {
				printf("ERROR: Invalid payload returned. Missing 'message'\n%s\n", chunk.memory);
			}
		}
    }

    /* cleanup curl stuff */
    curl_easy_cleanup(curl_handle);
    curl_mime_free(form);
    free(chunk.memory);
    curl_global_cleanup();

    return 0;
}


/**
 * the main part starts here
 */
int main(int argc, char *argv[]) {

    srand(time(NULL));

    /**
     * command line argument default values
     */
    struct cmdArguments arguments;
    arguments.quiet = 0;
    arguments.verbose = 0;
    arguments.create_config_file = 0;
    arguments.file_to_paste = "-";

    /**
     * https://www.argtable.org/
     */
    void *argtable[] = {
        help = arg_litn(NULL, "help", 0, 1, "Display this help and exit"),
        quiet = arg_litn("q", "quiet", 0, 1, "Don't produce any output"),
        verbose = arg_litn("v", "verbose", 0, 1, "Verbose output"),
        createConfigFile = arg_litn("c", "create-config-file", 0, 1, "Create default config file"),
        fileToPaste = arg_filen(NULL, NULL, "<file>", 0, 1, "File to paste"),
        end = arg_end(20),
    };
    /* argtable parsing */
    int nerrors;
    nerrors = arg_parse(argc,argv,argtable);
    /* special case: '--help' takes precedence over error reporting */
    if (help->count > 0) {
        printf("Usage: selfpaste.exe");
        arg_print_syntax(stdout, argtable, "\n");
        arg_print_glossary(stdout, argtable, "  %-25s %s\n");
        arg_freetable(argtable, sizeof(argtable) / sizeof(argtable[0]));
        return(1);
    }
    /* If the parser returned any errors then display them and exit */
    if (nerrors > 0) {
        /* Display the error details contained in the arg_end struct.*/
        arg_print_errors(stdout, end, "selfpaste.exe");
        printf("Try '%s --help' for more information.\n", "selfpaste.exe");
        arg_freetable(argtable, sizeof(argtable) / sizeof(argtable[0]));
        return(1);
    }
    else {
        arguments.quiet = quiet->count;
        arguments.verbose = verbose->count;
        arguments.create_config_file = createConfigFile->count;
        arguments.file_to_paste = fileToPaste->filename[0];
    }

    if(arguments.verbose) {
        printf ("File = %s\n"
            "Verbose = %s\n"
			"Quiet = %s\n"
            "Create config file = %s\n",
            arguments.file_to_paste,
			arguments.verbose ? "yes" : "no",
            arguments.quiet ? "yes" : "no",
            arguments.create_config_file ? "yes" : "no"
        );
    }

    /**
     * Config file check.
     * Also create if not is available and command line option
     * to create it is set.
     */
    char* homedir = getenv("USERPROFILE");
    if(homedir == NULL) {
        printf("ERROR: USERPROFILE directory not found?\n");
        return(1);
    }
    if(arguments.verbose) printf("Homedir: '%s'\n", homedir);

    char selfpasteSaveDir[12] = "\\selfpaste";
    char storagePath[strlen(homedir) + strlen(selfpasteSaveDir)];
    strcpy(storagePath, homedir);
    strcat(storagePath, selfpasteSaveDir);

    DIR* checkStoragePathDir = opendir(storagePath);
    if(checkStoragePathDir) {
        if(arguments.verbose) printf("Storage directory exists: '%s'\n", storagePath);
        closedir(checkStoragePathDir);
    } else if (ENOENT == errno) {
        if(_mkdir(storagePath) == 0) {
            if(arguments.verbose) printf("Storage directory created: '%s'\n", storagePath);
        }
        else {
            printf("ERROR: Storage directory '%s' could not created\n", storagePath);
            return(1);
        }
    }
    else {
        printf("ERROR: Storage directory '%s' could not validated.\n", storagePath);
        return(1);
    }

    char configFileName[16] = "\\selfpaste.cfg";
    char configFilePath[strlen(storagePath) + strlen(configFileName)];

    strcpy(configFilePath, storagePath);
    strcat(configFilePath, configFileName);

    if(arguments.verbose) printf("Configfilepath: '%s'\n", configFilePath);

    if(access(configFilePath, F_OK) != -1) {
        if(arguments.verbose) printf("Using configfile: '%s'\n", configFilePath);
        if(arguments.create_config_file == 1) {
            printf("INFO: Re creating configfile by manually deleting it.\n");
            return(1);
        }
    } else {
        printf("ERROR: Configfile '%s' not found.\n",configFilePath);
        if(arguments.create_config_file == 1) {
            printf("Creating configfile: '%s'\n", configFilePath);

            FILE *fp = fopen(configFilePath, "w");
            if (fp) {
                fputs("# selfpaste config file.\n", fp);
                fprintf(fp, "# See %s for more details.\n", program_bug_address);
                fprintf(fp, "# Version: %s\n", program_version);
                fprintf(fp, "SELFPASTE_UPLOAD_SECRET=%s\n", randomString(50));
                fputs("ENDPOINT=http://you-seflpaste-endpoi.nt\n", fp);
                fclose(fp);

                printf("Config file '%s' created.\nPlease update your settings!\n", configFilePath);
                return(0);
            }
            else {
                printf("ERROR: Configfile '%s' could not be written.\n",configFilePath);
            }
        }
        return(1);
    }

    /**
     * Reading and parsing the config file.
     * populate configOptions struct
     *
     * https://rosettacode.org/wiki/Read_a_configuration_file#C
     * https://github.com/welljsjs/Config-Parser-C
     * https://hyperrealm.github.io/libconfig/
     * https://www.gnu.org/software/libc/manual/html_node/Finding-Tokens-in-a-String.html
     */
    struct configOptions configOptions;
    FILE* fp;
    if ((fp = fopen(configFilePath, "r")) == NULL) {
        printf("ERROR: Configfile '%s' could not be opened.\n",configFilePath);
        exit(1);
    }
    if(arguments.verbose) printf("Reading configfile: '%s'\n", configFilePath);
    char line[128];
    char *optKey,*optValue, *workwith;
    while (fgets(line, sizeof line, fp) != NULL ) {
        if(arguments.verbose) printf("- Line: %s", line);
        if (line[0] == '#') continue;

        /* important. strok modifies the string it works with */
        workwith = strdup(line);

        optKey = strtok(workwith, "=");
        if(arguments.verbose) printf("Option: %s\n", optKey);
        optValue = strtok(NULL, "\n\r");
        if(arguments.verbose) printf("Value: %s\n", optValue);

        if(strcmp("ENDPOINT",optKey) == 0) {
            configOptions.endpoint = optValue;
        }
        if(strcmp("SELFPASTE_UPLOAD_SECRET",optKey) == 0) {
            configOptions.secret = optValue;
        }
    }
    fclose(fp);

    if(arguments.verbose) {
        printf("Using\n- Secret: %s\n- Endpoint: %s\n- File: %s\n",
            configOptions.secret,configOptions.endpoint,
            arguments.file_to_paste);
    }

    /* do the upload */
    uploadCall(configOptions, arguments);

    return(0);
}
