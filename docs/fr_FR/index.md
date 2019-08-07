<img align="left" src="../img/evohome_icon.png" width="120" style="padding-right:16px;">
Plugin permettant l'intégration du système Evohome de Honeywell.

Basé sur l'excellente librairie "evohome" de watchforstock, **[disponible ici](https://github.com/watchforstock/evohome-client)** 
(fork embarqué depuis 0.3.0)<br/><br/><br/><br/><br/>



Possibilités
==
- affichage des températures et consignes actives sur les widget (TH) de chaque zone, le tout avec gestion d'historique (qui comprend également les consignes programmées)
- des statistiques minimalistes sont affichables sur les TH
- réglage du mode de présence, manuellement via le widget Console (Console) ou par scénario
- modifier les consignes manuellement via les TH ou par scénario
- possibilité de sauvegarder et charger des programmes hebdomadaires (manuellement ou par scénario)
- éditeur complet des programmes hebdomadaires

Depuis 0.4.0 : gère la multi-localisation et le Round-Thermostat<br/>
***/!\ ATTENTION - VOUS DEVEZ LANCER UNE SYNCHRONISATION LORS DE LA MISE A JOUR DE LA VERSION 0.3.x VERS 0.4.x***<br/>
***/!\ Sauvegarde préalable hautement conseillée. Contactez-moi pour tout problème d'update***


Configuration du plugin
==

Sur la page principale, saisir login et mot de passe, tels que vous les avez définis sur le site officiel.<br/>
Cliquer alors sur le bouton Synchroniser :
- sur chaque localisation existante, une Console va être créée, ainsi que les équipements pour chaque zone trouvée.<br/>
NB1 : le nommage des équipements utilise le préfixe modifiable "TH" + " " + [nom de la zone].<br/>
NB2 : les TH sont rattachés aux objets parents comportant le même nom ou similaire que celui de la zone (A dans B ou B dans A, indépendant de la casse)<br/>
NB3 : expérimental : si vous disposez du plugin Virtuel, des composants sont ajoutés dans ce plugin (ce qui vous permet de profiter des informations dans l'application Jeedom ou autre bridge de votre smartphone).<br/>
**=> en cas d'erreur lors de la synchronisation, n'hésitez pas à me contacter via le forum**


Les autres réglages disponibles :<br/>

- Console
  - choix de l'affichage des choix des modes de présence (intégré à la Console, ou déporté en popup)
  - possibilité de forcer la lecture du système avant sauvegarde de la programmation (était utile avant la présence de l'éditeur intégré, afin de prendre en compte les modifications effectuées sur la console Evohome physique)

- Thermostats
  - style de la barre de titre
  - unité d'affichage des températures et consigne
  - précision d'affichage
  - mode de réglage manuel des consignes : intégré (mode permanent), par popup (réglage de la durée)

- Programmes hebdomadaires
  - mode d'affichage par défaut (lors de l'action des boutons Pc et Ps)
  - activation du mode édition

- Historique
  - période de lecture (des températures, mais aussi du programme hebdomadaire)
  - possibilité de caler la période sur l'horloge (exemple : 15mn donnera HH:00, HH:15, etc.)
  NB : bien que le système de lecture ait été amélioré au fil des versions, il reste déconseillé de régler la période à 10mn pour éviter des erreurs à répétition
  - Durée de rétention de l'historique (des températures, consignes réelles et programmées) : permet de régler toutes vos zones en une seule action

Utilisation
==
Tout est fait pour être intuitif, faites-moi savoir si vous avez besoin d'explication détaillée !

Quelques détails :
- les boutons Pc et Ps sur les widgets signifient :
  - Pc : programmation courante
  - Ps : programmation de la sauvegarde (celle affichée dans la liste déroulante)
- un indicateur Batterie apparait dans le coin gauche de la barre de titre des TH, en cas de défaillance. Si la batterie est 100% HS (le thermostat ne répond plus), la température elle-même est remplacée par un icone indicatif
- si un mode statistique est activé, des flèches animées verte (vers le haut) et rouge (vers le bas) peuvent apparaitre à droite des températures. Signifient respectivement : température en hausse ou en baisse par rapport à la mesure précédente.
- l'édition des programmes hebdomadaires n'est disponible que sur l'affichage horizontal
- les valeurs min/max des consignes réglables sur chaque thermostat sont récupérées dans les données Honeywell. Pour information, ces valeurs sont réglables sur la console physique (par défaut 5/25).


Scénario
==

Le pilotage par scénario est très simple : il vous suffit d'ajouter une commande de type action, et choisir le paramètre dans la liste déroulante.<br/>
Soit :
- Réglage mode : pour le pilotage du mode de présence
- Set consigne : pour modifier une consigne<br/>
new 0.4.1 : le premier choix permet de revenir à la température programmée (remplace le script documenté dans le **[forum](https://www.jeedom.com/forum/viewtopic.php?f=143&t=31647&p=736308#p736308)**)<br/>
=> pour ces réglages, le résultat est un changement permanent (aucun intérêt à prévoir une durée, justement par le fait que ces changements sont pilotés par scénario !)

- Restaure : pour charger une programmation (sauvegardée au préalable via la Console ou l'éditeur de programmes, sur le Dashboard)<br/>
=> lorsqu'un programme est utilisé dans un scénario, vous ne pouvez plus le supprimer depuis la Console


Forum
==
N'hésitez pas à visiter le **[forum dédié](https://www.jeedom.com/forum/viewtopic.php?f=143&t=31647)** pour toute question ou suggestion.


Changelog
==
Se trouve dans le fichier dédié (en anglais), et accessible en standard via le bouton dédié sur les écrans de Jeedom : market, configuration générale du plugin, tableau des versions.