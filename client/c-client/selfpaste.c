/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
 * You should have received a copy of the
 * COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
 * along with this program.  If not, see http://www.sun.com/cddl/cddl.html
 *
 * 2019 - 2020 https://://www.bananas-playground.net/projekt/selfpaste
 */

/*
 * !WARNING!
 * This is a very simple, with limited experience written, c program.
 * Use at own risk and feel free to improve
 */

#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <argp.h>
#include <unistd.h>
#include <pwd.h>
#include <time.h>

// https://curl.haxx.se
#include <curl/curl.h>

// https://github.com/json-c/json-c
#include <json-c/json.h>

/*
 * Commandline arguments
 * see: https://www.gnu.org/software/libc/manual/html_node/Argp-Example-3.html#Argp-Example-3
 */
const char *argp_program_version = "1.0";
const char *argp_program_bug_address = "https://://www.bananas-playground.net/projekt/selfpaste";
static char doc[] = "selfpaste. Upload given file to your selfpaste installation.";
static char args_doc[] = "file";

// The options we understand.
static struct argp_option options[] = {
    {"verbose",'v', 0, 0, "Produce verbose output" },
    {"quiet", 'q', 0, 0, "Don't produce any output" },
    {"output", 'o', "FILE", 0, "Output to FILE instead of standard output" },
    {"create-config-file", 'c', 0, 0, "Create default config file" },
    { 0 }
};

struct cmdArguments {
    char *args[1];
    int quiet, verbose, create_config_file;
    char *output_file;
};

// Parse a single option.
static error_t
parse_opt (int key, char *arg, struct argp_state *state) {
    struct cmdArguments *arguments = state->input;

    switch (key) {
        case 'q':
            arguments->quiet = 1;
        break;
        case 'v':
            arguments->verbose = 1;
        break;
        case 'o':
            arguments->output_file = arg;
        break;
        case 'c':
            arguments->create_config_file = 1;
        break;

        case ARGP_KEY_ARG:
          if (state->arg_num >= 1)
            // Too many arguments.
            argp_usage (state);

          arguments->args[state->arg_num] = arg;
        break;

        case ARGP_KEY_END:
          if (state->arg_num < 1 && arguments->create_config_file == 0)
            // Not enough arguments.
            argp_usage (state);
        break;

        default:
        return ARGP_ERR_UNKNOWN;
    }

    return 0;
}

static struct argp argp = { options, parse_opt, args_doc, doc };

/*
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

/*
 * struct to hold the config options loaded from config file
 * Extend if the options file changes.
 */
struct configOptions {
    char *secret;
    char *endpoint;
};

/*
 * struct to hold the returned data from the http post call
 * done with curl
 * see: https://curl.haxx.se/libcurl/c/getinmemory.html
 */
struct MemoryStruct {
    char *memory;
    size_t size;
};

/*
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

/*
 * make a post curl call to upload the given file
 * and receive the URL as a answer
 * see: https://curl.haxx.se/libcurl/c/getinmemory.html
 */
