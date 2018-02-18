# Script to read the locations and devices+weather attached to

# Load required libraries
import sys
import requests
import json
from evohomeclient2 import EvohomeClient

# login details in the 2 fields below
USERNAME, PASSWORD = sys.argv[1:1+2]

client = EvohomeClient(USERNAME, PASSWORD, False)

print '[',
nb = 0
nbItems = len(client.installation_info)
for loc_data in client.installation_info:
	print '{',
	print '"locationId":' + loc_data['locationInfo']['locationId'],
	print ', "name":"' + loc_data['locationInfo']['name'] + '"',
	print '}',
	nb = nb + 1
	if nb < nbItems:
		print ',',
print ']'
