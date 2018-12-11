# Changelog - evohome4jeedom

## [version 0.2.1] - 2018-12-11 - View history fix
### Improvements
- Schedules edition mode
	1. indicator '*' added after the zone name in the zones list, when schedule for it has changed<br/>
	2. you can now revert the changes on a day with the small button which appears on the right when changes has done<br/>

### Change
- Category of the plugin becomes Energy

### Fix
- History graphs are now also displayable from the View mode

## [version 0.2.0] - 2018-11-10 - the 'schedule edition'
### NEWS
- Schedules edition mode (thanks to ecc for pre-testing)<br/>
	1. the Edition mode has to be activated in the General configuration before to use it<br/>
	2. first, use the Pc button (or Ps if you have already saved a full schedule) on console of any component, then Click on Edit button on the top right (or on the right on each zone name), and let's go !<br/>
	3. changes are lost if you close the edition popup ; you have to Save before quit to keep your changes<br/>

### Improvements
- better cache around cron/get informations from zones
- Schedule popup :<br/>
	1. zone names are now those defined in your components<br/>
	2. showed zones are now only those affected in your components<br/>
	3. labels days are now translated (french/english)<br/>
- component :<br/>
	1. better management of the 'presence mode'<br/>
	2. now, the tip under the label for the Temporary Override shows the until time (already shown after the icon, but can be masked if you did not enlarge the tile width enough)<br/>
- adjust look of the tiles (console and component)

### Fixes
- no more check python-pip in dependency_info (could fail after some update)
- python part / get zones information (thanks to jaktens and ecc)<br/>
	1. better management of exceptions. Due to that, you could see now exception messages in the Jeedom messages popup.<br/>
	2. no more crash when encounter an incomplete device
- python part / adjust mode<br/>
	3. was KO after the 0.1.2 (no more compliant with the update of evohomeclient)<br/>

## [version 0.1.2] - 2018-10-28 - the 'evohomeclient fix' (+ minors)
### Fixes
- evohome-client-2.07/evohomeclient2 ; Zone and schedule data :<br/>
	1. heatSetpointStatus becomes setpointStatus<br/>
	2. targetTemperature becomes targetHeatTemperature<br/>
### More
- InfosZonesE2.py<br/>
	1. some dummy prints during the reading to avoid the broken pipe potential error<br/>
	2. decoding zones infos protected under try/catch<br/>
- evohome.class.php / dependancy_info<br/>
	1. no more check python-pip (fails now after some upgrade in my conf)

## [version 0.1.1] - 2018-03-11 - the 'INIT fix' (and some more)
### Fixes
- general configuration panel :<br/>
	1. sorry for that, I missed the "just after the installation" and a big mistake in the user/password fields<br/>
	2. still regarding these fields, 'something' fills them with your jeedom account when configuration has not been saved yet ; attempt to reset them in this case<br/>
	3. I missed the saving of the 3 radio inputs ; click or change value was needed so it was taken into account<br/>
- a dummy value was stayed in the field version of the info.json (+ version number now in name field)
- the two files temperature_content.html was saved with BOM marker
- about the two schedule php file : add a check for a optional field in the scheduleToShow array to avoid http.error logs

## [version 0.1] - 2018-03-08 - dashboard version v1
### Added
- general configuration panel :<br/>
	1. choice between Celsius or Fahrenheit unit : please note it's not only a display setting, if affects the values stored in history<br/>
	When unit changes, only incoming values are converted and stored (no history manipulation)
	2. choice of the default schedule display mode, between Horizontal and Vertical layer<br/>
	3. choice of the interval of data collect (10, 15, 20 & 30 mn), specially to minimize history load<br/>
	NB : launch a collect less or aqual 5 minutes could Honeywell to blacklist the requester for a while.<br/>
	So the min. fixed to 10mn regarding History graphs for a long period, these values are (enough)<br/>
	4. choice to show the Presence Modes on the Console widget or by a popup from the Console (the previous behavior)<br/>
	5. choice to enforce the data collect just before launch a schedule saving (to be sure you will have the fresh data, in case you have just modified any schedule on the Evotouch or mobile application)<br/>
	6. facility regarding the clean period of history : this setting adjusts in one time (when saving) all your components 

- List of components : each is now showed by its picture

- Th component : new Info : about the scheduled setpoint, with history tracking ; usage : see differences between schedule and reality (by manual settings or automatic adjustments when Evotouch is set to optimization mode)
 
- Th widget :<br/>
	1. a P button appears so you can display the schedule of the zone<br/>
	2. in optimization mode, when setpoint is different as the scheduled one, a arrow appears :<br/>
		- down-green if setpoint is lower<br/>
		- up-red if setpoint if higher<br/>
		> A tip over the icon shows the scheduled setpoint<br/>

- Console and Th widgets : the P(x) buttons are now toggle commands (one click to show schedule(s), one click to hide)

- Console : Save action : You can now enter a commentary. Any characters accepted (encoded to preserve special ones)

- Schedule display<br/>
	1. Commentary as seen in the Save action above, is showed on the top<br/>
	2. Current day and current setpoint are now showed on a lightgreen background color<br/>

### Evolutions
- Console : when you launch some actions, a waiting message is now displayed in the top of screen

### Fixes
- labels in french rendered now without developer configuration
- when a heating is lowbatt, no temperature is available (null value) : now, in the zones select list on a equipment, this is showed by '(unavailable)'
- for the actions like : Set Mode, Restore, Save with refresh data :
	- wait for ending of data collecting (typically from the cron) if currently running
	- lock (then unlock) the cron to avoid concurrent api access (and response time degradation)
- schedule popup is now closed just before launching any action (Set Mode, Save, Restore and Remove)

### Underground
- better manipulation of json data, in case of null or KO content (more log, no more bad effects)
- after restore, refresh only temp. & setPoint data, and merge restored schedule (speed up process)

### To be followed
- render 'mobile' widgets with actions and popup available

## [beta 2] - 2018-02-25 - the 'ECC FIXES'

### Fixed (many thanks to 'ecc')
- *install*<br/>
	1. cron is now  blocked when/while plugin status = NOK, or if your credentials are not set (login/password)<br/>
	2. check of the dependencies were incorrect, caused a permanent NOK status<br/>
	3. when no location is specified yet, it caused a error in receiving argument on InfosZonesE2.py<br/>

- *python to php*<br/>
	1. data could not be 'sent' by python, when some UTF8 characters was inside your zones names, or system names<br/>

- *php 5 vs 7*<br/>
	1. split function replaced by explode<br/>
	2. PHP7 restrictions on json : booleans built in python are now returned correctly formed<br/>

### Improved
- *install_apt.sh*<br/>
	1. restore the apt-get clean and update commands 

## [beta 1] - 2012-02-18
first publication
