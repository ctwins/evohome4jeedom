<img align="left" src="../../plugin_info/evohome_icon.png" width="120" style="padding-right:16px;">
Plugin permettant l'intégration du système Evohome de Honeywell.

Basé sur l'excellente librairie "evohome" de watchforstock, **[disponible ici](https://github.com/watchforstock/evohome-client)** 
(fork embarqué depuis 0.3.0)<br/><br/><br/><br/><br/>


Possibilités
==
- affichage des températures et consignes actives sur les widget de chaque zone, le tout avec gestion d'historique (qui comprend également les consignes programmées)
- des statistiques minimalistes sont affichables sur les widgets des zones
- réglage du mode de présence, manullement (via la console), ou par scénario
- modifier les consignes manuellement via les widget ou par scénario
- possibilité de sauvegarder et charger des programmes hebdomadaires (manuellement ou par scénario)
- éditeur complet des programmes hebdomadaires

Depuis 0.4.0 : gère la multi-localisation et le Round-Thermostat<br/>
** /!\ ATTENTION - VOUS DEVEZ LANCER UNE SYNCHRONISATION LORS DE LA MISE A JOUR VERS CETTE VERSION /!\ ** <br/>
** /!\ Sauvegarde préalable hautement conseillée /!\ Contactez moi pour tout problème d'update **


Configuration du plugin
==

Sur la page principale, saisir login et mot de passe, tels que vous les avez définis sur le site officiel.<br/>
Cliquer alors sur le bouton Synchroniser :
- sur chaque localisation existante, une console va être créée, ainsi que les équipements pour chaque zone trouvée.<br/>
NB1 : le nommage des équipements utilise le préfixe modifiable "TH" + " " + [nom de la zone].<br/>
NB2 : les widgets sont rattachés aux objets parents comportant le même nom ou similaire que celui de la zone (A dans B ou B dans A, indépendant de la casse)<br/>
NB3 : expérimental : si vous disposez du plugin Virtuel, des composants sont ajoutés dans ce plugin (ce qui vous permet de profiter des informations dans l'application Jeedom ou autre bridge de votre smartphone).<br/>
**=> en cas d'erreur lors de la synchronisation, n'hésitez pas à me contacter via le forum**

Les autres réglages disponibles :<br/>
- choix de l'unité d'affichage des températures et consigne
- précision d'affichage
- mode d'affichage par défaut des programmes hebdomadaires
- activation du mode édition (des programmes)
- choix de l'affichage des choix des modes de présence (intégré à la console, ou déporté en popup)
- possibilité de forcer la lecture du système avant sauvegarde de la programmation (était utile avant la présence de l'éditeur intégré, afin de prendre en compte les modifications effectuées sur la console Evohome physique)
- période de lecture (des températures, mais aussi du programme hebdomadaire)
- possibilité de caler la période sur l'horloge (exemple : 15mn donnera HH:00, HH:15, etc)
NB : bien que le système de lecture ait été amélioré au fil des versions, il reste déconseillé de régler la période à 10mn pour éviter des erreurs à répétition
- Durée de rétention de l'historique (des températures, consignes réelles et programmées) : permet de régler toutes vos zones en une seule action


Utilisation
==
Tout est fait pour être intuitif, faites moi savoir si vous avez besoin d'explication détaillée.


Scénario
==

Le pilotage par scénario est très simple : il vous suffit d'ajouter une commande de type action, et choisir le paramètre dans la liste déroulante.<br/>
Soit :
- Réglage mode : pour le pilotage du mode de présence
- Set consigne : pour modifier une consigne<br/>
A noter que dans les deux premiers cas, le résultat est un changement permanent (aucun intérêt à prévoir une durée, justement par le fait que vous pilotez ces changements par scénario)

- Restaure : pour charger une programmation (sauvegardée au préalable via la console ou l'éditeur de programmes, sur le Dashboard)<br/>
A noter que lorsqu'un programme est utilisé dans un scénario, vous ne pouvez plus le supprimer depuis la console

- Restaurer la consigne à sa valeur programmée<br/>
Grâce au cas d'usage soulevé par **pykforum**, pour effectuer cette opération, vous devez créer ce bloc de code :<br/>

		$objParent = "NOM_OBJET_PARENT_DU_THERMOSTAT";
		$eqName = "NOM_EQUIPEMENT_THERMOSTAT";
		
		$eq = eqLogic::byObjectNameEqLogicName($objParent,$eqName);
		$zoneId = $eq[0]->getConfiguration('zoneId');
		$cmdCP = cmd::byString("#[$objParent][$eqName][Consigne programmée]#");
		$params = "auto#$zoneId#0#" . $cmdCP->execCmd() . "#null";
		
		$cmdSC = cmd::byString("#[$objParent][$eqName][Set Consigne]#");
		$cmdSC->execCmd($options = array('select' => $params));


Forum
==
N'hésitez pas à me (nous) rendre visite sur le **[blog dédié](https://www.jeedom.com/forum/viewtopic.php?f=143&t=31647)** pour toute question ou suggestion.


Changelog
==
Se trouve dans le fichier dédié (en anglais)