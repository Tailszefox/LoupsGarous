#!/usr/bin/env python2
# -*- coding: utf-8 -*-

import ConfigParser

config = ConfigParser.ConfigParser()

config.read("./prefs.ini")

pseudo = config.get("prefs", "pseudo")
mdp = config.get("prefs", "mdp")
chanJeu = config.get("prefs", "chanJeu")
chanParadis = config.get("prefs", "chanParadis")
chanLoups = config.get("prefs", "chanLoups")
serveur = config.get("prefs", "serveur")
sslServeur = config.get("prefs", "ssl")
port = config.getint("prefs", "port")

print u"Configuration :"
print u"\tPseudo :", pseudo
print u"\tMot de passe :", "oui" if len(mdp) > 0 else "non"
print u"\tServeur :", serveur + ":" + str(port)
print u"\tSSL:", "oui" if sslServeur == "True" else "non"
print u"\tCanal de jeu :", chanJeu
print u"\tCanal des loups :", chanLoups
print u"\tCanal du paradis :", chanParadis
print
