import sys
import requests
import json
import time
from evohomeClientSC import EvohomeClientSC
import logging

logging.basicConfig()
evohome_log = logging.getLogger("evohomeBridge-RestaureZones")

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

#baseUrl = 'https://tccna.honeywell.com/WebAPI/emea/api/v1/'
baseUrl = 'https://mytotalconnectcomfort.com/WebAPI/emea/api/v1/'

# Ser login details in the 2 fields below
USERNAME = sys.argv[1]
PASSWORD = sys.argv[2]
# payload A
# -- a1
#SESSION_ID_V1 = sys.argv[3]
#USER_ID_V1 = sys.argv[4]
# -- a2
SESSION_ID_V2 = None if sys.argv[5] == '0' else sys.argv[5]
SESSION_EXPIRES_V2 = float(sys.argv[6])
# -- a3
DEBUG = sys.argv[7] == '1'
# -- a4
LOCATION_ID = sys.argv[8]
# payload B
FILE_PATH = sys.argv[9]

CLIENT = None

lastReceived = None

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
		print ('{"success":false,"code":"UnknownLocation","message":"no location for ID %s %s}' % (LOCATION_ID, addTokenTags()))
	else:
		tcs = loc._gateways[0]._control_systems[0]

		zonesRet = '['
		nb = 0
		taskId = []
		with open(FILE_PATH,'r') as f:
			schedule_db = f.read()
			schedules = json.loads(schedule_db)
			for zone in schedules['zones']:
				nb = nb + 1
				if nb > 1:
					zonesRet = zonesRet + ','
				zonesRet = zonesRet + '{'
				zonesRet = zonesRet + '"zoneId":' + str(zone['zoneId'])
				#zonesRet = zonesRet + ', "name" : "' + zone['name'] + '"'
				r = tcs.zones_by_id[str(zone['zoneId'])].set_schedule(json.dumps(zone['schedule']))
				# save "id of task" from r :
				taskId.append([r["id"], False])
				zonesRet = zonesRet + ',"taskId":' + str(r["id"])
				zonesRet = zonesRet + '}'
		zonesRet = zonesRet + "]"

		# loop on the "id of task", and get out when each is finished
		nbTasks = len(taskId)
		td = time.time()
		more = True
		lastBadState = "dummy"
		while more:
			nbOk = 0
			for pair in taskId:
				if pair[1]:
					nbOk = nbOk + 1
				else:
					if DEBUG:
						evohome_log.warning("request for taskId = %s" % pair[0])
					r = requests.get(baseUrl+'commTasks?commTaskId=%s' % pair[0], headers=CLIENT.headers())
					lastReceived = r.text
					ct = json.loads(lastReceived)
					if DEBUG:
						evohome_log.warning(" > gives : " + ct['state'])
					if ct['state'] == 'Succeeded':
						nbOk = nbOk + 1
						pair[1] = True
					else:
						lastBadState = ct['state']
			if nbOk == nbTasks:
				more = False
				# 2018-02-24 - same as InfosZonesE2 - fix to correctly send some non ascii characters
				#print '{"success":true, "resultByZone":' + zonesRet.encode('utf-8') + ', "access_token":"%s"}' % SESSION_ID_V2
				print ('{"success":true, "resultByZone":%s %s}' % (zonesRet, addTokenTags()))
			elif time.time() - td > 300:
				if DEBUG:
					evohome_log.warning("waiting loop stopped after 5mn")
				print ('{"success":false,"code":"TreatmentError","message":"Waiting state time exceeded 5mn (%s ok for %s, last state=%s)" %s}' % (nbOk, nbTasks, lastBadState, addTokenTags()))
				more = False
			else:
				#if DEBUG:
				evohome_log.warning("Waiting for " + str(nbTasks-nbOk) + " task(s)...")
				time.sleep(2)

except Exception as e:
	evohome_log.exception("Exception")
	if lastReceived != None:
		evohome_log.error("Last received = <%s>" % lastReceived)
	print ('{"success":false,"code":"Exception","message":"%s" %s}' % ("{0}".format(e), addTokenTags()))

finally:
	if DEBUG:
		evohome_log.warning('done')