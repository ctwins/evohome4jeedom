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
CODE_MODE = sys.argv[4]
UNTIL = None

client = EvohomeClient(USERNAME, PASSWORD, False)

loc = None
if LOCATION_ID == '-1':
	loc = client.locations[0]
else:
	for tmp in client.locations:
		if tmp.locationId == LOCATION_ID:
			loc = tmp

if loc == None:
	print '{ "success": false, "errors" : [ { "code" : "UnknownLocation", "message" : "no location for ID ' + LOCATION_ID + '" } ] }'
else:
	tcs = loc._gateways[0]._control_systems[0]
	systemId = tcs.systemId;

	# call library :
	#tcs._set_status(CODE_MODE);
	#tcs._set_status(CODE_MODE, UNTIL);

	headers = dict(client.headers)
	headers['Content-Type'] = 'application/json'

	if UNTIL is None:
		data = {"SystemMode":CODE_MODE,"TimeUntil":None,"Permanent":True}
	else:
		data = {"SystemMode":CODE_MODE,"TimeUntil":"%sT00:00:00Z" % UNTIL.strftime('%Y-%m-%d'),"Permanent":False}
	r = requests.put('https://tccna.honeywell.com/WebAPI/emea/api/v1/temperatureControlSystem/%s/mode' % systemId, data=json.dumps(data), headers=headers)

	ret = client._convert(r.text)
	if 'id' in ret:
		r = requests.get('https://tccna.honeywell.com/WebAPI/emea/api/v1/commTasks?commTaskId=%s' % ret['id'], headers=headers)
		print '{ "success" : true, "modeSet" : ' + CODE_MODE + ', "result" : ' + r.text + ' }'
	else:
		print '{ "success" : false, "modeSet" : ' + CODE_MODE + ', "errors" : [ { "code" : "TreatmentError", ' + r.text[1:] + ' ] }'
