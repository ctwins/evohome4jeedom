# evohome4jeedom
This is a plugin for Jeedom 3.0 platform, regarding the Honeywell Evohome system.<br/>
State is : v0.1.2 - dashboard version 1 - FIX #2

Written in php, the Jeedom main langage, a bit of javascript and of course some html, and, at this time,  some python bridges because all will be nothing without the excellent python library "evohome" from watchforstock.<br/>
Great thanks to him and it's implementation you can find here : https://github.com/watchforstock/evohome-client

Features just cover my needs to get a triggerable injection of full week scheduling in the main evotouch console.<br/>
Of course, the temperatures and setpoints are showed inside room components, with history availability.<br/>
One connection is made per 10 mn to obtain full informations like temp. but also schedule for all the rooms.

On the general properties page, you have to set your username and password, which pair is the account you have to create/created on the official Honeywell web application (same as one linked with the phone's application).


Install and try, with your favorite FTP client. You must have the possibility to remove the plugin in case of general crash.<br/>
So, to install from the downloaded zip file :<br/>
1. Follow the installation by zip file, as seen in botom of https://jeedom.github.io/core/en_US/plugin
2. Just unzip in a folder named 'evohome' inside the plugins folder, then grant to www-data for owner and group part, with the "rwx" rights, set "r-x" for others (or 0775 when under www-data user)<br/>


As you will see, the configuration is very simple.<br/>
**Warning : some operations take times, more than 30 seconds for the setting mode, just be patient when you see the rolling picture..**

Tip to trigger setting mode or restore from file, is to add 2 lines of code inside your scenario, like that :
- Setting mode, with xxx is the ID of the setmode action, inside your Console component :
```php
$cmd = cmd::byId(xxx);
$cmd->execCmd($options=array(evohome::ARG_CODE_MODE=>evohome::CODE_MODE_ECO), $cache=0);
```
with the setting here of the Economy mode.<br/>
Settings available :<br/>
CODE_MODE_AUTO, CODE_MODE_OFF, CODE_MODE_ECO, CODE_MODE_AWAY, CODE_MODE_DAYOFF, CODE_MODE_CUSTOM<br/>
I have chosen to not set the time limit by the possibilities to plan as you want with the scenarios ;)

- Restore scheduling, with yyy is the ID of the restore action, inside your Console component :
```php
$cmd=cmd::byId(yyy);
$cmd->execCmd($options=array(evohome::ARG_FILE_ID=> zzz), $cache=0);
```
and zzz, here, is the id of your file, which can be find on the popup of saved files, by your browser tool ;)
<br/><br/>

Last but not least, a Jeedom blog is dedicated to the Evohome plugin (this one or incoming others..) here :<br/>
https://www.jeedom.com/forum/viewtopic.php?f=143&t=31647

More detailed explanations will come later, when I will understand how the docs can be built :(<br/>
Waiting for that, don't hesitate to contact me in the blog above.

Enjoy !
