#!/bin/sh

PROGRESS_FILE=/tmp/dependancy_evohome_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > $PROGRESS_FILE

echo "Clean...";
sudo apt-get clean
echo 33 > $PROGRESS_FILE

echo "Update.."
sudo apt-get update
echo 66 > $PROGRESS_FILE

echo "Python requests"
retValue=$(php /var/www/html/plugins/evohome/resources/install.php ${PROGRESS_FILE})

if [ $? -ne 0 ]; then
	echo $retValue
else
	echo "Everything is successfully installed !"
fi

rm $PROGRESS_FILE