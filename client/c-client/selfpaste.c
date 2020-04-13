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

// https://www.gnu.org/software/libc/manual/html_node/Argp-Example-3.html#Argp-Example-3
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

struct arguments {
  char *args[1];
  int quiet, verbose, create_config_file;
  char *output_file;
};

// Parse a single option.
static error_t
parse_opt (int key, char *arg, struct argp_state *state) {
  //Get the input argument from argp_parse, which we know is a pointer to our arguments structure.
  struct arguments *arguments = state->input;

  switch (key)
    {
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

// some random string creation stuff
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



int main(int argc, char *argv[]) {
	struct arguments arguments;
	srand(time(NULL));

    // argument parsing and default values
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

    // checking and finding config file
    char* homedir = getenv("HOME");
    if ( homedir == NULL ) {
        homedir = getpwuid(getuid())->pw_dir;
    }
    if(homedir[0] == '\0') {
        printf("ERROR: $HOME directory not found?\n");
        return(1);
    }
    if(arguments.verbose) printf("Homedir: %s\n", homedir);

    char configFileName[15] = "/.selfpaste.cfg";
    if(arguments.verbose) printf("Config file name: '%s'\n", configFileName);
    char configFilePath[strlen(homedir) + strlen(configFileName)];

    strcpy(configFilePath, homedir);
    strcat(configFilePath, configFileName);

    if(arguments.verbose) printf("Configfilepath: '%s'\n", configFilePath);

    if( access( configFilePath, F_OK ) != -1 ) {
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
                fprintf(fp, "# See %s for more details.\n", argp_program_bug_address);
                fprintf(fp, "# Version: %s\n", argp_program_version);
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

	return(0);
}