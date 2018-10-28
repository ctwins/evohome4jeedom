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
READ_SCHEDULE = sys.argv[4]

try:
	client = EvohomeClient(USERNAME, PASSWORD, False)
	# avoid broken pipe in the php caller
	print ' '

	accurateDevices = None
	ACCURATE_MODE = True
	#Initial JSON POST to the website to return your userdata
	baseurl = 'https://tccna.honeywell.com/WebAPI/api/'
	postData = {'Username':USERNAME, 'Password':PASSWORD, 'ApplicationId':'91db1612-73fd-4500-91b2-e63b069b185c'}
	lHeaders = {'content-type':'application/json'}
	response = requests.post(baseurl + 'Session',data=json.dumps(postData),headers=lHeaders)
	# avoid broken pipe in the php caller
	print ' '
	userInfos = json.loads(response.content)
	#Next, using your userid, get all the data back about your site
	#Print out the headers for our temperatures, this let's us input to .csv file easier for charts
	url = baseurl + 'locations?userId=%s&allData=True' % userInfos['userInfo']['userID']
	lHeaders['sessionId'] = userInfos['sessionId']
	response = requests.get(url,data=json.dumps(postData),headers=lHeaders)
	# avoid broken pipe in the php caller
	print ' '
	locations = json.loads(response.content)
	if LOCATION_ID == '-1':
		accurateDevices = locations[0]['devices']
	else:
		for location in locations:
			if str(location['locationID']) == LOCATION_ID:
				accurateDevices = location['devices']
	if accurateDevices == None:
		ACCURATE_MODE = False

	loc = None
	if LOCATION_ID == '-1':
		loc = client.locations[0]
	else:
		for tmp in client.locations:
			if tmp.locationId == LOCATION_ID:
				loc = tmp
	if loc == None:
		print '{"success": false, "errors": [ { "code": "UnknownLocation", "message": "no location for ID ' + LOCATION_ID + '" } ] }'
	else:
		# avoid broken pipe in the php caller
		print ' '
		tcs = loc._gateways[0]._control_systems[0]

		jZones = '{ "success":true'
		# examples : Auto (isPermanent=True) / 
		jZones = jZones + ', "currentMode":"' + tcs.systemModeStatus['mode'] + '"'
		jZones = jZones + ', "permanentMode":'
		if tcs.systemModeStatus['isPermanent']:
			jZones = jZones + 'true'
		else:
			jZones = jZones + 'false'
		if tcs.systemModeStatus['isPermanent']:
			jZones = jZones + ',"untilMode": "NA"'
		else:
			jZones = jZones + ',"untilMode": "' + tcs.systemModeStatus['timeUntil'] + '"'

		jZones = jZones + ',"zones":['
		nb = 0
		nbItems = len(tcs._zones)
		for zone in tcs._zones:
			jZones = jZones + '{"zoneId":' + zone.zoneId
			jZones = jZones + ',"name":"' + zone.name + '"'
			if zone.temperatureStatus['isAvailable'] == False:
				jZones = jZones + ',"temperature": null'
				jZones = jZones + ',"temperatureD5": null'
				jZones = jZones + ',"units":"' + device['thermostat']['units'] + '"'
			elif ACCURATE_MODE:
				for device in accurateDevices:
					if str(device['deviceID']) == zone.zoneId:
						jZones = jZones + ',"temperature":' + str(device['thermostat']['indoorTemperature'])
						jZones = jZones + ',"units":"' + device['thermostat']['units'] + '"'
						jZones = jZones + ',"temperatureD5":' + str(zone.temperatureStatus['temperature'])
			else:
				jZones = jZones + ',"temperature":' + str(zone.temperatureStatus['temperature'])
				jZones = jZones + ',"units":"Celsius"'

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
				print ' '
			jZones = jZones + "}"
			# {'id' : 1567715, 'name' : 'Sejour', 'temperature' : 20.0, 'setPoint' : 17.0, 'status' : 'FollowSchedule'}
			nb = nb + 1
			if nb < nbItems:
				jZones = jZones + ','

		jZones = jZones + ']}'
		# 2018-02-21 - thx to ecc - fix to correctly send some non ascii characters (specifically inside the names of the zones)
		print jZones.encode('utf-8')
except Exception as e:
	print '{"success": false, "errors": [ { "code": "Exception", "message": "' + '{0}'.format(e) + '" } ] }'