int uploadCall(struct configOptions cfgo, struct cmdArguments arguments) {
    CURL *curl_handle;
    CURLcode res;

    struct MemoryStruct chunk;

    chunk.memory = malloc(1);  // will be grown as needed by the realloc above
    chunk.size = 0;    // no data at this point

    curl_global_init(CURL_GLOBAL_ALL);

    // init the curl session
    curl_handle = curl_easy_init();
    // specify URL to get
    curl_easy_setopt(curl_handle, CURLOPT_URL, cfgo.endpoint);
    // send all data to this function
    curl_easy_setopt(curl_handle, CURLOPT_WRITEFUNCTION, WriteMemoryCallback);
    // we pass our 'chunk' struct to the callback function
    curl_easy_setopt(curl_handle, CURLOPT_WRITEDATA, (void *)&chunk);
    // some servers don't like requests that are made without a user-agent
    // field, so we provide one
    curl_easy_setopt(curl_handle, CURLOPT_USERAGENT, "selfpaseCurlAgent/1.0");

    // add the POST data
    // // https://curl.haxx.se/libcurl/c/postit2.html
    curl_mime *form = NULL;
    curl_mimepart *field = NULL;

    form = curl_mime_init(curl_handle);
    field = curl_mime_addpart(form);
    curl_mime_name(field, "pasty");
    curl_mime_filedata(field, arguments.args[0]);

    field = curl_mime_addpart(form);
    curl_mime_name(field, "dl");
    curl_mime_data(field, cfgo.secret, CURL_ZERO_TERMINATED);

    curl_easy_setopt(curl_handle, CURLOPT_MIMEPOST, form);


    // execute it!
    res = curl_easy_perform(curl_handle);

    // check for errors
    if(res != CURLE_OK || chunk.size < 1) {
        printf("ERROR: curl_easy_perform() failed: %s\n", curl_easy_strerror(res));
        exit(1);
    }

    json_object *json, *jsonWork;
    enum json_tokener_error jerr = json_tokener_success;

    if (chunk.memory != NULL) {
        if(arguments.verbose) printf("%lu bytes retrieved\n", (unsigned long)chunk.size);
        if(arguments.verbose) printf("CURL returned:\n%s\n", chunk.memory);

        // https://gist.github.com/leprechau/e6b8fef41a153218e1f4
        json = json_tokener_parse_verbose(chunk.memory, &jerr);
        if (jerr == json_tokener_success) {
            jsonWork = json_object_object_get(json, "status");
            printf("Status: %s\n", json_object_get_string(jsonWork));

            jsonWork = json_object_object_get(json, "message");
            printf("selfpastelink: %s\n", json_object_get_string(jsonWork));
        }
        else {
            printf("ERROR: Invalid payload returned. Check your config:\n%s\n", chunk.memory);
        }
    }

    // cleanup curl stuff
    curl_easy_cleanup(curl_handle);
    curl_mime_free(form);
    free(chunk.memory);
    curl_global_cleanup();

    return 0;
}




/*
 * main routine
 */
int main(int argc, char *argv[]) {
	srand(time(NULL));

    /*
     * command line argument parsing and default values
     */
    struct cmdArguments arguments;
    arguments.quiet = 0;
    arguments.verbose = 0;
    arguments.output_file = "-";
    arguments.create_config_file = 0;

    argp_parse (&argp, argc, argv, 0, 0, &arguments);

    if(arguments.verbose) {
        printf ("File = %s\nOutputfile = %s\n"
            "Verbose = %s\nQuiet = %s\n",
            arguments.args[0],
            arguments.output_file,
            arguments.verbose ? "yes" : "no",
            arguments.quiet ? "yes" : "no",
            arguments.create_config_file ? "yes" : "no"
        );
    }

    /*
     * Config file check.
     * Also create if non is available and command line option
     * to create it is set.
     */
    char* homedir = getenv("HOME");
    if ( homedir == NULL ) {
        homedir = getpwuid(getuid())->pw_dir;
    }
    if(homedir[0] == '\0') {
        printf("ERROR: $HOME directory not found?\n");
        return(1);
    }
    if(arguments.verbose) printf("Homedir: %s\n", homedir);

    char configFileName[16] = "/.selfpaste.cfg";
    if(arguments.verbose) printf("Config file name: '%s'\n", configFileName);
    char configFilePath[strlen(homedir) + strlen(configFileName)];

    strcpy(configFilePath, homedir);
    strcat(configFilePath, configFileName);

    if(arguments.verbose) printf("Configfilepath: '%s'\n", configFilePath);

    if(access(configFilePath, F_OK) != -1) {
        if(arguments.verbose) printf("Using configfile: '%s'\n", configFilePath);
        if(arguments.create_config_file == 1) {
            printf("INFO: Re creating configfile by manually deleting it.\n");
            exit(1);
        }
    } else {
        printf("ERROR: Configfile '%s' not found.\n",configFilePath);
        if(arguments.create_config_file == 1) {
            printf("Creating configfile: '%s'\n", configFilePath);

            FILE *fp = fopen(configFilePath, "w");
            if (fp) {
                fputs("# selfpaste config file.\n", fp);
                fprintf(fp, "# See %s for more details.\n", argp_program_bug_address);
                fprintf(fp, "# Version: %s\n", argp_program_version);
                fprintf(fp, "SELFPASTE_UPLOAD_SECRET=%s\n", randomString(50));
                fputs("ENDPOINT=http://you-seflpaste-endpoi.nt\n", fp);
                fclose(fp);

                printf("Config file '%s' created.\nPlease update your settings!\n", configFilePath);
                exit(0);
            }
            else {
                printf("ERROR: Configfile '%s' could not be written.\n",configFilePath);
            }
        }
        exit(1);
    }

    /*
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

        // important. strok modifies the string it works with
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
            arguments.args[0]);
    }

    // do the upload
    uploadCall(configOptions, arguments);

    return(0);
}
