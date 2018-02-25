# Changelog - evohome4jeedom

## [beta 2] - 2018-02-25 - the 'ECC FIXES'

### Fixed (many thanks to 'ecc')
- *install*
	1. cron is now  blocked when/while plugin status = NOK, or if your credentials are not set (login/password)
	2. check of the dependencies were incorrect, caused a permanent NOK status
	3. when no location is specified yet, it caused an error in receiving argument on InfosZonesE2.py

- *python to php*
	1. data could not be 'sent' by python, when some UTF8 characters was inside the zones names, or location name

- *php 5 vs 7*
	1. split function replaced by explode
	2. PHP7 restrictions on json : booleans built in python are now returned correctly formed

### Improved
- *install_apt.sh*
	1. restore the apt-get clean and update commands 


## [beta 1] - 2012-02-18
first publication
