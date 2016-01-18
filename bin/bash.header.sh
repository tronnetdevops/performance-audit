#!/bin/bash

####
# @brief Header file for Performance Audit bash scripts.
#
# ## Overview
# This file is used to define some common functions and variables to be used by 
# bash scripts written for the Performance Audit, such as server_update.sh
#
# Mostly these are CWD and logging functions.
#
# @date 02/25/2014
# @author <smurray@ontraport.com>
####

SCRIPT_PATH=$(dirname $0)
LOG_PATH="$SCRIPT_PATH/../logs/$(date '+%Y.%m.%d-.-%H-%M-%S')"

####
# @brief Highlight text in YELLOW, give option to bold and add spacer.
#
# @param $1 {String} Text to be highlighted
# @param $2 {Integer} 1 or empty, if "1" determins if text should be bolded.
# @param $3 {Integer} 1 or empty, if "1" then append another empty line after message.
#
# @date 02/25/2014
# @author <smurray@ontraport.com>
####
function logStatus {
	if [ "$2" = "1" ];
	then
		echo "$(tput bold)$(tput setaf 3)$1$(tput sgr0)"
	else
		echo "$(tput setaf 3)$1$(tput sgr0)"
	fi
	
	if [ "$3" = "1" ];
	then
		echo;
	fi
}

####
# @brief Highlight text in GREEN, give option to bold and add spacer.
#
# @param $1 {String} Text to be highlighted
# @param $2 {Integer} 1 or empty, if "1" determins if text should be bolded.
# @param $3 {Integer} 1 or empty, if "1" then append another empty line after message.
#
# @date 02/25/2014
# @author <smurray@ontraport.com>
####
function logSuccess {
	if [ "$2" = "1" ];
	then
		echo "$(tput bold)$(tput setaf 2)$1$(tput sgr0)"
	else
		echo "$(tput setaf 2)$1$(tput sgr0)"
	fi
	
	if [ "$3" = "1" ];
	then
		echo;
	fi
}

####
# @brief Highlight text in RED, give option to bold and add spacer.
#
# @param $1 {String} Text to be highlighted
# @param $2 {Integer} 1 or empty, if "1" determins if text should be bolded.
# @param $3 {Integer} 1 or empty, if "1" then append another empty line after message.
#
# @date 02/25/2014
# @author <smurray@ontraport.com>
####
function logFail {
	if [ "$2" = "1" ];
	then
		echo "$(tput bold)$(tput setaf 1)$1$(tput sgr0)"
	else
		echo "$(tput setaf 1)$1$(tput sgr0)"
	fi
	
	if [ "$3" = "1" ];
	then
		echo;
	fi
}