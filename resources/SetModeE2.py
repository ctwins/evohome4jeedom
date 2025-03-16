import sys
import requests
import json
import time
from evohomeClientSC import EvohomeClientSC
import logging

logging.basicConfig()
evohome_log = logging.getLogger("evohomeBridge-SetMode")

baseUrl = 'https://tccna.resideo.com/WebAPI/emea/api/v1/'
#baseUrl = 'https://mytotalconnectcomfort.com/WebAPI/emea/api/v1/'

def addTokenTags():
	if CLIENT != None and CLIENT.access_token != None:
		ret = ', "access_token":"' + CLIENT.access_token + '"'
		ret = ret + ', "token_state":' + ('2' if SESSION_ID_V2 != CLIENT.access_token else '1')
		return ret + ', "access_token_expires":' + str(CLIENT.access_token_expires)
	ret = ', "access_token":"0"'
	ret = ret + ', "token_state":0'
	return ret + ', "access_token_expires":0'

VERSION = sys.argv[1]

# Ser login details in the 2 fields below
USERNAME = sys.argv[2]
PASSWORD = sys.argv[3]
# payload A
# -- a1
#SESSION_ID_V1 = sys.argv[4]
#USER_ID_V1 = sys.argv[5]
# -- a2
SESSION_ID_V2 = None if sys.argv[6] == '0' else sys.argv[6]
SESSION_EXPIRES_V2 = float(sys.argv[7])
# -- a3
DEBUG = sys.argv[8] == '1'
# -- a4
LOCATION_ID = sys.argv[9]
# payload B
CODE_MODE = sys.argv[10]
UNTIL = None

CLIENT = None

lastResponse = None

try:
	CLIENT = EvohomeClientSC(USERNAME, PASSWORD, SESSION_ID_V2, SESSION_EXPIRES_V2, DEBUG)

	loc = None
	if LOCATION_ID == '-1':
		loc = CLIENT.locations[0]
	else:
		for tmp in CLIENT.locations:
			if tmp.locationId == LOCATION_ID:
				loc = tmp
				break

	if loc == None:
		print ('{"success":false,"code":"UnknownLocation","message":"no location for ID %s" %s}' % (LOCATION_ID, addTokenTags()))

	else:
		tcs = loc._gateways[0]._control_systems[0]

		# call library :
		#tcs._set_status(CODE_MODE)
		#tcs._set_status(CODE_MODE, UNTIL)

		lHeaders = CLIENT._headers
		lHeaders['Content-Type'] = 'application/json'

		if UNTIL is None:
			data = {"SystemMode":CODE_MODE,"TimeUntil":None,"Permanent":True}
		else:
			data = {"SystemMode":CODE_MODE,"TimeUntil":"%sT00:00:00Z" % UNTIL.strftime('%Y-%m-%d'),"Permanent":False}
		r = requests.put(baseUrl+'temperatureControlSystem/%s/mode' % tcs.systemId, data=json.dumps(data), headers=lHeaders)
		lastResponse = r.text

		ret = json.loads(lastResponse)
		if 'id' in ret:
			td = time.time()
			more = True
			while more:
				lHeaders = CLIENT.headers()
				lHeaders['Content-Type'] = 'application/json'
				r = requests.get(baseUrl+'commTasks?commTaskId=%s' % ret['id'], headers=lHeaders)
				lastResponse = r.text
				ct = json.loads(lastResponse)
				if ct['state'] == 'Succeeded':
					if DEBUG:
						evohome_log.warning("SetMode has succeeded.")
					print ('{"success":true %s}' % addTokenTags())
					more = False
				elif ct['state'] == 'Failed':
					evohome_log.error("Task ended with Failed after %ssec. lastResponse = %s" % (time.time() - td, lastResponse))
					print ('{"success":false,"code":"TreatmentError","message":"Task ended with Failed status after %ssec" %s}' % (time.time() - td, addTokenTags()))
					more = False
				elif time.time() - td > 300:
					msg = "Waiting state time exceeded 5mn (last state was %s)" % ct['state']
					evohome_log.error(msg)
					print ('{"success":false,"code":"TreatmentError","message":"%s" %s}' % (msg, addTokenTags()))
					more = False
				else:
					evohome_log.warning("Waiting for state (was %s)" % ct['state'])
					time.sleep(2)
		else:
			print ('{"success":false,"modeSet":%s,"code":"TreatmentError","message":"%s" %s}' % (CODE_MODE, r.text, addTokenTags()))

except Exception as e:
	evohome_log.exception("Exception")
	if lastResponse != None and DEBUG:
		evohome_log.warning("Last received = <%s>" % lastResponse)
	print ('{"success":false,"code":"Exception","message":"%s" %s}' % ("{0}".format(e), addTokenTags()))