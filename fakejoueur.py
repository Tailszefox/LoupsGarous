#!/usr/bin/env python
# -*- coding: utf-8 -*-

# Cette classe représente un (faux) joueur de LG

import random
from copy import copy

class FakeJoueur():
    def __init__(self, irc, pseudo):
        self.irc = irc
        self.pseudo = pseudo
        self.role = None
        self.roleSecondaire = None
        self.estLoup = False
        self.estVoice = False
        self.precedent = None
        self.sePresenteMaire = False
        self.autreAmoureux = None
        self.canaux = ["#placeduvillage"]

        if(self.irc.connection.forcerGagnant == "nul"):
            self.vaQuitterPartie = True
        else:
            self.vaQuitterPartie = (random.randint(0, 100) <= self.irc.unitI("pourcentage_absence"))

    def __str__(self):
        return self.pseudo

    def __repr__(self):
        return self.pseudo

    def getJoueurs(self):
        return self.irc.connection.joueurs

    def getJoueursRandom(self):
        joueurs = self.getJoueurs()
        joueursCopy = copy(joueurs)
        random.shuffle(joueursCopy)

        return joueursCopy

    def estSur(self, canal):
        canal = canal.lower()

        for c in self.canaux:
            if(c.lower() == canal):
                return True

        return False

    # Messages

    def messageChan(self, message):
        if(not self.estVoice and self.irc.demarre):
            print "- {} n'est pas voice et ne peut donc pas dire : \"{}\"".format(self.pseudo, message)
            return

        self.irc.sendEvent("pubmsg", self.pseudo, "#placeduvillage", message)

    def messageChanLoup(self, message):
        self.irc.sendEvent("pubmsg", self.pseudo, "#loupsgarous", message)

    def messageChanParadis(self, message):
        self.irc.sendEvent("pubmsg", self.pseudo, "#paradis", message)

    def messageMdj(self, message):
        self.irc.sendEvent("privmsg", self.pseudo, "Maitredujeu", message)

    # Actions

    def rejoindreCanal(self, canal):
        if canal not in self.canaux:
            self.canaux.append(canal)
            self.irc.sendEvent("join", self.pseudo, canal)

    def quitterCanal(self, canal):
        try:
            self.canaux.remove(canal)
            print u"- {} - A quitté {}".format(self.pseudo, canal)
        except:
            raise Exception(u"Le joueur {} n'est pas sur le canal {}".format(self.pseudo, canal))

    def attribuerRole(self, message):
        self.role = message.strip()

        if("un loup garou" in message):
            self.estLoup = True

        self.messageChan("Je suis {} - Loup : {}".format(self.role, self.estLoup))

    def attribuerRoleSecondaire(self, message):
        self.roleSecondaire = message.strip()

        self.messageChan("J'ai un rôle secondaire : {}".format(self.roleSecondaire))

    def kicke(self, canal):
        try:
            self.canaux.remove(canal)
            print u"- {} - Kické de {}".format(self.pseudo, canal)
        except:
            raise Exception(u"Le joueur {} n'est pas sur le canal {}".format(self.pseudo, canal))

    def devientLoup(self):
        print u"- {} - Devient loup".format(self.pseudo)
        self.role = "un loup garou"
        self.estLoup = True

    def devientAmoureux(self, autreAmoureux):
        print u"- {} - Devient amoureux de {}".format(self.pseudo, autreAmoureux)
        self.autreAmoureux = autreAmoureux

    def meurt(self):
        print u"- {} - Est mort".format(self.pseudo)
        self.rejoindreCanal("#paradis")
        self.messageChanParadis("!roles")

        self.getJoueurs().remove(self)

    def donnerVoice(self):
        print u"- {} - Voicé".format(self.pseudo)
        self.estVoice = True

    def enleverVoice(self):
        print u"- {} - Dévoicé".format(self.pseudo)
        self.estVoice = False

    # Agissements Jeu

    def loupTuer(self):
        joueurs = self.getJoueursRandom()

        # On tue le premier joueur de la liste qui n'est pas loup
        for j in joueurs:
            if(j.estLoup):
                continue

            # Si on est amoureux, on ne tue pas son amoureux
            if(self.autreAmoureux is not None and self.autreAmoureux.lower() == j.pseudo.lower()):
                self.messageChanLoup("Je ne tue pas mon amoureux {} !".format(j.pseudo))
                continue

            self.messageChanLoup("!tuer {}".format(j.pseudo))
            break

    def maitreDemande(self):
        joueurs = self.getJoueursRandom()
        self.messageChanLoup("{}".format(joueurs[0].pseudo))

    def cupidonDemande(self):
        joueurs = self.getJoueursRandom()

        # 1er amoureux : Joueur au hasard
        if(self.irc.amoureux1 is None):
            joueurChoisi = joueurs[0].pseudo
        # 2ème amoureux : Joueur au hasard qui n'est pas le premier amoureux
        # et, si demandé, est dans un autre camp
        else:
            for j in joueurs:
                if(j.pseudo == self.irc.amoureux1[:self.irc.amoureux1.find("!")]):
                    continue
                if(self.irc.unitB("forcer_amour_villageois_loup") and self.irc.connection.getJoueur(self.irc.amoureux1[:self.irc.amoureux1.find("!")]).estLoup == j.estLoup):
                    print u"- Cupidon - {} est dans le camp du premier amoureux".format(j.pseudo)
                    continue

                joueurChoisi = j.pseudo
                break

        self.messageMdj(joueurChoisi)

    def voyanteDemande(self):
        joueurs = self.getJoueursRandom()
        
        # Demande le premier joueur qui n'est pas nous
        for j in joueurs:
            if(j is not self):
                self.messageMdj(j.pseudo)
                break

    def policierOuSalvateurDemande(self):
        joueurs = self.getJoueursRandom()

        for j in joueurs:
            if(j is not self and j is not self.precedent):
                self.precedent = j
                self.messageMdj(j.pseudo)
                break

    def chasseurDemande(self):
        joueurs = self.getJoueursRandom()

        for j in joueurs:
            if(j is not self):
                if(self.autreAmoureux is not None and self.autreAmoureux.lower() == j.pseudo.lower()):
                    print u"Le chasseur ne tue pas son amoureux {}.".format(j.pseudo)
                    continue
                self.messageMdj(j.pseudo)
                return

    def sorciereVieDemande(self, pseudoMort):
        # Si c'est nous et que la sorcière se sauve toujours
        if(pseudoMort.lower() == self.pseudo.lower() and self.irc.unitB("sorciere_vie_toujours_self")):
            self.messageMdj("oui")
            return

        if(random.randint(0, 100) <= (self.irc.unitI("pourcentage_sorciere_vie") * (self.irc.noJour+1))):
            self.messageMdj("oui")
            return

        self.messageMdj("non")

    def sorciereMortDemande(self):
        if(random.randint(0, 100) <= (self.irc.unitI("pourcentage_sorciere_mort") * (self.irc.noJour+1))):
            for j in self.getJoueursRandom():
                if(j is self):
                    continue

                if(self.autreAmoureux is not None and self.autreAmoureux.lower() == j.pseudo.lower()):
                    self.messageChan("Je ne tue pas mon amoureux {} !".format(j.pseudo))
                    continue

                self.messageMdj(j.pseudo)
                return

        self.messageMdj("non")

    def corbeauDemande(self):
        if(random.randint(0, 100) <= (self.irc.unitI("pourcentage_corbeau") * (self.irc.noJour+1))):
            for j in self.getJoueursRandom():
                if(j is self):
                    continue

                if(self.autreAmoureux is not None and self.autreAmoureux.lower() == j.pseudo.lower()):
                    self.messageChan("Je ne vote pas contre mon amoureux {} !".format(j.pseudo))
                    continue

                self.messageMdj(j.pseudo)
                return

        self.messageMdj("non")

    def sePresenterMaire(self):
        for i in range(1, random.randint(2, 6)):
            phrase = ""

            for x in range(0, 5):
                phrase += random.choice(self.irc.connection.listeMots).strip() + " "

            self.messageMdj(phrase)

    def voteMaire(self):
        # Si le joueur s'est présenté, il vote pour lui-même
        if(self.sePresenteMaire):
            self.messageChan("!voter {}".format(self.pseudo))
        # Sinon, vote au hasard pour un autre joueur
        else:
            candidat = random.choice(self.irc.candidats)
            self.messageChan("!voter {}".format(candidat))

    def designeSuccesseur(self):
        for j in self.getJoueursRandom():
            if(j is self):
                continue

            self.messageMdj(j.pseudo)

    def voteLapidation(self, egalite = False, declencheur = True):
        if(self.irc.connection.forcerGagnant == "villageois" and self.estLoup):
            self.voterBlanc()
            return
        if(self.irc.connection.forcerGagnant == "loups" and not self.estLoup):
            self.voterBlanc()
            return
        if(self.irc.connection.forcerGagnant == "amoureux" and self.irc.amoureux1 is not None and self.autreAmoureux is None):
            self.voterBlanc()
            return

        # On vote directement contre l'ange si il doit gagner
        if(self.irc.connection.forcerGagnant == "ange" and self.irc.ange is not None):
            for j in self.getJoueurs():
                if("ange" in j.role):
                    self.messageChan("!tuer {}".format(j.pseudo))
                    return

        joueurs = self.getJoueursRandom()

        # Le joueur peut ne pas voter
        if(random.randint(0, 100) < self.irc.unitI("pourcentage_non_vote")):
            self.messageChan("Je ne vote pas")
            self.voterBlanc()
            return

        # Chance pour un villageois de voter contre un loup
        if(not self.estLoup):
            pourcentageVoteLoup = self.irc.unitI("pourcentage_villageois_vote_loup") * self.irc.noJour
            voterContreLoup = random.randint(0, 100) <= (pourcentageVoteLoup)

            if(self.irc.connection.forcerGagnant == "villageois"):
                voterContreLoup = True

        # Risque pour un loup de voter contre un autre loup
        else:
            pourcentageVoteLoup = self.irc.unitI("pourcentage_loup_vote_loup") * self.irc.noJour
            voterContreLoup = random.randint(0, 100) <= (pourcentageVoteLoup)

            if(self.irc.connection.forcerGagnant == "loups"):
                voterContreLoup = False

        self.messageChan("Je suis loup : {} - Je vote contre un loup : {}% - {}".format(self.estLoup, pourcentageVoteLoup, voterContreLoup))

        for j in joueurs:
            if(j is not self):
                # Si égalité, on ne peut voter que pour les joueurs concernés
                if(egalite and j.pseudo.lower() not in self.irc.joueursEgalite):
                    self.messageChan("Je ne vote pas {} : il n'est pas dans la liste".format(j.pseudo))
                    continue

                # Si le loup ne va pas voter contre un loup, on zappe les loups
                if(self.estLoup and j.estLoup and not voterContreLoup):
                    self.messageChan("Je ne vote pas {} : je suis loup et lui aussi".format(j.pseudo))
                    continue

                # Si le villageois doit voter contre un loup et que j n'en est pas un, on zappe
                if(not self.estLoup and voterContreLoup and not j.estLoup):
                    self.messageChan("Je ne vote pas {} : je dois voter contre un loup".format(j.pseudo))
                    continue

                # Les amoureux ne votent pas entre eux
                if(self.autreAmoureux is not None and self.autreAmoureux.lower() == j.pseudo.lower()):
                    self.messageChan("Je ne vote pas contre mon amoureux {} !".format(j.pseudo))
                    continue

                if(declencheur):
                    self.messageChan("!tuer {}".format(j.pseudo))
                    return
                else:
                    self.messageChan("{}".format(j.pseudo))
                    return
                break

        # Si on est arrivé ici, c'est que le joueur ne va pas voter
        self.messageChan("Je n'ai personne contre qui voter.")
        self.voterBlanc()

    def voterBlanc(self):
        # Les joueurs ont 50% de chance de dire "!blanc" quand ils ne votent pas
        if(random.randint(0, 100) > 50):
            self.messageChan("!blanc")

    def mettreMessageMurs(self):
        if(random.randint(0, 100) <= self.irc.unitI("pourcentage_murs")):
            phrase = ""

            for x in range(0, 5):
                phrase += random.choice(self.irc.connection.listeMots).strip() + " "

            self.messageMdj(phrase)