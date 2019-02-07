import sys
import requests
import json
import time
from evohomeClientSC import EvohomeClientSC
import logging

logging.basicConfig()
evohome_log = logging.getLogger("evohomeBridge-InfosZones")
evohome_logV1 = logging.getLogger("evohomeBridge-InfosZones(APIV1)")

baseurl = 'https://tccna.honeywell.com/WebAPI/api/'

SESSION_ID_V1 = None
USER_ID_V1 = None
V1_JUST_RENEWED = False
lastResponseAllDataV1 = None
CLIENT = None
SESSION_ID_V2 = None

def loginV1():
	global SESSION_ID_V1
	global USER_ID_V1
	# Session ID is valid for 15 minutes.
	# Each call with previously obtained Session ID resets timeout interval.
	# For explicit timeout reset use PUT api/Session API call (without response)
	lHeaders = {'content-type':'application/json'}
	jsonData = json.dumps({'Username':USERNAME, 'Password':PASSWORD, 'ApplicationId':'91db1612-73fd-4500-91b2-e63b069b185c'})
	response = requests.post(baseurl + 'Session', data=jsonData, headers=lHeaders)
	# avoid broken pipe in the php caller
	print (' ')
	lastResponseAllDataV1 = response.content
	if response.status_code != requests.codes.ok:
		evohome_logV1.error('open session = %s : %s' % (str(response.status_code), lastResponseAllDataV1.replace('\r\n','')))
	else:
		userInfos = json.loads(lastResponseAllDataV1)
		if (lastResponseAllDataV1.startswith('[') and userInfos[0].has_key('code')) or (not lastResponseAllDataV1.startswith('[') and userInfos.has_key('code')):
			evohome_logV1.error('got 200 but error = <%s>' % lastResponseAllDataV1)
		else:
			SESSION_ID_V1 = userInfos['sessionId']
			USER_ID_V1 = str(userInfos['userInfo']['userID'])
			return True
	SESSION_ID_V1 = '0'
	USER_ID_V1 = '0'
	return False

def getAllDataV1():
	lHeaders = {'content-type':'application/json', 'sessionId':SESSION_ID_V1}
	response = requests.get(baseurl + 'locations?userId=%s&allData=True' % USER_ID_V1, headers=lHeaders)
	# avoid broken pipe in the php caller
	print (' ')
	return response

def removeSessionV1():
	if SESSION_ID_V1 != '' and SESSION_ID_V1 != '0':
		lHeaders = {'content-type':'application/json', 'sessionId':SESSION_ID_V1}
		r = requests.delete(baseurl + 'Session', headers=lHeaders)
		if DEBUG:
			evohome_logV1.warning('removeSessionV1 : %s - <%s>' % (r.status_code, r.text))

def addTokenTags():
	global SESSION_ID_V1
	global USER_ID_V1
	v1State = ('0' if SESSION_ID_V1 == '0' else '2' if SessionIdV1Org != SESSION_ID_V1 else '1')
	if not V1_JUST_RENEWED and SESSION_ID_V1 != '0' and CLIENT != None and SESSION_ID_V2 != CLIENT.access_token:
		if DEBUG:
			# NB : to avoid "too many requests" errors, we don't request for a new session as soon as V2 has just changed (ie, just after [CLIENT = EvohomeClientSC(USERNAME...])
			# so, the next call will do the job (10mn min later regarding the settings available)
			# we do this "reset" (based on the V2 timelive), as the V1 session seems to become unstable after some hours
			evohome_logV1.warning('V2 token has just changed : remove the current V1 session (ready for next call)')
		removeSessionV1()
		v1State = 3
		SESSION_ID_V1 = '0'
		USER_ID_V1 = '0'

	ret = ', "session_id_v1":"' + SESSION_ID_V1 + '"'
	ret = ret + ',"user_id_v1":"' + USER_ID_V1 + '"'
	ret = ret + ',"session_state_v1":' + str(v1State)
	if CLIENT != None:
		ret = ret + ',"access_token":"' + CLIENT.access_token + '"'
		ret = ret + ',"token_state":' + ('2' if SESSION_ID_V2 != CLIENT.access_token else '1')
		ret = ret + ',"access_token_expires":' + str(CLIENT.access_token_expires)
	else:
		ret = ret + ',"access_token":"0"'
		ret = ret + ',"token_state":0'
		ret = ret + ',"access_token_expires":0'
	return ret

