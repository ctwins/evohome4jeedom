# evohome4jeedom

<img align="left" src="plugin_info/evohome_icon.png" width="120" style="padding-right:16px;">
This is a plugin for Jeedom 3.x and 4.x platform, regarding the Honeywell Evohome/Round T87RF/Lyric T6/T6R.<br/>
State is : v0.5.3 - Mobile relooking & Detailed informations

Written in php, the Jeedom main langage, 'a bit' of javascript, of course some html, and, at this time (from 0.3.0), a forked Python bridge (from the excellent python library "evohome" of watchforstock).<br/>
Great thanks to him (and contributors). This implementation can be ***[found here](https://github.com/watchforstock/evohome-client)*** <br/>
*(\*) : see "Under the hood" of the changelog file, under revision 0.3.0*


<br/>Install from the Market where the plugin is published in stable status.

Features covered first my needs to get a triggerable injection of full week scheduling in the main Evotouch console<br/>
The temperatures and setpoints are showed inside room (called also 'zone', 'area' or 'TH') components, with history availability<br/>
You can edit full schedule, one zone and full week at a time.<br/>
NEW 0.4.3 - view and edition is now available at the point of view of a Day (all zones per day)<br/>
You can set the heatpoint : from a +/- detailed popup, or directly reset (at the predefined setting point).<br/>
More, a statistics panel on every TH widget could appear (requested from the Console).<br/>
NEW 0.5.0 - Integration of the Lyric T6/T6R systems.<br/>
NEW 0.5.3 - Detailed cmd Informations appear in the Console and TH equipments (see the french doc).
<br/><br/><br/>
Some words about configuration (or see the french doc.) :<br/>

1> Evohome and Round T87RF :<br/>
On the general configuration page, choose the System Evohome, then set your username and password, which pair is the account you have to create/created on the official Honeywell web application (same as one linked with the phone's application).<br/>
2> Lyric T6/T6R :<br/>
A bit more complex, as the login uses the OAuth2 protocol :<br/>
1. Sign up at the Honeywell [developer home](https://developer.honeywellhome.com)
2. Click on 'Create New App', set name with 'Jeedom' (for example), and Callback URL with your jeedom url, ending by "/plugins/evohome/core/class/lyric.callback.php". That gives something like :<br/>
http://[ip-of-your-jeedom]/plugins/evohome/core/class/lyric.callback.php
3. Click on the app name to see the generated consumer key and consumer secret
4. Now, copy/paste these informations on the configuration page, after setting the System to Lyric, of course.
5. Click on Initialisation. This will open a new page of the Honeywell which invits you to set your Lyric credentials
6. Follow the request and accept
7. Finally, the Honeywell page should close, and a token is injected in the plugin :)

1,2> After saving your credentials, use the Synchronize button to create all the components for ALL the locations you could have (usually, only one ;)<br/>
In the same page, you can adjust the period of refresh (and more !), so full informations like temp. but also schedule for all the rooms are read.<br/>

Easy scenarios settings (was previously by scripting) :<br/>
To change setting mode, restore schedule from file or change heatpoint of zone, just use the add action with the command you want, and choose the right value in the selectable list)<br/>
Time limit is not settable for setting mode and changing heatpoint, as you have the possibility to plan as you want with the scenarios ;)<br/>
Please note as a schedule file is set in at least one scenario, it could not be deleted from the Console panel.

**Warning : some operations take time**, could be some minutes for the schedule restoring, just be patient when you see the rolling picture, and/or, take an eye on the information which appears on the top of screen ;)

Last but not least, a **[Jeedom forum is dedicated to this Evohome plugin](https://community.jeedom.com/t/plugin-evohome)** (***[previous forum](https://forum.jeedom.com/viewtopic.php?f=143&t=31647&sid=41c4acd4ffe5ecc1c4f120ecf7ce7569&start=200)***)<br/>
Don't hesitate to contact me in this blog for any question or problem you could encounter.
<br/>

Enjoy !