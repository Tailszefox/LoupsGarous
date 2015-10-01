#!/usr/bin/env python
# -*- coding: utf-8 -*-

import ConfigParser

class TestUnit():
    def __init__(self, chemin = None):
        self.variables = {}

        # Chargement des valeurs par défaut dans un premier temps
        self.chargeUnit("./tests/default.unit")

        # Chargement des valeurs de l'unit pour écraser celle par défaut
        if(chemin is not None):
            self.chargeUnit(chemin)

        print u"Variables de test chargées :"
        for nomVariable in self.variables:
            print u"\t{} = {}".format(nomVariable, self.variables[nomVariable])

        print

    def chargeUnit(self, chemin):
        unitFichier = ConfigParser.ConfigParser()
        
        if(len(unitFichier.read(chemin)) == 0):
            raise Exception("Impossible de lire le fichier de configuration {}".format(chemin))

        print u"Fichier de test {} chargé".format(chemin)

        for item in unitFichier.items(section = "unit"):
            itemName, itemValue = item

            self.variables[itemName] = itemValue