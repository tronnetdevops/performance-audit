#####
# Installer and Uninstaller
#
#####

JSDIRPATH :=
HTACCESSPATH :=

VERSION := "0.4"
DATE := $(shell date +%s)
BINPATH := "$(shell pwd)/bin"

checkinstall: 
	echo "lets hope you're running centos! $(uname -a)."

install: checkinstall
	bash ${BINPATH}/server_update.sh

usage:
	echo "Usage:"

uninstall:
	echo "uninstalling"
	# remove all changes made

clean:
	# rm -rf ./build/
	
	echo "cleaning"

.PHONY: checkinstall
.SILENT: