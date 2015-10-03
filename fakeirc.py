#!/usr/bin/env python
# -*- coding: utf-8 -*-

# Ces classes sont destinées à tester le bot, en simulant un serveur IRC

from joueur import Joueur

from time import sleep
import random
import functools
import threading
import string
import ipdb
import traceback
import sys

class FakeIrc():
    def __init__(self, server, pseudo, description):
        self.connection = Connection(self)

        print u"Initialisé"

    def start(self):
        self.connection.demarrerPartie()

    def sendEvent(self, sendTo, source, target, arguments = None):
        event = Event(source, target, arguments)

        method = getattr(self, "on_" + sendTo)
        method(self.connection, event)

    def unit(self, variableName):
        try:
            return self.testUnit.variables[variableName]
        except KeyError:
            raise Exception("La variable {} n'est pas définie dans l'unité de test".format(variableName))

    # Retourne un int
    def unitI(self, variableName):
        return int(self.unit(variableName))

    # Retourne un bool
    def unitB(self, variableName):
        value = self.unit(variableName)
        return (value.strip().lower() == "true")

    # Retourne un array
    def unitA(self, variableName):
        value = self.unit(variableName)

        if(len(value.strip()) == 0):
            return []

        arrayReturn = []

        for v in value.split(","):
            arrayReturn.append(v.strip())

        return arrayReturn

