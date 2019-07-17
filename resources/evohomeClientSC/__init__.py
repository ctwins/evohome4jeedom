from __future__ import print_function
import requests
import json
import codecs
import time
from .location import Location
from .base import EvohomeBase
import logging

logging.basicConfig()
evoLog = logging.getLogger("EvohomeClientSC")

class EvohomeClientSC(EvohomeBase):
	def __init__(self, username, password, pAccessToken, pTokenTimeout, debug=False):
		super(EvohomeClientSC, self).__init__(False)

		self.username = username
		self.password = password
		self.debug = debug
		self.request_timeout = 15
		self.baseurl = 'https://tccna.honeywell.com/WebAPI/emea/api/v1'

		if pAccessToken == None or pAccessToken == '0' or pTokenTimeout == None or pTokenTimeout == 0:
			if debug:
				evoLog.warning('initialize (A)...')
			self._basic_login()
			self.user_account()
			if debug:
				evoLog.warning('initialize done')
		else:
			if debug:
				evoLog.warning('restore previous session (to='+str(pTokenTimeout)+")")
			self.access_token = pAccessToken
			self.access_token_expires = pTokenTimeout
			self._headers = {
				'Authorization': 'bearer ' + pAccessToken,
				'Accept': 'application/json, application/xml, text/json, text/x-json, text/javascript, text/xml'
			}
			retry = False
			try:
				# TODO : prefear refresh_token ?
				self.user_account()
			except requests.HTTPError as eh:
				if eh.response.status_code == 429:
					# too many requests (useless to retry)
					if debug:
						evoLog.warning('due to previous error, useless to retry')
					raise eh
				retry = True
			except Exception as ex:
				if debug:
					evoLog.warning('initialize (B)')
				retry = True
			if retry:
				self._basic_login()
				self.user_account()
				if debug:
					evoLog.warning('initialize done')

		if debug:
			evoLog.warning('load installation...')
		self.installation()
		if debug:
			evoLog.warning('installation loaded')

	def _get_location(self, location):
		if location is None:
			return self.installation_info[0]['locationInfo']['locationId']
		return location

	def _get_single_heating_system(self):
		# This allows a shortcut for some systems
		location = None
		gateway = None
		control_system = None
		
		if len(self.locations)==1:
			location = self.locations[0]
		else:
			raise Exception("More than one location available")
			
		if len(location._gateways)==1:
			gateway = location._gateways[0]
		else:
			raise Exception("More than one gateway available")
			
		if len(gateway._control_systems)==1:
			control_system = gateway._control_systems[0]
		else:
			raise Exception("More than one control system available")
			
		return control_system
		
	def _basic_login(self):
		self.access_token = None
		self.access_token_expires = None

		url = 'https://tccna.honeywell.com/Auth/OAuth/Token'
		headers = {
			'Authorization': 'Basic NGEyMzEwODktZDJiNi00MWJkLWE1ZWItMTZhMGE0MjJiOTk5OjFhMTVjZGI4LTQyZGUtNDA3Yi1hZGQwLTA1OWY5MmM1MzBjYg==',
			'Accept': 'application/json, application/xml, text/json, text/x-json, text/javascript, text/xml'
		}
		data = {
			'Content-Type':	'application/x-www-form-urlencoded; charset=utf-8',
			'Host':	'rs.alarmnet.com/',
			'Cache-Control':'no-store no-cache',
			'Pragma':	'no-cache',
			'grant_type':	'password',
			'scope':	'EMEA-V1-Basic EMEA-V1-Anonymous EMEA-V1-Get-Current-User-Account',
			'Username':	self.username,
			'Password':	self.password,
			'Connection':	'Keep-Alive'
		}
		r = requests.post(url, data=data, headers=headers, timeout=self.request_timeout)
		if r.status_code != requests.codes.ok:
			if self.debug:
				evoLog.warning("error while _basic_login : %s - %s" % (r.status_code, r.text.replace('\r\n','')))
			r.raise_for_status()

		try:
			data = self._convert(r.text)
		except Exception as e:
			evoLog.warning("basic_login : error while convert : %s" % "{0}".format(e))

		self.access_token = data['access_token']
		self.access_token_expires = time.time() + data['expires_in']

		self._headers = {
			'Authorization': 'bearer ' + self.access_token,
			'Accept': 'application/json, application/xml, text/json, text/x-json, text/javascript, text/xml'
		}

	def headers(self):
		if self.access_token is None or self.access_token_expires is None:
			if self.debug:
				evoLog.warning("token is invalid : ask a new one")
			# token is invalid
			self._basic_login()
		elif time.time() > self.access_token_expires - 30:
			# token has expired
			if self.debug:
				evoLog.warning("token expired while getting headers : ask a new one")
			self._basic_login()
		return self._headers

	def user_account(self):
		self.userId = None
		r = requests.get(self.baseurl+'/userAccount', headers=self.headers(), timeout=self.request_timeout)
		if r.status_code != requests.codes.ok:
			if self.debug:
				evoLog.warning("error while user_account : %s - %s" % (r.status_code, r.text.replace('\r\n','')))
			r.raise_for_status()

		self.userId = self._convert(r.text)['userId']

	def installation(self):
		self.locations = []
		r = requests.get(self.baseurl+'/location/installationInfo?userId=%s&includeTemperatureControlSystems=True' % self.userId, headers=self.headers(), timeout=self.request_timeout)
		if r.status_code != requests.codes.ok:
			if self.debug:
				evoLog.warning("error while installation : %s - %s" % (r.status_code, r.text.replace('\r\n','')))
			r.raise_for_status()

		self.installation_info = self._convert(r.text)
		self.system_id = self.installation_info[0]['gateways'][0]['temperatureControlSystems'][0]['systemId']

		for loc_data in self.installation_info:
			self.locations.append(Location(self, loc_data))

		return self.installation_info

	def full_installation(self, location=None):
		location = self._get_location(location)
		r = requests.get(self.baseurl+'/location/%s/installationInfo?includeTemperatureControlSystems=True' % location, headers=self.headers(), timeout=self.request_timeout)
		if r.status_code != requests.codes.ok:
			if self.debug:
				evoLog.warning("error while full_installation : %s - %s" % (r.status_code, r.text.replace('\r\n','')))
			r.raise_for_status()

		return self._convert(r.text)

	def gateway(self):
		r = requests.get(self.baseurl+'/gateway', headers=self.headers(), timeout=self.request_timeout)
		if r.status_code != requests.codes.ok:
			if self.debug:
				evoLog.warning("error while gateway : %s - %s" % (r.status_code, r.text.replace('\r\n','')))
			r.raise_for_status()

		return self._convert(r.text)

	def set_status_normal(self):
		return self._get_single_heating_system().set_status_normal()

	def set_status_reset(self):
		return self._get_single_heating_system().set_status_reset()

	def set_status_custom(self, until=None):
		return self._get_single_heating_system().set_status_custom(until)

	def set_status_eco(self, until=None):
		return self._get_single_heating_system().set_status_eco(until)

	def set_status_away(self, until=None):
		return self._get_single_heating_system().set_status_away(until)

	def set_status_dayoff(self, until=None):
		return self._get_single_heating_system().set_status_dayoff(until)

	def set_status_heatingoff(self, until=None):
		return self._get_single_heating_system().set_status_heatingoff(until)

	def temperatures(self):
		return self._get_single_heating_system().temperatures()

	def zone_schedules_backup(self, filename):
		return self._get_single_heating_system().zone_schedules_backup(filename)

	def zone_schedules_restore(self, filename):
		return self._get_single_heating_system().zone_schedules_restore(filename)
