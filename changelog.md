# Changelog - evohome4jeedom

## [version 0.1.1] - 2018-03-11 - the 'INIT fix' (and some more)
### Fixes
- general configuration panel :
	1. sorry for that, I missed the "just after the installation" and a big misatake in the user/password fields
	2. still for these fields, something fills with your jeedom account when configuration has not been saved yet ; attempt to reset them in this case
	3. miss the saving of the 3 radio inputs ; there was needed to click or change value so it was taken into account
- a dummy value was stayed in the field version of the info.json
- the two files temperature_content.html was saved with BOM marker
- about the two schedule php file : add a check for a optional field in the scheduleToShow array to avoid http.error logs

## [version 0.1] - 2018-03-08 - dashboard version v1
### Added
- general configuration panel :
	1. choice between Celsius or Fahrenheit unit : please note it's not only a display setting, if affects the values stored in history<br/>
	When unit changes, only incoming values are converted and stored (no history manipulation)
	2. choice of the default schedule display mode, between Horizontal and Vertical layer
	3. choice of the interval of data collect (10, 15, 20 & 30 mn), specially to minimize history load<br/>
	NB : launch a collect less or aqual 5 minutes could Honeywell to blacklist the requester for a while.<br/>
	So the min. fixed to 10mn regarding History graphs for a long period, these values are (enough)
	4. choice to show the Presence Modes on the Console widget or by a popup from the Console (the previous behavior)
	5. choice to enforce the data collect just before launch a schedule saving (to be sure you will have the fresh data, in case you have just modified any schedule on the Evotouch or mobile application)
	6. facility regarding the clean period of history : this setting adjusts in one time (when saving) all your components 

- List of components : each is now showed by its picture

- Th component : new Info : about the scheduled setpoint, with history tracking ; usage : see differences between schedule and reality (by manual settings or automatic adjustments when Evotouch is set to optimization mode)
 
- Th widget :
	1. a P button appears so you can display the schedule of the zone
	2. in optimization mode, when setpoint is different as the scheduled one, a arrow appears :
		- down-green if setpoint is lower
		- up-red if setpoint if higher
		> A tip over the icon shows the scheduled setpoint

- Console and Th widgets : the P(x) buttons are now toggle commands (one click to show schedule(s), one click to hide)

- Console : Save action : You can now enter a commentary. Any characters accepted (encoded to preserve special ones)

- Schedule display
	1. Commentary as seen in the Save action above, is showed on the top
	2. Current day and current setpoint are now showed on a lightgreen background color

### Evolutions
- Console
	- Ween you launch some actions, a waiting message is now displayed in the top of screen

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
