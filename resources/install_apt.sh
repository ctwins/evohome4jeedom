PROGRESS_FILE=/tmp/dependancy_evohome_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "Launch install of evohome dependancies"

#sudo apt-get clean
#sudo apt-get update
sudo apt-get install -y wget python-pip
echo 50 > ${PROGRESS_FILE}
if [ $(pip list | grep evohomeclient | wc -l) -eq 0 ]; then
    echo "Installation du module evohomeclient pour python"
    sudo pip install evohomeclient
fi
echo 100 > ${PROGRESS_FILE}

echo "Everything is successfully installed!"
rm ${PROGRESS_FILE}
