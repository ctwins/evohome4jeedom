import sys
import requests
import json
import time
from evohomeClientSC import EvohomeClientSC
import logging

logging.basicConfig()
evohome_log = logging.getLogger("evohomeBridge-RestaureZones")

#baseUrl = 'https://tccna.honeywell.com/WebAPI/emea/api/v1/'
baseUrl = 'https://mytotalconnectcomfort.com/WebAPI/emea/api/v1/'

def addTokenTags():
	if CLIENT != None and CLIENT.access_token != None:
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
SESSION_EXPIRES_V2 = float(sys.argv[6])
# -- a3
DEBUG = sys.argv[7] == '1'
# -- a4
LOCATION_ID = sys.argv[8]
# payload B
schedules = json.loads(sys.argv[9])

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

		nb = 0
		zonesRet = '['
		taskId = []
		for zone in schedules['zones']:
			nb = nb + 1
			if nb > 1:
				zonesRet = zonesRet + ','
			zonesRet = zonesRet + '{'
			zonesRet = zonesRet + '"zoneId":' + str(zone['zoneId'])
			r = tcs.zones_by_id[str(zone['zoneId'])].set_schedule(json.dumps(zone['schedule']))
			# save "id of task" from r :
			taskId.append([r["id"], False, False])
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
			nbFailed = 0
			for nuplet in taskId:
				if nuplet[1]:
					nbOk = nbOk + 1
				elif nuplet[2]:
					nbFailed = nbFailed + 1
				else:
					if DEBUG:
						evohome_log.warning("request for taskId = %s" % nuplet[0])
					r = requests.get(baseUrl+'commTasks?commTaskId=%s' % nuplet[0], headers=CLIENT.headers())
					lastReceived = r.text
					ct = json.loads(lastReceived)
					if DEBUG:
						evohome_log.warning(" > gives : " + ct['state'])
					if ct['state'] == 'Succeeded':
						nbOk = nbOk + 1
						nuplet[1] = True
					elif ct['state'] == 'Failed':
						nbFailed = nbFailed + 1
						nuplet[2] = True
					else:
						lastBadState = ct['state']
			if (nbOk + nbFailed) == nbTasks:
				if nbFailed > 0:
					msg = "%s/%s task(s) failed" % (nbFailed, nbTasks)
					evohome_log.error(msg)
					print ('{"success":false,"code":"TreatmentError","message":"%s" %s}' % (msg, addTokenTags()))
				else:
					print ('{"success":true,"resultByZone":%s %s}' % (zonesRet, addTokenTags()))
				more = False
			elif time.time() - td > 300:
				msg = "Waiting state time exceeded 5mn (%s/%s ok, last state=%s)" % (nbOk, nbTasks, lastBadState)
				evohome_log.error(msg)
				print ('{"success":false,"code":"TreatmentError","message":"%s" %s}' % (msg, addTokenTags()))
				more = False
			else:
				#if DEBUG:
				evohome_log.warning("Waiting for " + str(nbTasks-nbOk) + " zone(s)...")
				time.sleep(2)

except Exception as e:
	evohome_log.exception("Exception")
	if lastReceived != None:
		evohome_log.error("Last received = <%s>" % lastReceived)
	print ('{"success":false,"code":"Exception","message":"%s" %s}' % ("{0}".format(e), addTokenTags()))

finally:
	if DEBUG:
		evohome_log.warning('done')