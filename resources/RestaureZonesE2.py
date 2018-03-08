# Script to read the zones available in the device 0

# Load required libraries
import sys
import requests
import json
from evohomeclient2 import EvohomeClient

# Ser login details in the 2 fields below
USERNAME = sys.argv[1]
PASSWORD = sys.argv[2]
LOCATION_ID = sys.argv[3]
FILE_PATH = sys.argv[4]
#print 'locid = [' + LOCATION_ID + ']',

client = EvohomeClient(USERNAME, PASSWORD, False)

loc = None
if LOCATION_ID == '-1':
	loc = client.locations[0]
else:
	for tmp in client.locations:
		if tmp.locationId == LOCATION_ID:
			loc = tmp

if loc == None:
	print '{ "success":false, "errors" : [ { "code" : "UnknownLocation", "message" : "no location for ID ' + LOCATION_ID + '" } ] }'
else:
	tcs = loc._gateways[0]._control_systems[0]

	zonesRet = '['
	nb = 0
	nbItems = len(tcs._zones)
	#print ' - nbItems=' + str(nbItems),
	with open(FILE_PATH, 'r') as f:
		schedule_db = f.read()
		schedules = json.loads(schedule_db)
		for zone in schedules['zones']:
			zonesRet = zonesRet + '{'
			zonesRet = zonesRet + '"zoneId" : ' + str(zone['zoneId'])
			zonesRet = zonesRet + ', "name" : "' + zone['name'] + '"'
			retValue = tcs.zones_by_id[str(zone['zoneId'])].set_schedule(json.dumps(zone['schedule']))
			zonesRet = zonesRet + ', "result" : ' + json.dumps(retValue)
			zonesRet = zonesRet + '}'
			nb = nb + 1
			if nb < nbItems:
				zonesRet = zonesRet + ','
	zonesRet = zonesRet + "]"
	# 2018-02-24 - same as InfosZonesE2 - fix to correctly send some non ascii characters
	print '{ "success" : true, "resultByZone" : ' + zonesRet.encode('utf-8') + ' }'