# Set login details in the 2 fields below
USERNAME = sys.argv[1]
PASSWORD = sys.argv[2]
# payload A
# -- a1
SESSION_ID_V1 = sys.argv[3]
SessionIdV1Org = SESSION_ID_V1
USER_ID_V1 = sys.argv[4]
# -- a2
SESSION_ID_V2 = None if sys.argv[5] == '0' else sys.argv[5]
SESSION_EXPIRES_V2 = sys.argv[6]
# -- a3
DEBUG = sys.argv[7] == '1'
# -- a4
LOCATION_ID = sys.argv[8]
# payload B
READ_SCHEDULE = sys.argv[9]

logguedV1 = False

try:
	# 0.3.0 - manage cached session V2
	CLIENT = EvohomeClientSC(USERNAME, PASSWORD, SESSION_ID_V2, float(SESSION_EXPIRES_V2), DEBUG)

	# avoid broken pipe in the php caller
	print (' ')

	devicesV1 = None
	# 0.3.0 - manage cached session V1
	if USER_ID_V1 == '' or USER_ID_V1 == '0' or SESSION_ID_V1 == '' or SESSION_ID_V1 == '0':
		logguedV1 = loginV1()			# requests new session
		if DEBUG and logguedV1:
			evohome_logV1.warning('new requested : %s' % SESSION_ID_V1)
	else:
		if DEBUG:
			evohome_logV1.warning('input : %s' % SESSION_ID_V1)
		logguedV1 = True

	if logguedV1:
		getAllDataDone = True
		response = getAllDataV1()
		lastResponseAllDataV1 = response.content
		if response.status_code == 401:
			# 401 : Unauthorized (session has expired)
			if DEBUG:
				evohome_logV1.warning('session expired, request new one')
			logguedV1 = loginV1()
			if not logguedV1:
				# (error is already loggued)
				getAllDataDone = False
			else:
				if DEBUG:
					evohome_logV1.warning('renew as session has expired : %s' % SESSION_ID_V1)
				V1_JUST_RENEWED = True
				# request again :
				response = getAllDataV1()
				lastResponseAllDataV1 = response.content
				if response.status_code != requests.codes.ok:	# can't be 401
					evohome_logV1.error('error=%s : %s' % ( str(response.status_code), lastResponseAllDataV1.replace('\r\n','') ))
					getAllDataDone = False
					removeSessionV1()
					USER_ID_V1 = '0'
					SESSION_ID_V1 = '0'
		elif response.status_code != requests.codes.ok:
			evohome_logV1.error('error=%s : %s' % ( str(response.status_code), lastResponseAllDataV1.replace('\r\n','') ))
			getAllDataDone = False
			removeSessionV1()
			USER_ID_V1 = '0'
			SESSION_ID_V1 = '0'

		if getAllDataDone:
			locationsV1 = json.loads(lastResponseAllDataV1)
			if (lastResponseAllDataV1.startswith('[') and locationsV1[0].has_key('code')) or (not lastResponseAllDataV1.startswith('[') and locationsV1.has_key('code')):
				evohome_logV1.error('got 200 but error=<%s>' % lastResponseAllDataV1.replace('\r\n',''))
			elif LOCATION_ID == '-1':
				devicesV1 = locationsV1[0]['devices']
			else:
				for location in locationsV1:
					if str(location['locationID']) == LOCATION_ID:
						devicesV1 = location['devices']
						break

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
		# avoid broken pipe in the php caller
		print (' ')
		tcs = loc._gateways[0]._control_systems[0]

		jZones = '{"success":true'
		if devicesV1 != None:
			jZones = jZones + ',"apiV1":true'
		else:
			jZones = jZones + ',"apiV1":false'

		# examples : Auto (isPermanent=True) / 
		jZones = jZones + ',"currentMode":"' + tcs.systemModeStatus['mode'] + '"'
		jZones = jZones + ',"permanentMode":'
		if tcs.systemModeStatus['isPermanent']:
			jZones = jZones + 'true'
			jZones = jZones + ',"untilMode":"NA"'
		else:
			jZones = jZones + 'false'
			jZones = jZones + ',"untilMode":"' + tcs.systemModeStatus['timeUntil'] + '"'

		jZones = jZones + ',"zones":['
		nb = 0
		for zone in tcs._zones:
			# 0.3.0 - check now the modelType
			if zone.modelType != 'HeatingZone' and zone.modelType != 'Unknown':
				evohome_log.warning("Infos ZoneId "+zone.zoneId + ", name=[" + zone.name + "], modelType=" + zone.modelType + ", zoneType=" + zone.zoneType)
				# waited : modelType=HeatingZone, zoneType=RadiatorZone
				# can be : modelType=Unknown, zoneType=Unknown
				evohome_log.warning("- setpointCapabilities="+json.dumps(zone.setpointCapabilities))
				evohome_log.warning("- scheduleCapabilities="+json.dumps(zone.scheduleCapabilities))
				evohome_log.warning("- temperatureStatus="+json.dumps(zone.temperatureStatus))
			# zone.name.strip : specific case (from jaktens-2018-11), but was before the check of the modelType (who knows..)
			if zone.modelType == 'HeatingZone' and zone.name.strip():
				nb += 1
				if nb > 1:
					jZones = jZones + ','
				jZones = jZones + '{"zoneId":' + zone.zoneId
				jZones = jZones + ',"name":"' + zone.name + '"'
				if not zone.temperatureStatus['isAvailable']:
					jZones = jZones + ',"temperature":null'
					jZones = jZones + ',"temperatureD5":null'
				elif devicesV1 != None:
					for device in devicesV1:
						if str(device['deviceID']) == zone.zoneId:
							jZones = jZones + ',"temperature":' + str(device['thermostat']['indoorTemperature'])
							#jZones = jZones + ',"temperatureD5":' + str(zone.temperatureStatus['temperature'])
							# example : ["Heat", "Off"]
							# jZones = jZones + ',"allowedModes":' + json.dumps(device['thermostat']['allowedModes'])
							break
				else:
					jZones = jZones + ',"temperature":' + str(zone.temperatureStatus['temperature'])
				if devicesV1 != None:
					for device in devicesV1:
						if str(device['deviceID']) == zone.zoneId:
							jZones = jZones + ',"units":"' + device['thermostat']['units'] + '"'
							break
				else:
					jZones = jZones + ',"units":"Celsius"'
				# 0.3.0 - additional infos
				jZones = jZones + ',"setPointCapabilities":{'
				#jZones = jZones + '"canControl":' + ('true' if zone.setpointCapabilities['canControlHeat'] else 'false')
				jZones = jZones + '"resolution":' + str(zone.setpointCapabilities['valueResolution'])
				jZones = jZones + ',"minHeat":' + str(zone.setpointCapabilities['minHeatSetpoint'])
				jZones = jZones + ',"maxHeat":' + str(zone.setpointCapabilities['maxHeatSetpoint'])
				# examples : ["PermanentOverride", "FollowSchedule", "TemporaryOverride"]
				#jZones = jZones + ',"allowedModes":' + json.dumps(zone.setpointCapabilities['allowedSetpointModes'])
				jZones = jZones + '}'
				"""jZones = jZones + ',"scheduleCapabilities":{'
				jZones = jZones + '"minPerDay":' + str(zone.scheduleCapabilities['minSwitchpointsPerDay'])
				jZones = jZones + ',"maxPerDay":' + str(zone.scheduleCapabilities['maxSwitchpointsPerDay'])
				jZones = jZones + ',"timingResolution":"' + str(zone.scheduleCapabilities['timingResolution']) + '"'
				jZones = jZones + '}'"""
				# 0.1.2 - evohome-client-2.07/evohomeclient2 :
				# - heatSetpointStatus becomes setpointStatus
				# - targetTemperature becomes targetHeatTemperature
				jZones = jZones + ',"setPoint":' + str(zone.setpointStatus['targetHeatTemperature'])
				# example 'FollowSchedule' / PermanentOverride (manual or permanent) / TemporaryOverride+nextTime (until xx)
				jZones = jZones + ',"status":"' + zone.setpointStatus['setpointMode'] + '"'
				if zone.setpointStatus['setpointMode'] == 'TemporaryOverride':
					# example : 2018-01-25T08:00:00
					jZones = jZones + ',"until":"' + zone.setpointStatus['until'] + '"'
				else:
					jZones = jZones + ',"until":"NA"'
				if READ_SCHEDULE == '0':
					jZones = jZones + ',"schedule":null'
				else:
					# add schedule infos (NB : each call to zone.schedule() takes ~1 sec because of an API request)
					jZones = jZones + ',"schedule":' + json.dumps(zone.schedule())
					# avoid broken pipe in the php caller
					print (' ')
				jZones = jZones + "}"
		jZones = jZones + ']'
		jZones = jZones + ',"timestamp":' + str(round(time.time()))
		jZones = jZones + addTokenTags()
		jZones = jZones + '}'
		# 2018-02-21 - thx to ecc - fix to correctly send some non ascii characters (specifically inside the names of the zones)
		print (jZones.encode('utf-8'))

except Exception as e:
	evohome_log.exception('Exception')
	if lastResponseAllDataV1 != None and DEBUG:
		evohome_logV1.warning('V1 lastResponse : %s' % lastResponseAllDataV1.replace('\r\n',''))
	print ('{"success":false,"code":"Exception","message":"%s" %s}' % ('{0}'.format(e), addTokenTags()))

finally:
	if DEBUG:
		evohome_log.warning('done')
