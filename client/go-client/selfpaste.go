package main

import (
	"errors"
	"fmt"
	"log"
	"math/rand"
	"os"
)

// This program is free software: you can redistribute it and/or modify
// it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
// You should have received a copy of the
// COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
// along with this program.  If not, see http://www.sun.com/cddl/cddl.html

// 2019 - 2022 https://://www.bananas-playground.net/projekt/selfpaste

const website = "https://www.bananas-playground.net/projekt/selfpaste"
const version = "1.0"
// used for non-existing default config
const letters = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-_"

func main() {
	loadConfig()
}

/**
 * Check and display error with additional message
 */
func errorCheck(e error, msg string) {
	if e != nil {
		log.Fatal(msg,e)
	}
}

/**
 * load or even create a default config
 * $HOME/.selfpaste.yaml
 */
func loadConfig() {
	homeDir, err := os.UserHomeDir()
	errorCheck(err, "No $HOME directory available?")
	log.Printf("Your $HOME: %s \n", homeDir)

	var configFile = homeDir + "/.selfpaste.yaml"

	if _, err := os.Stat(configFile); errors.Is(err, os.ErrNotExist) {
		log.Printf("Config file not found. Creating default: %s \n", configFile)

		newConfig, err := os.Create(configFile)
		errorCheck(err, "Can not create config file!")
		defer newConfig.Close()


		_, err = fmt.Fprintf(newConfig, "# selfpaste go client config file.\n")
		errorCheck(err, "Can not write to new config file")
		fmt.Fprintf(newConfig, "# See %s for more details.\n", website)
		fmt.Fprintf(newConfig, "# Version: %s\n", version)
		fmt.Fprintf(newConfig, "endpoint:\n")
		fmt.Fprintf(newConfig, "  host: http://your-seflpaste-endpoi.nt\n")
		fmt.Fprintf(newConfig, "  secret: %s\n", randStringBytes(50))

		log.Fatalf("New default config file created: - %s - Edit and launch again!",configFile)
	}
}

/**
 * just a random string
 */
func randStringBytes(n int) string {
	b := make([]byte, n)
	for i := range b {
		b[i] = letters[rand.Intn(len(letters))]
	}
	return string(b)
}
