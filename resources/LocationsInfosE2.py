import sys
import requests
import json
import codecs
from evohomeClientSC import EvohomeClientSC
import logging

logging.basicConfig()
evohome_log = logging.getLogger("evohomeBridge-LocInfos")

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

VERSION = sys.argv[1]

# login details in the 2 fields below
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

CLIENT = None

try:
	CLIENT = EvohomeClientSC(USERNAME, PASSWORD, SESSION_ID_V2, SESSION_EXPIRES_V2, DEBUG)

	jLocations = '{"success":true, "locations":'
	nbl = 0
	for loc in CLIENT.locations:
		nbl += 1
		jLocations = jLocations + ('[' if nbl == 1 else ',')
		jLocations = jLocations + '{'
		jLocations = jLocations + '"locationId":' + loc.locationId
		jLocations = jLocations + ',"name":"' + loc.name + '"'
		# 0.4.0 - manage the allowed system mode
		tcs = loc._gateways[0]._control_systems[0]
		smList = []
		for oneSm in tcs.allowedSystemModes:
			smList.append(oneSm['systemMode'])
		jLocations = jLocations + ',"asm":' + json.dumps(smList)
		jLocations = jLocations + ',"zones":'
		modelType = None
		nbz = 0
		for zone in tcs._zones:
			if (zone.modelType == 'HeatingZone' or zone.modelType == 'RoundWireless') and zone.name.strip():
				if modelType == None:
					modelType = zone.modelType
				nbz += 1
				jLocations = jLocations + ('[' if nbz == 1 else ',')
				jLocations = jLocations + '{"id":"' + zone.zoneId + '"'
				jLocations = jLocations + ',"typeEqu":"TH"'
				jLocations = jLocations + ',"name":"' + zone.name + '"}'
		jLocations = jLocations + ('null' if nbz == 0 else ']')
		jLocations = jLocations + ',"modelType":' + ('null' if modelType == None else '"'+modelType+'"')
		jLocations = jLocations + '}'
	# 2018-02-21 - same as InfosZonesE2 - fix to correctly send some non ascii characters
	jLocations = jLocations + ('null' if nbl == 0 else ']')
	jLocations = jLocations + addTokenTags()
	jLocations = jLocations + '}'
	ret = jLocations.encode('utf-8')
	if VERSION == '3':
		ret = codecs.encode(ret,"hex")
	print (ret)

except Exception as e:
	evohome_log.exception("Exception")
	print ('{"success":false,"code":"Exception","message":"%s" %s}' % ("{0}".format(e), addTokenTags()))
