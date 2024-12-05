# Changelog - Evohome (& Round T87RF & Lyric T6/T6R) for Jeedom V3.3 to V4.4.x

## [version 0.6.0] - 2024-12-05 - Jeedom 4.4.x compatibility

#### Fixes

1. Widgets malformed when displaying first time on screen<br/>
(due to unexplained switch in version 4.4 of Jeedom of delayed import of js files)<br/>
Thanks to [thierry.viens](https://community.jeedom.com/t/probleme-daffichage-plugin-honeywell/131356/18) for his time during our tests
2. Change setpoint no more worked.<br/>
(due to a more restrictive PHP version regarding static functions)<br/>
Thanks to [Gsxrnoir2001](https://community.jeedom.com/t/probleme-setconsignedata/131862) for his request.

## [version 0.5.7] - 2023-05-08 - Python 3 compatibility & Added TH Scenario modes

#### Added

1. New settings added in the list of Setting point :<br/>
Current setting point -1°<br/>
Current setting point -0.5°<br/>
Current setting point +0.5°<br/>
Current setting point +1°<br/>
Please note the new value is computed from the Current setting, not the Current scheduled<br/>

#### Fixes

1. Plugin is now dependant of Python 3 library (and no more Python 2)<br/>
This was required by the Debian 11 Bulleye edition.
2. Lyric only : was a bug during first initialization preventing creation of 2 two equipments.


## [version 0.5.6] - 2022-11-04 - Previous mode by Scenario & Auto schedules limits

#### Added

1. Scenario, Set Mode action : you can now select a Previous Mode option, which set the system on the previous mode if any (the ones was previously current when last change has occured).<br/>
You have to **save** the Console equipment so the new option appears in the selectable values.<br/>
Thanks to [bludomo](https://community.jeedom.com/t/comment-retourner-sur-le-dernier-mode-evohome-dans-un-scenario/81732).

2. Schedule edition<br/>
A) All the editable values can now be set only in the ranges specified by the system.<br/>
This restricts keyboard inputs :
    - heating : typically 5/25 for Evohome, 5/35 for Lyric.<br/>
    These values can be set by the physical console for each area (at least for Evohome).<br/>
    Evohome : each area setting is taken into account.<br/>
    Lyric : setting values out of scope conducted the schedule restoration to fail in the check phase. At first answer, I limited to 2mn the time of checking.<br/>
    Thanks to [sergedomotique](https://community.jeedom.com/t/boucle-infinie-en-restauration-de-programme/79155) for our tests.
    - times : minutes can only be set by step of 10 (same for Evohome/Lyric).<br/>
      This unmanageable limit is showed in a tip under the minutes field.
B) General
    - Buttons min/max added with values available (same as individual setting on a TH)<br/>
    - periods (no user change) : the min and max number of periods are now get from the system data for Evohome, and fixed to 1/6 for Lyric.<br/>
      This unmanageable limit is showed in a tip under the Append button.<br/>
      To follow the rules, step time and periods limits are (also) stored in new info cmd for each TH (if you saved your TH again, or launch a synchronization ; each is not mandatory).


#### Remarks
1. Under Jeedom 4.3.9 (perhaps 4.3.x), the dynamic display while setting a TH is disturbed (but the command works properly).


## [version 0.5.5] - 2022-02-13 - Jeedom 4.2.x compatibility (Lyric part)

#### Fixes

1. Specific Lyric : procedure of Initialization with Honeywell failed with the new Jeedom 4.2 security approach.<br/>
/!\ You have to change the callback URL as described in the README.md or French documentation.

#### Under the hood (technical parts)

1. Check part is now limited to 2mn, after restoring a week Schedule. Under certain condition (a segment set at 00:00), the schedule received after the refresh is not eaxctly the same as this send by the plugin (observed with a Lyric system, but only sometimes). 


## [version 0.5.4] - 2022-02-05 - Jeedom 4.2.x compatibility

#### Fixes

1. Horizontal Schedule & editing : did not work with Jeedom 4.2.x.


## [version 0.5.3(.1)] - 2021-12-04(05) - Mobile relooking & Detailed informations

#### Added

1. Some 'command informations' appear in Console and TH equipments (to get them, just Synchronize on general configuration or (re)Save equipment(s)<br/>
     - Console (from "Etat")
       - Current Mode and Previous Mode [thanks to Noobs94]
       - Permanent mode (0=no, next field waited ; 1=yes), Mode until (NA if previous = 0)
       - Current schedule name
       - Lyric specific : Occupancy counter (0..n)<br/>
     - TH (from "Type consigne")
       - Status (string)
       - (status active) Until (NA if not applicable)
       - Previous temperature
       - Delta from previous temperature (use this one to trigger Scenario)
       - DateTime Battery low, DateTime Connection lost [thanks to Melchior]

#### Changes

1. Widgets for Mobile displaying were rewrited
     - Console
       - only mode change is kept (statistic mode & schedule info/actions removed), accordingly to general configuration (inside widget or by popup. NB : "inside" is not available for Lyric)
     - TH
       - height resizing
       - no more schedule or setting buttons
2. General configuration ; Console part
      - remove 'reading before saving' (became useles since schedule editor exists)

#### Fixes

1. Console : "override" icon was displayed if mode was not "Follow Schedule"

#### Under the hood (technical parts)

1. Split code from honeywell.class.php to 3 files/classes under 'modules' folder + functions renaming and/or redispatched
2. Remove obsolete HTML elements ; close all 'a' tags


## [version 0.5.2] - 2021-01-31 - Jeedom 4.1 compatibility

#### Fixes

1. [thanks to titinh] Widget were malformed when rendered under Jeedom 4.1
2. [thanks ElDje] The character '$' can now be used in the password

#### Under the hood (technical parts)

1. minor - Evohome cloud access (Python parts) : better check of current token


## [version 0.5.1] - 2020-11-08 - Lyric fixes & TH min/max usage

#### Changes

1. Lyric & geofencing schedule : manage now the triggers data seen on the sleep section (used when displaying the schedule, and save/restore actions).
2. TH : each of min/max settings now used in the editor
3. General configuration : added "'x' equ. added, 'y' equ. modified" on the status message, after Synchronization action (all devices)

#### Fixes

1. [thanks to jcapelle] Lyric : the URL callback for authentication part could now have a port specified
2. [thanks to jeedommaison59] Minor fixes in the General configuration

#### Under the hood (technical parts)

1. js part of evohome.php sent to honeywell.js, and minified
2. core/HeatMode.php replaced by core/structures.php with more structures (classes) parts and their converters (better 'object' approach)
3. Evohome users : auto-update a new field when updating plugin (avoid the previous request 'Do a Sync. after update')
4. info.json structure a bit modified to better match the required


## [version 0.5.0] - 2020-08-30 - The Lyric (T6/T6R) edition
*Thanks to jcapelle, aalizon and Touns as beta testers*

#### Added

1. In the title : the plugin manages now the Lyric T6/T6R device and full authentication under OAuth2 protocol.<br/>
Please see the doc to know how to login (the plugin documentation or README.md on github).<br/>
Technically, the token received is refreshed every 20mn by a cron created in Jeedom during installation (see the page "Task engine", class=evohome, function=main_refresh).<br/>
By this approach, the token has an infinite live duration, as your network and internet connection is stable.

#### Changes
1. The plugin image has been changed to no longer be specialized 'evohome' ;)

#### Fixes

1. password can now contains any character (was KO with a ';' : Thanks [domonew](https://community.jeedom.com/t/plugin-evohome/12666/35))
2. some image files were resized (avoid useless bigger files, and sometimes glitches on screen)
3. some minor bugs ; it's not in my habit not to say which ones, but I lost my tracks (working on this version started at february, and the PC used broke down recently)

#### Under the hood (technical parts)

1. js files are now auto refreshed (no more need to clear the browser's cache after update)
2. big refactoring of the core classes to be ready for many devices and protocols
3. refactoring of JS parts (less globals variables) and minify all js files
3. removing useless files and folders
4. preparation to change the name of the plugin (evohome>honeywell) (delayed for a future version)
5. the zoneId are now managed as String against Integer (previous schedules files remain compatibles)


## [version 0.4.3] - 2020-01-03 - Opened window & Day view on horizontal Schedule & Full Jeedom V4 compatibility

#### Improvements

1. TH widget : "Opened window" detection reported with an icon : based upon setPoint = minHeat and state = Follow schedule
2. Schedule, Horizontal view, from Console only : a new button appears to view all zones by day. This mode is also editable.
2. (bis) Schedule / Edition mode : the "Copy" function is now controlled from a single modal
3. Jeedom V4 compatibiliy ('cosmetic' revision as 0.4.2 was already ready for J4) : specially, the dark mode is taken into account

#### Fixes

1. data reading : add Battery Low cases
2. TH widget / integrated +/- did not work in certain cases
3. Invalid check for python-requests (thanks to github/titidnh)


## [version 0.4.2] - 2019-08-23 - PHP7.2 & Mobile app compatibilities

#### Improvements

1. PHP7.2 (and more) : ensures compatibility now
   - by the way, gives a compatibility with Jeedom V4. WARNING ! this first step gives a runnable plugin, lot of rendering has to be corrected for V4
2. Mobile app : modifications on some cmd settings, so they are now compliant with the Mobile application (on smartphone)

#### Fixes

1. synchronization : small bug in the returned value
2. schedule saving : avoid a minor error reported in http.error under certain condition
3. change in check dependencies (look now for python-requests, no more avconv !). Thanks **Yotasky**.
4. was missing one translation

#### Under the hood

1. The field ZoneId is now stored in the logicalId field of equipment (**synchronization needed !**)


## [version 0.4.1] - 2019-08-07 - the title edition ;)

#### Added

1. new section "Thermostats" in the general configuration (and TH widgets effects)<br/>
You can now set :
   - the mode of the heatpoint settings :<br/>
     - inside the widget : is the previous mode before 0.4.0. Remember the change of setting is permament.
     - by popup : introduced by 0.4.0 : with this mode, you can adjust the duration of the change
   - the way the title bar of the TH widgets are filled
     - Deactivated : as before 0.4.0, no color applied ;)
	 - System (from category) : (0.4.1) - Use the system color and as specified, depending of the category choosen on each widget
	 - System + 2 thresholds : like previous 0.4.0, but now, use internal code instead the warning and error levels on the 'temperature' command (which are removed when you Synchronize the plugin 'again').<br/>
	 You can also set your own values for the 2 levels. Colors remain fixed to plain 'orange' ('warning' like) and plain 'red' ('error' like).<br/>
	 (by the way, a battery default does'nt show red bar no more)
	 - from official colors (with gradients) : use the Honeywell official colors and threshold values, and apply a gradient depending of the current value and the next 'table' value.
   - by the way, the choice of units and the display accuracy are moved into this section

2. Set consigne by scenario : new value "Cancel" in top of list<br/>
*This new choice replaces the scripting we discuss with pykforum around 2019-06-03 in the forum*

#### Improvements
1. changes in the general configuration of values which Console or TH widgets displaying effects are now auto-applied when you Save the conf. (interesting with 2 browser tabs or windows)
3. Set Consigne
   - if the requested values are the same as the current ones (heatpoint and duration), no action is launched (and reported as 'error')
   - better reporting of potential faults ; displayed messages are also prefixed with "By Scenario" in case of.
   - the 'cancel' button in the widget is no more grayed : the reason is, value could be the same, not the duration. "Cancel" goes back to the "Followed schedule" mode.
   - scenario : the scope of values available in the list of selectable values are now those set on the system (in the physical console ; default 5 / 25)<br/>
   NB : these values are set during each data reading if needed (and not in the Synchro action, as, in this phase, the default values are used because real ones are unknown at this time)
     - these same values follow now the Units chosen (in the general configuration)

#### Fixes

1. open a equipement (editing) : showing image of the equipment could be erroneous
2. was missing translations of the new labels from 0.4.0
3. Synchro action is now under control of a timeout of 2 mn (fix the double or multi clics bad effects)
4. synchro action : the detection of the Virtual plugin is now really effective (caused some troubles)
5. the Python module "requests" is now installed in the dependencies
6. Set Consigne : corrections regarding the Unit chosen (+ values adjusted in selectable list for scenario)
FUTURE - taking into account the Unit chosen for Schedule displaying & editing


## [version 0.4.0] - 2019-07-17 - the "multi-locations & Round-Thermostat Edition"

#### Added
1. **/!\ [thanks to moldu59] The plugin can manage now multi-locations<br/>**
   - **IN ALL CASES**, please follow these steps when you update the plugin :
     - (update from the market panel)
     - open the configuration from Plugins/Plugins management/Evohome
     - (you will see that the Location selectable list has been removed)
     - Click on Synchronize<br/>
   - **If you install for the first time**, follow these same steps (of course, after previously setting your account)<br/>
   - Your schedule files are of course specific for each location and are automaticaly adapted

2. [thanks to moldu59] Plugin is now compatible with the **Rounded Thermostat**<br/>
(and takes into account the restricted Setting Modes as those presents on the Evohome device of official applications)

#### Improvements
1. [thanks to ecc] Get now the connection lost fault from the gateway (red wifi icon appears on the top right of each widget)<br/>
Please note that, in this case, the reading could run, as the API return again and again the last values got
2. [from above] For the Set Mode, Set Temp and Restore action, manage now the 'failed' status received from the task manager
3. "title bar" on the widgets receive a backround color :<br/>
   - T >= 28 : error color (red)
   - T >= 26 : warning color (orange)
   - else    : standard color (depending of the category)
4. manual heatpoint setting opens now a popup so the duration can be adjusted

#### Fixes
1. During changing setpoint, when interactive information appear, the grayed Consigne and spinner band disappeared
2. When restore a schedule file which contains no change from the current one, screen was freezing with the big rolling wheel (refreshing the page - F5 or else - restablished the situation)
3. [thanks to moldu59] Empty schedule was badly displayed
4. Trying opening the Schedule panel in Horizontal view when no Console is currently displayed caused a JS error (and freezing the screen)
5. [thanks to ecc] Small batt KO icon (on the top left of the TH widget) did'nt appear when the fault is not received from the API<br/>
although no TH value is received (API says isAvailable=false).


## [version 0.3.2] - 2019-02-18 - fix #7 - the verbose edition

#### Added
1. General configuration
	 - A refresh button appears, to aid the first installation
        - enter user & password
        - click on Refresh
        - the Localisation should appear in the selectable list
        - after chosen the localisation, launch the Synchronization
	 - Synchronization
        - to avoid potential name conflicts (with other components of other plugin(s) on the same parent object), the TH components name are now prefixable
        - if some component are added during Synchronization, the screen is now refreshed (as you open Plugins/Energy/Evohome)

2. /!\ TH widget<br/>
	 Detection of battery low / connection lost<br/>
   An icon appears on the left top corner, with the date/time on tip when the default appeared (reported by Evohome)

#### Improvements
1. Configuration (of components)
   - if a component is not marked as Visible, its tile is now grayed (as for Activated)
   - if a component has no area affected (yet), it shows now a 'men at work' image (and no more a TH image), to avoid confusion

2. /!\ for the actions which could take a "certain time" to finish (Setting heatpoint, Set Mode and especially Restore schedule), the progress of the action is now showed at top of screen. (this is why I call this update "the verbose edition")<br/>
   It appears also when the action is triggered by Scenario, and if the associated component is currently visible on screen :<br/>
   - console for Set mode and Restore schedule
   - the target TH component for Setting heatpoint

3. Statistics enabled : 2 infos added :
	 - a up green or red down animated arrow could appear on the right of the current temperature, with a tip showing the delta between the previous measure
	 - the delta between current temperature and heatpoint value is showed above the fire or 'ok' icon
	 - NB : to get a correct display of the TH widget with all these new informations, their dimensions should be at least 210px x 120px<br/>
	   (TIP : on the general configuration, you can use the Synchronization action + option 'Resize the existing component' activated to easily resize all your components)

4. Restore schedule
	 - only the zones containing different schedule as the current one are now sent
	 - after sending the zones, it could be more than one reading data action to refresh correctly the current schedule data (each after 30sec of waiting)

#### Fixes
1. /!\ Blocked situation could appear when the task executed by python script took too much time (around 2mn).<br/>
   This is due to the PHP disposition which stops execution of a script after a certain delay.<br/>
	 Two dispositions taken :
   - as this time limit can be reset while execution, it's now simply the case
   - but, to avoid too much waiting time, all commands are now limited to a duration of 5mn (from the PHP side, it's a classical timeout disposition)
2. Loading Schedule file from Scenario did not work (bad parameter sending)
3. Schedule editor : the pre-selected day and slice (green background) did not revert their style after moving/chosing another slice
4. The cached token was badly received by the LocationsInfoE2 and RestaureZonesE2 scripts (caused a token regeneration, and potentially gives a "Too much requests" error)

#### Changes
1. TH widget : the 'ok' icon is renewed


## [version 0.3.1] - 2019-02-09 - fix #6

#### Improvement
- The version (here 0.3.1) appears now after the Version label on the General configuration (and has been removed from the title)

#### Fix
- Correction of regression for new installation (or user/password blanks)
additional : introduce a timeout (5mn) in the "waiting for python loops" to avoid general blockings

#### Under the hood
- Revision of logging around call of the method runPython, in case of internal and python error appears
This could be have to effect to show additional errors in the "Jeedom messages" panel


## [version 0.3.0] - 2019-02-07 - the "+/- edition"
#### Added
- General configuration
	- A Synchronize button appears in the general configuration<br/>
		a. For the new installations : set your account, save => the location should appear in the list, choose it, then Synchronize<br/>
		b. For existing installations : it's convenient to use it to auto-upgrade the commands added in all the equipements (see below..)
	- a new option "Synchronize with clock" appears right to the Interval choice.<br/>
	Check it if you want the cron launches the reading at 'precise time'<br/>
  That means, for example, if the interval is set to 10mn, the cron will trigger the reading at HH:00, HH:10, HH:20....<br/>
  Let's say, it will beautify the history graph ;)

- In the up-right corner of each widget, a small color circle appears which shows the status of synchronization with the system (auto-off after 4 sec)
	- green : all is right, when click (*) on it, a tip is showed with the last and the next time of synchronization
	- orange : applies only when the display accuracy is other than the default one : the native values could not be retrieved
	- red broken-link icon (+ grayed values on the TH widget) : synchronization did not work on the last attempt. When click, the tip shows the last time it worked.<br/>
	In this situation, no command could be send to the system (SetMode, Restore schedule, Adjusting)
	- a spinning icon : when reading is running

- **(SO WAITED !)** Adjust buttons appear on the TH widgets<br/>
  up/down/reset (reset will set to the scheduled heatpoint value)<br/>
  Please note that the displaying of these buttons are under control of the (new) command "Set heatpoint" / showing in the commands of the TH equipment, command which appears after re-save the equipement (or by using the Synchronize action in the general configuration).<br/>
  That means that you can control the heatpoint by Scenario too (with a selectable list, from 5 to 25 by 0.5) ;)<br/>
  How to : adjust as you want ; after a delay of 4 sec next to the last action, the command is sent to the system<br/>
  At this time, the sepoint is background grayed with a spinning icon. After a while (waiting for system state), the right background appears (and status bar on the top of screen shows the result of command, so, successful, or error message)<br/>
  You can adjust more then one TH at at time, as each sending is internally serialized<br/>

- a new option 'Statistics' appears in the Console commands (corresponding to the new Statistics command which appears after re-save the equipment - or do a Synchronize action, see above - and if you have chosen to Show it)<br/>
  So, a select list appears in the Console widget, with the choices : Deactivated, Day, Week, Month.<br/>
  A click (*) on the small grid displayed on each TH widget makes appear a more detailed bigger grid (auto-off after 10 sec)

(*) : no mouse hover, so the smartphone compatibility

#### Improvements
- When editing Scenario, the SetMode, RestoreSchedule (and Adjustment) use now a selectable list<br/>
  Please note that if a Schedule file is used in a Scenario, it could no more be deleted from the Console
- A status bar appears now on the top of the window after sending command to the system<br/>
  like SetMode, Restore schedule, Adjust heatpoint
- Schedule panels (all zones and individual views) : showing now a '*' on the right of the area name which current content is different of the current (or last read) one<br/>
  This is a more detailed state which completes the "different of the current schedule" ;)
- The 'incoming' of the 0.2.2 is now available (Schedule edition : saving changes on the current - selected - schedule activates now the loading button)

#### Fix
- Python/reading informations : better check of the Heating area (fix the reported error by "jaktens")

#### Changes
- now, only the admin profile has the possibility to do these things regarding the schedules :
	1. Editing, Saving, Restore, Remove schedule

#### Under the hood
- The extra module evohomeclient is now embedded inside the plugin itself ('forked' to introduce the token cache and custom logs)<br/>
  If you restart the dependency (not mandatory) under the General configuration, the module will be removed in the OS (by a pip command)
- So, all the API requests use now cached session information (session for V1 API, token for V2 API)<br/>
  This major improvement made the heatpoint adjustment possible, and has also the effect to fight the "Too many requests" errors (by the way, few ones could persist)
- All the structural commands (SetMode, Restore schedule and now Adjust.) use know the waiting status of task<br/>
  Please note this is experimental cause sometimes, status will not terminate with the waited "Succedeed"<br/>
  In this case, the loop terminates by a timeout to avoid blocking the plugin (and status bar will show that)<br/>
  In Debug mode, you can follow the waiting in the 'http.error' log
- All requests made against the Python layer are serialized, to avoid collision, and potentially "Too many requests" situations
- Most of functions on the TH widget become unique in a .js file
- Most of widget and schedule panel html style becomes classes
- The Python layers logs now verbously under control of the Debug level of the plugin


## [version 0.2.3] - 2018-12-19 - pictures adjustments
#### Changes
- icon of the plugin to go out of the official Jeedom chart<br/>
- changing of the 'Console' photo + the 2 photos have now a transparent background
- adding the missing 'reverse' icon (needed since 0.2.1)
- adding version info in the title of the conf. page (never visible elsewhere)


## [version 0.2.2] - 2018-12-16 - fix #5
#### Improvements
- Temperature tile<br/>
	1. adjust display of icon and additional information 

#### Fixes
- Schedules edition mode<br/>
	1. adding a slice with the hh:mm after all existing was badly inserted<br/>
	2. bad color when leaving a virtual slice (00:00 without setting)<br/>
	3. could not remove any slice when only 2 slice defined with the first at 00:00
	4. revert just after adding a slice caused js error (and no effect)  

#### Incoming
- Schedules edition mode<br/>
	1. saving change on the current schedule should activate the loading button<br/>
	consider the active schedule has not to be loaded


## [version 0.2.1] - 2018-12-11 - the 'schedule edition' 1.1

#### Improvements
- Schedules edition mode<br/>
	1. indicator '*' added after the area name in the zones list, when schedule for it has changed<br/>
	2. you can now revert the changes on a day with the small button which appears on the right when changes has done<br/>

#### Fix
- History graphs are now also displayable from the View mode

#### Change
- Category of the plugin becomes Energy


## [version 0.2.0] - 2018-11-10 - the 'schedule edition' 1.0
#### Added
- Schedules edition mode (thanks to ecc for pre-testing)<br/>
	1. the Edition mode has to be activated in the General configuration before to use it<br/>
	2. first, use the Pc button (or Ps if you have already saved a full schedule) on console of any component, then Click on Edit button on the top right (or on the right on each area name), and let's go !<br/>
	3. changes are lost if you close the edition popup ; you have to Save before quit to keep your changes<br/>

#### Improvements
- better cache around cron/get informations from zones
- Schedule popup :<br/>
	1. area names are now those defined in your components<br/>
	2. showed zones are now only those affected in your components<br/>
	3. labels days are now translated (french/english)<br/>
- component :<br/>
	1. better management of the 'presence mode'<br/>
	2. now, the tip under the label for the Temporary Override shows the until time (already shown after the icon, but can be masked if you did not enlarge the tile width enough)<br/>
- adjust look of the tiles (console and component)

#### Fixes
- no more check python-pip in dependency_info (could fail after some update)
- python part / get zones information (thanks to jaktens and ecc)<br/>
	1. better management of exceptions. Due to that, you could see now exception messages in the Jeedom messages popup.<br/>
	2. no more crash when encounter an incomplete device
- python part / adjust mode<br/>
	3. was KO after the 0.1.2 (no more compliant with the update of evohomeclient)<br/>


## [version 0.1.2] - 2018-10-28 - the 'evohomeclient fix' (+ minors)
#### Fixes
- evohome-client-2.07/evohomeclient2 ; area and schedule data :<br/>
	1. heatSetpointStatus becomes setpointStatus<br/>
	2. targetTemperature becomes targetHeatTemperature<br/>
#### More
- InfosZonesE2.py<br/>
	1. some dummy prints during the reading to avoid the broken pipe potential error<br/>
	2. decoding zones infos protected under try/catch<br/>
- evohome.class.php / dependancy_info<br/>
	1. no more check python-pip (fails now after some upgrade in my conf)


## [version 0.1.1] - 2018-03-11 - the 'INIT fix' (and some more)
#### Fixes
- general configuration panel :<br/>
	1. sorry for that, I missed the "just after the installation" and a big mistake in the user/password fields<br/>
	2. still regarding these fields, 'something' fills them with your jeedom account when configuration has not been saved yet ; attempt to reset them in this case<br/>
	3. I missed the saving of the 3 radio inputs ; click or change value was needed so it was taken into account<br/>
- a dummy value was stayed in the field version of the info.json (+ version number now in name field)
- the two files temperature_content.html was saved with BOM marker
- about the two schedule php file : add a check for a optional field in the scheduleToShow array to avoid http.error logs


## [version 0.1] - 2018-03-08 - dashboard version v1
#### Added
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
	1. a P button appears so you can display the schedule of the area<br/>
	2. in optimization mode, when setpoint is different as the scheduled one, a arrow appears :<br/>
		- down-green if setpoint is lower<br/>
		- up-red if setpoint if higher<br/>
		> A tip over the icon shows the scheduled setpoint<br/>

- Console and Th widgets : the P(x) buttons are now toggle commands (one click to show schedule(s), one click to hide)

- Console : Save action : You can now enter a commentary. Any characters accepted (encoded to preserve special ones)

- Schedule display<br/>
	1. Commentary as seen in the Save action above, is showed on the top<br/>
	2. Current day and current setpoint are now showed on a lightgreen background color<br/>

#### Improvements
- Console : when you launch some actions, a waiting message is now displayed in the top of screen

#### Fixes
- labels in french rendered now without developer configuration
- when a heating is lowbatt, no temperature is available (null value) : now, in the zones select list on a equipment, this is showed by '(unavailable)'
- for the actions like : Set Mode, Restore, Save with refresh data :
	- wait for ending of data collecting (typically from the cron) if currently running
	- lock (then unlock) the cron to avoid concurrent api access (and response time degradation)
- schedule popup is now closed just before launching any action (Set Mode, Save, Restore and Remove)

#### Underground
- better manipulation of json data, in case of null or KO content (more log, no more bad effects)
- after restore, refresh only temp. & setPoint data, and merge restored schedule (speed up process)


## [beta 2] - 2018-02-25 - the 'ECC FIXES'

#### Improvements
- *install_apt.sh*<br/>
	1. restore the apt-get clean and update commands 

#### Fixes (many thanks to 'ecc')
- *install*<br/>
	1. cron is now  blocked when/while plugin status = NOK, or if your credentials are not set (login/password)<br/>
	2. check of the dependencies were incorrect, caused a permanent NOK status<br/>
	3. when no location is specified yet, it caused a error in receiving argument on InfosZonesE2.py<br/>

- *python to php*<br/>
	1. data could not be 'sent' by python, when some UTF8 characters was inside your zones names, or system names<br/>

- *php 5 vs 7*<br/>
	1. split function replaced by explode<br/>
	2. PHP7 restrictions on json : booleans built in python are now returned correctly formed<br/>


## [beta 1] - 2018-02-18
first publication
