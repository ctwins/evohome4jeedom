# evohome4jeedom

<img align="left" src="plugin_info/evohome_icon.png" width="120" style="padding-right:16px;">
This is a plugin for Jeedom 3.x and 4.x platform, regarding the Honeywell Evohome system.<br/>
State is : v0.4.3 - Opened Window & Day view on Horizontal Schedule & Full Jeedom V4 compatibility

Written in php, the Jeedom main langage, a bit of javascript, of course some html, and, at this time (from 0.3.0), a forked Python bridge (from the excellent python library "evohome" of watchforstock).<br/>
Great thanks to him. His implementation can be ***[found here](https://github.com/watchforstock/evohome-client)*** <br/>
*(\*) : see "Under the hood" of the changelog file, under revision 0.3.0*


<br/>Install from the Market where the plugin is published in stable status :)

Features covered first my needs to get a triggerable injection of full week scheduling in the main Evotouch console<br/>
The temperatures and setpoints are showed inside room (called also 'zone', 'area' or 'TH') components, with history availability<br/>
You can edit full schedule, one zone and full week at a time<br/>
NEW 0.4.3 - view and edition is now available at the point of view of a Day (all zones per day)<br/>
You can set the heatpoint : from a +/- detailed popup, or directly reset (at the predefined setting point).<br/>
More, a statistics panel on every TH widget could appear (requested from the Console)<br/>

As you will see, the configuration is very simple.<br/>

On the general properties page, you have to set your username and password, which pair is the account you have to create/created on
the official Honeywell web application (same as one linked with the phone's application).<br/>
In the same page, you can adjust the period of refresh, so full informations like temp. but also schedule for all the rooms are read.<br/>
After saving your credentials, a Synchronize button helps you to create all the components for ALL the locations you could have (usually, only one ;)<br/>

Easy scenarios settings (was previously by scripting) :<br/>
To change setting mode, restore schedule from file or change heatpoint of zone, just use the add action with the command you want,
and choose the right value in the selectable list)<br/>
Time limit is not settable for setting mode and changing heatpoint, as you have the possibility to plan as you want with the scenarios ;)<br/>
Please note as a schedule file is set in at least one scenario, it could not be deleted from the Console panel.

**Warning : some operations take times**, near 2mn for the schedule restoring, just be patient when you see the rolling picture, and/or, take an eye on the information which appears on the top of screen ;)

Last but not least, a ***[Jeedom blog is dedicated to this Evohome plugin](https://community.jeedom.com/t/plugin-evohome/12666)***<br/>
Don't hesitate to contact me in this blog for any question or problem you could encounter.
(***[previous forum](https://www.jeedom.com/forum/viewtopic.php?f=143&t=31647)***)<br/>

Enjoy !