import sys
import requests
import json
import time
from evohomeClientSC import EvohomeClientSC
import logging

logging.basicConfig()
evohome_log = logging.getLogger("evohomeBridge-SetMode")

def addTokenTags():
	if CLIENT != None:
		ret = ', "access_token":"' + CLIENT.access_token + '"'
		ret = ret + ', "token_state":' + ('2' if SESSION_ID_V2 != CLIENT.access_token else '1')
		ret = ret + ', "access_token_expires":' + str(CLIENT.access_token_expires)
	else:
		ret = ', "access_token":"0"'
		ret = ret + ', "token_state":0'
		ret = ret + ', "access_token_expires":0'
	return ret

# Ser login details in the 2 fields below
USERNAME = sys.argv[1]
PASSWORD = sys.argv[2]
# payload A
# -- a1
#SESSION_ID_V1 = sys.argv[3]
#USER_ID_V1 = sys.argv[4]
# -- a2
SESSION_ID_V2 = None if sys.argv[5] == '0' else sys.argv[5]
SESSION_EXPIRES_V2 = sys.argv[6]
# -- a3
DEBUG = sys.argv[7] == '1'
# -- a4
LOCATION_ID = sys.argv[8]
# payload B
CODE_MODE = sys.argv[9]
UNTIL = None

CLIENT = None

baseUrl = 'https://tccna.honeywell.com/WebAPI/emea/api/v1/'
lastReceived = None

try:
	CLIENT = EvohomeClientSC(USERNAME, PASSWORD, SESSION_ID_V2, float(SESSION_EXPIRES_V2), DEBUG)

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
		lastReceived = r.text

		ret = json.loads(lastReceived)
		if 'id' in ret:
			td = time.time()
			more = True
			while more:
				print (' ')
				lHeaders = CLIENT.headers()
				lHeaders['Content-Type'] = 'application/json'
				r = requests.get(baseUrl+'commTasks?commTaskId=%s' % ret['id'], headers=lHeaders)
				lastReceived = r.text
				ct = json.loads(lastReceived)

				if ct['state'] == 'Succeeded':
					if DEBUG:
						evohome_log.warning("SetMode has succeeded.")
					print ('{"success":true %s}' % addTokenTags())
					more = False
				else:
					if time.time() - td > 120:
						if DEBUG:
							evohome_log.warning("waiting loop stopped after 2mn.")
						print ('{"success":false,"code":"TreatmentError","message":"Waiting state time exceeded 2mn" %s}' % addTokenTags())
						more = False
					else:
						print (' ')	# avoid broken pipe in the php caller
						if DEBUG:
							evohome_log.warning("Waiting 2sec for state (was %s)" % ct['state'])
						time.sleep(2)
		else:
			print ('{"success":false,"modeSet":%s,"code":"TreatmentError","message":"%s" %s}' % (CODE_MODE, r.text, addTokenTags()))

except Exception as e:
	evohome_log.exception("Exception")
	if lastReceived != None and DEBUG:
		evohome_log.warning("Last received = <%s>" % lastReceived)
	print ('{"success":false,"code":"Exception","message":"%s" %s}' % ("{0}".format(e), addTokenTags()))
