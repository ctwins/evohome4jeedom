import sys
import requests
import json
from evohomeClientSC import EvohomeClientSC
import logging

logging.basicConfig()
evohome_log = logging.getLogger("evohomeBridge-LocInfos")

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

# login details in the 2 fields below
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

CLIENT = None

try:
	CLIENT = EvohomeClientSC(USERNAME, PASSWORD, SESSION_ID_V2, SESSION_EXPIRES_V2, DEBUG)

	json = '{"success":true, "locations":['
	nb = 0
	for data in CLIENT.locations:
		nb += 1
		if nb > 1:
			json = json + ','
		json = json + '{'
		json = json + '"locationId":' + data.locationId
		json = json + ',"name":"' + data.name + '"'
		json = json + ',"zones":['
		nbz = 0
		for zone in data._gateways[0]._control_systems[0]._zones:
			if zone.modelType == 'HeatingZone' and zone.name.strip():
				nbz += 1
				if nbz > 1:
					json = json + ','
				json = json + '{"id":' + zone.zoneId
				json = json + ',"name":"' + zone.name + '"}'
		json = json + ']}'
	# 2018-02-21 - same as InfosZonesE2 - fix to correctly send some non ascii characters
	json = json + ']'
	json = json + addTokenTags()
	json = json + '}'
	print (json.encode('utf-8'))

except Exception as e:
	evohome_log.exception("Exception")
	print ('{"success":false,"code":"Exception","message":"%s" %s}' % ("{0}".format(e), addTokenTags()))
