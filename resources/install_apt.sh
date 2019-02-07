PROGRESS_FILE=/tmp/dependancy_evohome_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}

echo "Clean.."
sudo apt-get clean
echo 50 > ${PROGRESS_FILE}

echo "Update.."
sudo apt-get update
echo 75 > ${PROGRESS_FILE}

echo "Check and potentially remove previous evohomeclient module"
sudo pip list | awk 'NR>2 {print $1}' | grep evohomeclient | xargs -I {} pip uninstall -y {}
echo 100 > ${PROGRESS_FILE}

echo "Everything is successfully installed !"
rm ${PROGRESS_FILE}
