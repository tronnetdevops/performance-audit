#! /bin/bash

###
# @brief Installs and configures box for performance audits.
#
# ## Overview
# This file checks if certain pre-requisites are satisfied, such as database exists
# and php extensions are loaded. In the event they don't exist, it attempts to create them.
#
# @date 02/25/2014
# @author <smurray@ontraport.com>
###

cd $(dirname $0)
source ./bash.header.sh

BIN_PATH="$SCRIPT_PATH/../bin"
BUILD_PATH="$SCRIPT_PATH/../build"
DATA_PATH="$SCRIPT_PATH/../data"
ASSET_PATH="$SCRIPT_PATH/../assets"
LIB_PATH="$SCRIPT_PATH/../lib"
DOMAINS_PATH="$SCRIPT_PATH/../domains"
DOMAIN_PATH="$DOMAINS_PATH/app.ontraport.com/"
ERROR_FILE=$LOG_PATH/install-err.log

HTACCESS_PATH="$SCRIPT_PATH/../../../www/.htaccess"

# logStatus "Creating log directory at: $LOG_PATH"
# mkdir -p $LOG_PATH
# exec 2>$ERROR_FILE


if [ ! -d "$BUILD_PATH" ];
then
	logSuccess "Creating build directory!" 1
	mkdir $BUILD_PATH
fi

if [ ! -d "$BUILD_PATH/tmp" ];
then
	mkdir $BUILD_PATH/tmp
fi

logSuccess "Starting server updates!" 1
# logStatus "stderr is being redirected to: $ERROR_FILE" 0 1

logStatus "Checking if performance_metrics MySQL database exists..." 1 1
php $BIN_PATH/database_check.php

if [ ! "$?" = 0 ]
then
	logFail "Database has not been set up yet!" 1 1
	
	read -p "Create performance_audit MySQL database and tables? [Y/n] " installDatabase
	if [ "$installDatabase" = "y" -o "$installDatabase" = "Y" -o ! "$installDatabase" ]
	then
		logStatus "Getting credentials from temporary outfile..." 1
		
		DB_CREDS=$(cat $DATA_PATH/dbcreds.dat)

		DBUSER=$(echo $DB_CREDS | cut -d"," -f1)
		DBPASS=$(echo $DB_CREDS | cut -d"," -f2)
		DBHOST=$(echo $DB_CREDS | cut -d"," -f3)
		DBNAME=$(echo $DB_CREDS | cut -d"," -f4)
		
		if [ "$DBUSER" -a "$DBPASS" -a "$DBHOST" -a "$DBNAME" ]
		then
			
			rm -rf $DATA_PATH/dbcreds.dat
			
			logStatus "Attempting to create database..." 1 1
			
			mysql -h$DBHOST -u$DBUSER -p$DBPASS < $DATA_PATH/performance_audit.database.sql
			if [ "$?" = 0 ]
			then
				logSuccess "Database created!" 1 1
			else
				logFail "FAILED! An unkown issue occured when attempting to create the database!!" 1 1
			fi
		else
			logFail "Could not acquire satisfactory database credentials! Please check the following file to verify their veracity!" 1 1
			logFail "$DATA_PATH/dbcreds.dat"
		fi
	else
		logStatus "Skipping MySQL database install..." 1 1
	fi
else
	logSuccess "Database exists!" 1 1
fi

logStatus "Checking if Boomerang Javascript plugin has been compiled..." 1
if [ ! -e "${ASSET_PATH}/boomerang/boomerang.js" ]
then
	logFail "Boomerang hasn't been compiled yet!" 1 1

	read -p "Download and compile Boomerang Javascript plugin? [Y/n] " installBoomerang
	if [ "$installBoomerang" = "y" -o "$installBoomerang" = "Y" -o ! "$installBoomerang" ]
	then
		cd $BUILD_PATH

		logStatus "Cloning boomerang repository..."
		git clone https://github.com/Ontraport/boomerang.git
		logSuccess "Done cloning!" 0 1
		
		cd ./boomerang
		
		logStatus "Compiling source..."
		make
		
		BOOMERANG_BUILT_FILENAME=$(git status -s | xargs | sed "s/.*\?\? //")
		
		mv $BOOMERANG_BUILT_FILENAME ${ASSET_PATH}/boomerang/boomerang.js

		logSuccess "Boomerang build complete!" 0 1
	fi
