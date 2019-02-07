import json
import requests
from .base import EvohomeBase, EvohomeClientInvalidPostData

class ZoneBase(EvohomeBase):
	def __init__(self):
		super(ZoneBase, self).__init__()

	def schedule(self):
		r = requests.get(self.client.baseurl+'/%s/%s/schedule' % (self.zone_type, self.zoneId), headers=self.client.headers(), timeout=self.client.request_timeout)

		if r.status_code != requests.codes.ok:
			r.raise_for_status()

		mapping = [
			('dailySchedules', 'DailySchedules'),
			('dayOfWeek', 'DayOfWeek'),
			('temperature', 'TargetTemperature'),
			('timeOfDay', 'TimeOfDay'),
			('switchpoints', 'Switchpoints'),
			('dhwState', 'DhwState'),
		]
		j = r.text
		for f, t in mapping:
			j = j.replace(f, t)
			
		d = self._convert(j)
		# change the day name string to a number offset (0 = Monday)
		for day_of_week, schedule in enumerate(d['DailySchedules']):
			schedule['DayOfWeek'] = day_of_week
		return d

	def set_schedule(self, zone_info):
		# must only POST json, otherwise server API handler raises exceptions

		try:
			t1 = json.loads(zone_info)
		except:
			raise EvohomeClientInvalidPostData('zone_info must be JSON')

		headers = dict(self.client.headers())
		headers['Content-Type'] = 'application/json'
		r = requests.put(self.client.baseurl+'/%s/%s/schedule' % (self.zone_type, self.zoneId), data=zone_info, headers=headers, timeout=self.client.request_timeout)
		if r.status_code != requests.codes.ok:
			r.raise_for_status()
		return self._convert(r.text)

class Zone(ZoneBase):

	def __init__(self, client, data=None):
		super(Zone, self).__init__()
		self.client = client
		self.zone_type = 'temperatureZone'
		if data is not None:
			self.__dict__.update(data)

	def _set_heat_setpoint(self, data):
		headers = dict(self.client.headers())
		headers['Content-Type'] = 'application/json'
		r = requests.put(self.client.baseurl+'/temperatureZone/%s/heatSetpoint' % self.zoneId, json.dumps(data), headers=headers, timeout=self.client.request_timeout)
		if r.status_code != requests.codes.ok:
			r.raise_for_status()
		return r

	def set_temperature(self, temperature, until=None):
		if until is None:
			data = {"HeatSetpointValue":temperature,"SetpointMode":"PermanentOverride","TimeUntil":None}
		else:
			data = {"HeatSetpointValue":temperature,"SetpointMode":"TemporaryOverride","TimeUntil":until.strftime('%Y-%m-%dT%H:%M:%SZ')}
		return self._set_heat_setpoint(data)

	def cancel_temp_override(self, *args, **kwargs):
		data = {"HeatSetpointValue":0.0,"SetpointMode":"FollowSchedule","TimeUntil":None}
		return self._set_heat_setpoint(data)
