# Script to change Temp setting on device
# V1 = single device ; args 6/7 for zoneId/value ; until is forced null (means Hold)

# Load required libraries
import sys
import requests
import json
import time
import datetime
from evohomeClientSC import EvohomeClientSC
import logging

logging.basicConfig()
evohome_log = logging.getLogger("evohomeBridge-SetTempEE")

def addTokenTags():
	if CLIENT != None:
		ret = ',"access_token":"' + CLIENT.access_token + '"'
		ret = ret + ',"token_state":' + ('2' if SESSION_ID_V2 != CLIENT.access_token else '1')
		ret = ret + ',"access_token_expires":' + str(CLIENT.access_token_expires)
	else:
		ret = ',"access_token":"0"'
		ret = ret + ',"token_state":0'
		ret = ret + ',"access_token_expires":0'
	return ret

def callSetting(zones,txtData):
	data = json.loads(txtData)
	# value == n.nn or (0.0 ==> reset : revert to FollowSchedule / Scheduled)
	value = float(data['value'])
	if value == 0.0:
		if DEBUG:
			evohome_log.warning("reset %s" % data['zoneId'])
		r = zones[data['zoneId']].cancel_temp_override()
	else:
		if DEBUG:
			evohome_log.warning("setting %s with %s / %s" % (data['zoneId'], value, data['until']))
		# until must be None or compliant as : '%Y-%m-%dT%H:%M:%SZ'
		date = None if data['until'] == None else datetime.datetime.strptime(data['until'],'%Y-%m-%dT%H:%M:%SZ')
		r = zones[data['zoneId']].set_temperature(value,date)
	if DEBUG:
		evohome_log.warning("ret = %s" % r.text.replace('\r\n',''))
	return r

#baseUrl = 'https://tccna.honeywell.com/WebAPI/emea/api/v1/'
baseUrl = 'https://mytotalconnectcomfort.com/WebAPI/emea/api/v1/'

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
DATA = sys.argv[9]		# [{"zoneId":"z1","value":"v1","until":"u1"},{"zoneId":"z2","value":"v2","until":"u2"}[,..]]
						# zn = ZONE ID
						# vn = n.nn or ("0[.0]" or Scheduled == reset)
						# un = can be null (means "Hold") ; if vn # O/Schedule, can be a date/time like "2016-01-16T22:00:00Z"
		# Version 1 => single tuple (received in an array)
if False and DEBUG:
	#	evohome_log.warning("data = <%s>" % DATA)
	evohome_log.warning("SESSION_ID_V2 = %s" % SESSION_ID_V2)
	evohome_log.warning("SESSION_EXPIRES_V2 = %s" % SESSION_EXPIRES_V2)

CLIENT = None
lastResponse = None

try:
	CLIENT = EvohomeClientSC(USERNAME, PASSWORD, SESSION_ID_V2, float(SESSION_EXPIRES_V2), DEBUG)

	loc = None
	if LOCATION_ID == '-1':
		loc = CLIENT.locations[0]
	else:
		for tmp in CLIENT.locations:
			if tmp.locationId == LOCATION_ID:
				loc = tmp

	if loc == None:
		print ('{"success":false,"code":"UnknownLocation","message":"no location for ID %s" %s}' % (LOCATION_ID, addTokenTags()))
	else:
		response = callSetting(loc._gateways[0]._control_systems[0].zones_by_id,DATA)
		# Version 1 : single result
		lastResponse = response.text

		if response.status_code == 400:
			# 400-Bad Request - When validation error occurred. List of possible validation errors:
			# - code="ParameterIsMissing"	some of the required parameters are missing.
			# - code="ForbiddenParameter"	forbidden parameter was passed.
			# - code="DeviceIsLost"			the device is offline.
			# - code="ValueOutOfRange"		either HeatSetpoint is out of valid range (defined by upper and lower limits).
			ret = json.loads(lastResponse)
			print ('{"success":false,"code":"%s","message":"%s" %s}' % (ret[0]["code"], ret[0]["message"], addTokenTags()))

		elif response.status_code != 201:
			print ('{"success":false,"code":"Error","message":"%s" %s}' % (lastResponse.replace('\r\n',''), addTokenTags()))

		else:
			# task ID is created
			ret = json.loads(lastResponse)
			taskId = ret['id']

			more = True
			td = time.time()
			while more:
				r = requests.get(baseUrl+'commTasks?commTaskId=%s' % taskId, headers=CLIENT.headers())
				lastResponse = r.text
				ct = json.loads(lastResponse)
				# see : https://mytotalconnectcomfort.com/WebApi/Help/Model/TrueHome.WebApi.Models.Responses.CommTaskState
				if ct['state'] == 'Succeeded':
					if DEBUG:
						evohome_log.warning('succes :)')
					print ('{"success":true %s}' % addTokenTags())
					more = False
				elif ct['state'] == 'Failed':
					evohome_log.error("Task ended with Failed after %ssec. lastResponse = %s" % (time.time() - td, lastResponse))
					print ('{"success":false,"code":"TreatmentError","message":"Task ended with Failed status" %s}' % addTokenTags())
					more = False
				elif time.time() - td > 300:
					msg = "Waiting state time exceeded 5mn (last state was %s)" % ct['state']
					evohome_log.error(msg)
					print ('{"success":false,"code":"TreatmentError","message":"%s" %s}' % (msg, addTokenTags()))
					more = False
				else:
					evohome_log.warning('Waiting for state (was %s)' % ct['state'])
					time.sleep(2)

except Exception as e:
	evohome_log.exception("Exception")
	if lastResponse != None and DEBUG:
		evohome_log.warning('>> lastResponse : ' + lastResponse)
	print ('{"success":false,"code":"Exception","message":"%s" %s}' % ("{0}".format(e), addTokenTags()))

finally:
	if DEBUG:
		evohome_log.warning('done')