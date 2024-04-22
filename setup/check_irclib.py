#!/usr/bin/env python2
# -*- coding: utf-8 -*-

import sys

try:
    import irclib
    print("Le module irclib est installé.")
except ImportError:
    print("Le module irclib n'est pas installé. Veuillez installer le module pour la version actuelle de Python 2.")
    print("Si pip est installé, vous pouvez exécuter la commande suivante :")
    print("pip2 install https://downloads.sourceforge.net/project/python-irclib/python-irclib/0.4.8/python-irclib-0.4.8.tar.gz")
    sys.exit(1)
