#!/usr/bin/env python2
# -*- coding: utf-8 -*-

import sys

try:
    import irc.client as irclib
    print("Le module irc est installé.")
except ImportError:
    print("Le module irc n'est pas installé. Veuillez installer le module pour la version actuelle de Python 2.")
    print("Si pip est installé, vous pouvez exécuter la commande suivante :")
    print("pip2 install git+https://github.com/jaraco/irc.git@c4097183e57ffef083e0fc185a377eeca7454725")
    sys.exit(1)