else
	logSuccess "Boomerang has already been compiled!" 1 1
fi

logStatus "Checking if xhprof PHP exention is loaded..." 1
logStatus "warning: This is checking the cli php extensions, if your apache php and cli run different configurations, this may produce undesirable results." 0 1
if [ ! "$(php -m | grep xhprof)" ]
then
	logFail "XHProf extension does not appear to be installed!" 1 1

	read -p "Install XHProf PHP Extension? [Y/n] " installXHProf
	if [ "$installXHProf" = "y" -o "$installXHProf" = "Y" -o ! "$installXHProf" ]
	then
		cd $BUILD_PATH

		logStatus "Locating where php extensions directory is set to..."
		PHP_EXTENSION_INI_PATH=$(php -i | grep -i "Scan this dir for additional" | cut -d">" -f2 | xargs)
		logSuccess "Found: $PHP_EXTENSION_INI_PATH" 0 1

		logStatus "Cloning xhprof repository..."
		git clone https://github.com/Ontraport/xhprof.git
		logSuccess "Done cloning!" 0 1

		logStatus "Moving into xhprof extension directory..."
		cd ./xhprof/extension
		
		logStatus "Calling \"phpize\" to initiate extension build..."
		phpize
		
		logStatus "Calling \"./configure\" ..." 1
		./configure #--with-php-config=<path to php-config>
		
		logStatus "Doing \"make\" steps..." 0 1
		make
		
		logStatus "Doing \"make install\" steps..." 1
		logStatus "This may require sudo!" 0 1
		make install
		
		logStatus "Doing \"make test\" steps..." 0 1
		make test

		logStatus "Copying over xhprof extension ini..."
		cp "$DATA_PATH/php.extension.ini" "$PHP_EXTENSION_INI_PATH/ext-xhprof.ini"
		
		logStatus "Doing graceful reload of apache..."
		service httpd graceful
		
		cd $BUILD_PATH
		
		mv ./xhprof ./tmp
	
		if [ "$(php -m | grep xhprof)" ]
		then
			logSuccess "XHProf Installed!" 1 1
		else
			logFail "Looks like it didn't install successfully!" 1 1
			exit 1
		fi
	else
		logStatus "Skipping XHProf install..." 1 1
	fi
else
	logSuccess "XHProf extension already installed and loaded!" 1 1
fi


logStatus "Checking if ${HTACCESS_PATH} has auto prepend and append file flags set." 1
if [ ! "$(grep -i "performance-audit" $HTACCESS_PATH)" ]
then 
	logFail "The flags haven't been set yet!" 1 1

	read -p "Update .htaccess file? [Y/n] " updateHTAccess
	if [ "$updateHTAccess" = "y" -o "$updateHTAccess" = "Y" -o ! "$updateHTAccess" ]
	then
		logStatus "Appending flags to $HTACCESS_PATH"
		echo "" >> $HTACCESS_PATH
		echo "##" >> $HTACCESS_PATH
		echo "# performance-audit" >> $HTACCESS_PATH
		echo "##" >> $HTACCESS_PATH
		echo "php_value auto_prepend_file '${DOMAIN_PATH}/PerformanceAuditHeader.php'" >> $HTACCESS_PATH
		echo "php_value auto_append_file '${DOMAIN_PATH}/PerformanceAuditFooter.php'" >> $HTACCESS_PATH
		logSuccess "Finished!" 1 1
	fi
else
	logSuccess "Performance Audit Header and Footer flags present!" 1 1
fi

logStatus "Checking if phantomjs is installed for YSlow and PageSpeed Auditors." 1
if [ "0" = "1" ]
then 
	cd $BUILD_PATH
	if [ "$(phdantomjs -v >/dev/null 2>&1)" = "1.9.7" ]
	then
		wget https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-1.9.7-linux-x86_64.tar.bz2
	fi
	
	# if  yslow.js file doesn't exist already
	wget http://yslow.org/yslow-phantomjs-3.1.8.zip
	
	tar -xz yslow-phantomjs-3.1.8.zip

	
fi