class Connection():
    def __init__(self, irc):
        self.irc = irc
        self.noPartie = 0
        self.victoires = {}
        self.listeMots = list(open('/usr/share/dict/french'))

        forcerGagnant = self.irc.unit("forcer_gagnant")
        if(len(forcerGagnant.strip()) == 0):
            self.forcerGagnant = None
        else:
            self.forcerGagnant = forcerGagnant

    def execute_delayed(self, wait, function, arguments=()):
        function = functools.partial(function, *arguments)

        shortWait = wait/100.0

        delay = Delay(shortWait, function)
        delay.start()

    def getJoueur(self, pseudo):
        for j in self.joueurs:
            if(j.pseudo.lower() == pseudo.lower()):
                return j

        print "Joueur {} non trouvé".format(pseudo)
        return None

    def demarrerPartie(self):
        self.noPartie += 1
        nbParties = self.irc.unitI("nombre_parties")

        if(self.noPartie > nbParties):
            print u"--- Partie {}/{} - Terminé".format(nbParties, nbParties)
            print u"--- Victoires :"

            for camp in self.victoires:
                print u"---- {} : {}".format(camp, self.victoires[camp])
            return

        nbJoueurs = random.randint(self.irc.unitI("nombre_joueurs_min"), self.irc.unitI("nombre_joueurs_max"))
        print u"--- Partie {}/{} - {} joueurs".format(self.noPartie, nbParties, nbJoueurs)
        print u"--- Gagnants forcés : {}".format(self.forcerGagnant)

        self.joueurs = []

        for x in range(0, nbJoueurs):
            while True:
                nomAleatoire = random.choice(self.listeMots)
                nomAleatoire = nomAleatoire.lower().strip()
                nomAleatoire = filter(str.isalpha, nomAleatoire)

                if(self.getJoueur(nomAleatoire) is None):
                    break

            self.joueurs.append(Joueur(self.irc, "{}".format(nomAleatoire)))

        self.joueurs[0].messageChan("!jouer")

    def ajouterVictoire(self, message):
        if("VICTOIRE_VILLAGEOIS" in message):
            campGagnant = "villageois"
        elif("VICTOIRE_LOUPS_" in message):
            campGagnant = "loups"
        elif("VICTOIRE_AMOUREUX" in message):
            campGagnant = "amoureux"
        elif("MATCH_NUL" in message):
            campGagnant = "nul"
        elif("JOUEUR_DESIGNE_ETAIT_ANGE" in message):
            campGagnant = "ange"

        try:
            self.victoires[campGagnant] += 1
        except KeyError:
            self.victoires[campGagnant] = 1

    #
    # Évènements envoyés par le bot
    # 

    def privmsg(self, destination, message):
        print u"- BOT - Message pour {}".format(destination)

        # Message pour un canal
        if(destination.startswith("#")):

            # Les joueurs peuvent annoncer leur participation
            if("DIRE_PARTICIPER" in message or "PAS_ASSEZ_DE_JOUEURS" in message):
                for j in self.joueurs:
                    j.messageChan("!participer")

            # Les loups peuvent tuer
            elif("INSTRUCTIONS_LOUPS" in message):
                if(self.forcerGagnant == "villageois" or (self.irc.ange is not None and self.forcerGagnant == "ange")):
                    return

                loupsPresents = []

                # On fait voter le loup amoureux si les amoureux doivent gagner
                if(self.irc.connection.forcerGagnant == "amoureux" and self.irc.amoureux1 is not None):
                    for j in self.joueurs:
                        if(j.estLoup and j.estSur("#LoupsGarous") and j.autreAmoureux is not None):
                            j.loupTuer()
                            return

                for j in self.joueurs:
                    if(j.estLoup and j.estSur("#LoupsGarous")):
                        loupsPresents.append(j)

                # On fait jouer un loup au hasard
                if(len(loupsPresents) > 0):
                    random.choice(loupsPresents).loupTuer()

            # Appel du maître chanteur
            elif("APPEL_MAITRE" in message):
                for j in self.joueurs:
                    if(j.roleSecondaire is not None and j.roleSecondaire == "le maître-chanteur"):
                        j.maitreDemande()
                        return

            # Les candidats au poste de maire peuvent se présenter
            elif("INSTRUCTIONS_MAIRE" in message):
                for j in self.joueurs:
                    if(random.randint(0, 100) <= self.irc.unitI("pourcentage_maire")):
                        j.sePresenterMaire()
                        j.sePresenteMaire = True
                    else:
                        j.sePresenteMaire = False

            # Les votes pour le maire sont ouverts
            elif("DEBUT_VOTE_MAIRE" in message):
                for j in self.joueurs:
                    j.voteMaire()

            # Les villageois peuvent lapider
            elif("COMMENT_LAPIDER" in message):
                for j in self.joueurs:
                    j.voteLapidation()

            # Égalité au premier tour de lapidation
            elif("PREMIERE_EGALITE" in message):
                for j in self.joueurs:
                    j.voteLapidation(egalite = True)

            # Le maire doit départager
            elif("MAIRE_DEPARTAGE " in message):
                maire = self.getJoueur(self.irc.maire[:self.irc.maire.find("!")])
                maire.voteLapidation(egalite = True, declencheur = False)

            # Murs-Murs
            elif("INSTRUCTIONS_MURS" in message):
                for j in self.joueurs:
                    j.mettreMessageMurs()
              
            # Spiritisme
            elif("SPR_" in message):
                for j in joueurs:
                    if(j.estVoice):
                        joueurDire = j
                        break

                if("SPR_ROLEEXISTE_0" in message):
                    joueurDire.messageChan(str(random.randint(1, 4)))
                elif("SPR_MEMECAMP_" in message or "SPR_ESTSV_0" in message):
                    while True:
                        j1 = random.sample(self.joueurs, 1)[0].pseudo
                        j2 = random.sample(self.joueurs, 1)[0].pseudo

                        if(j1 != j2):
                            break

                    joueurDire.messageChan(j1)
                    joueurDire.messageChan(j2)
                elif("SPR_NOMBREROLES_0" in message):
                    joueurDire.messageChan(str(random.randint(1, 3)))

            # Un joueur est mort suite à une crise cardiaque
            elif("CRISE_CARDIAQUE " in message):
                self.getJoueur(message[17:]).meurt()

            # Fin de la partie
            elif("VICTOIRE_" in message or "MATCH_NUL" in message or "JOUEUR_DESIGNE_ETAIT_ANGE" in message):
                self.ajouterVictoire(message)

        # Message pour un joueur
        elif(self.getJoueur(destination) is not None):
            joueur = self.getJoueur(destination)

            # Attribution du rôle
            if("DONNER_ROLE " in message):
                joueur.attribuerRole(message[12:])

            # Attribution du rôle secondaire
            if("DONNER_ROLE_SUPPLEMENTAIRE " in message):
                joueur.attribuerRoleSecondaire(message[27:])

            # Appel Cupidon
            if("DEMANDE_CUPIDON_" in message):
                joueur.cupidonDemande()

            # Annonce amoureux
            if("MESSAGE_AMOUREUX" in message):
                joueur.devientAmoureux(message[18:])

            # Appel voyante
            elif("DEMANDE_VOYANTE" in message):
                joueur.voyanteDemande()

            # Appel policier ou salvateur
            elif("DEMANDE_POLICIER" in message or "DEMANDE_SALVATEUR" in message):
                joueur.policierOuSalvateurDemande()

            # Appel chasseur
            elif("DEMANDE_CHASSEUR" in message):
                joueur.chasseurDemande()

            # Appel sorcière pour potion guérison
            elif("SORCIERE_POTION_GUERISON" in message):
                pseudoMort = message[26:]
                joueur.sorciereVieDemande(pseudoMort)

            # Appel sorcière pour potion mort
            elif("SORCIERE_POTION_POISON" in message):
                joueur.sorciereMortDemande()

            # Appel corbeau
            elif("DEMANDE_CORBEAU" in message):
                joueur.corbeauDemande()

            # Le joueur (traitre ou enfant loup) devient loup
            elif("MESSAGE_ENFANT" in message or "MESSAGE_TRAITRE" in message):
                joueur.devientLoup()

            # Le maire est mort et doit désigner son successeur
            elif("MESSAGE_MORT_MAIRE" in message):
                joueur.designeSuccesseur()

            # Le joueur est mort
            elif("MESSAGE_AU_MORT" in message):
                joueur.meurt()

        # Message pour services
        else:
            pass

    def join(self, canal):
        print u"- BOT - Rejoint {}".format(canal)

    def mode(self, canal, mode):
        print u"- BOT - Mode {} sur {}".format(mode, canal)

        if(("+v" in mode or "-v" in mode) and canal.lower() == "#placeduvillage"):
            voice, pseudo = mode.split()
            print u"- BOT - Voice {} sur {}".format(voice, pseudo)

            joueur = self.getJoueur(pseudo)

            if(joueur is not None):
                if("+v" in voice):
                    self.getJoueur(pseudo).donnerVoice()
                elif("-v" in voice):
                    self.getJoueur(pseudo).enleverVoice()

    def invite(self, pseudo, canal):
        print u"- BOT - Invitation de {} sur {}".format(pseudo, canal)

        joueur = self.getJoueur(pseudo)

        try:
            joueur.rejoindreCanal(canal)
        except:
            print u"Le joueur {} ne fait plus partie du jeu".format(pseudo)

    def kick(self, canal, pseudo):
        print u"- BOT - Kick de {} du canal {}".format(pseudo, canal)

        joueur = self.getJoueur(pseudo)
        joueur.kicke(canal)

    def who(self, canal):
        #ipdb.set_trace()
        if(canal.lower() == "#placeduvillage"):
            for j in self.joueurs:
                if(not j.vaQuitterPartie):
                    self.irc.sendEvent("whoreply", "", "Maitredujeu", ["", "", "", "", j.pseudo])

    def disconnect(self, message):
        pass
    def close(self):
        pass

class Event():
    def __init__(self, source, target, arguments = None):
        self._source = "{}!test@example.com".format(source)
        self._target = target

        if(arguments is not None):
            if(type(arguments) is str):
                self._arguments = [arguments]
            else:
                self._arguments = arguments

    def arguments(self):
        return self._arguments

    def source(self):
        return self._source

    def target(self):
        return self._target

class Delay(threading.Thread):
    def __init__(self, delay, toRun):
        threading.Thread.__init__(self)
        self.toRun = toRun
        self.delay = delay

    def run(self):
        sleep(self.delay)

        try:
            self.toRun()
        except Exception as e:
            # Toute exception dans un thread fait quitter immédiatement
            print u"Exception dans thread"
            print(sys.exc_info()[1])
            traceback.print_exc()
            sys.exit()