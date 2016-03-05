<?php
// Alias des pseudos
include('./stats_alias.php');

// Incrémente $arr[$key], après l'avoir initialisé
function increment(&$arr, $key, $useAlias=true)
{
    if($useAlias)
    {
        global $alias;
        $key = strtolower($key);

        // L'alias existe, on l'utilise
        if(array_key_exists($key, $alias))
        {
            $key = $alias[$key];
        }
    }

    if(array_key_exists($key, $arr))
        $arr[$key]++;
    else
    {
        $arr[$key] = 1;
    }
}

function pourcentage($nombre, $total, $round=0)
{
    $p = ($nombre*100)/$total;
    return round($p, $round).'%';
}

function formatRole($role, $nb)
{
    switch($role)
    {
        case 'loup':
            return ngettext('loup garou', 'loups garous', $nb);
            
        case 'villageois':
            return ngettext('simple villageois', 'simples villageois', $nb);
            
        case 'voyante':
            return ngettext('voyante', 'voyantes', $nb);
            
        case 'chasseur':
            return ngettext('chasseur', 'chasseurs', $nb);
            
        case 'idiot':
            return ngettext('idiot', 'idiots', $nb);
            
        case 'salvateur':
            return ngettext('salvateur', 'salvateurs', $nb);
            
        case 'ancien':
            return ngettext('ancien', 'anciens', $nb);
            
        case 'cupidon':
            return ngettext('Cupidon', 'Cupidons', $nb);
            
        case 'fille':
            return ngettext('petite fille', 'petites filles', $nb);
            
        case 'sorciere':
            return ngettext('sorcière', 'sorcières', $nb);
            
        case 'policier':
            return ngettext('policier', 'policiers', $nb);
            
        case 'corbeau':
            return ngettext('corbeau', 'corbeaux', $nb);
            
        case 'enfant':
            return ngettext('enfant loup', 'enfants loups', $nb);

        case 'ange':
            return ngettext('ange', 'anges', $nb);

        case 'maitre':
            return ngettext('maître-chanteur', 'maîtres-chanteurs', $nb);

        default:
            return ngettext('rôle inconnu', 'rôles inconnus', $nb);
    }
}

// Années disponibles pour le calcul des stats
$annees = array("2011", "2012", "2013-2014", "2015", "2016");

if(isset($_GET['annee']) && in_array($_GET['annee'], $annees))
    $annee = strval($_GET['annee']);
else
    $annee = "-1";

$dir = new GlobIterator('./logs/*.xml');

// Initialisation
$totalParties = 0;

$totalJoueurs = 0;
$joueursParticipations = array();
$joueursSurvies = array();
$joueursVictoires = array();
$joueursDefaites = array();
$joueursRoleLoup = array();
$joueursRoleVillageois = array();
$joueursRoleSimple = array();
$pseudos = array();

$joueursVotesContreLoups = array();
$joueursVotesBlanc = array();
$joueursVotesTotal = array();

$victoiresVillageois = 0;
$victoiresLoups = 0;
$victoiresAmoureux = 0;
$victoiresAnge = 0;
$victoiresPersonne = 0;

$personnalites = array();
$roles = array();

$totalMessages = 0;
$totalTours = 0;
$totalLapidations = 0;
$totalLapidationsEgalitees = 0;

$totalPhasesPolicier = 0;
$totalPhasesCorbeau = 0;
$totalPhasesVoyante = 0;
$totalPhasesVoyanteLoups = 0;
$totalPhasesSalvateur = 0;
$totalPhasesSalvateurSauves = 0;
$totalPhasesSpiritisme = 0;

$totalVictimesLoups = 0;
$victimesLoups = array();

$totalVictimesLapidation = 0;
$totalVictimesLapidationAncien = 0;
$totalVictimesLapidationLoups = 0;
$totalVictimesLapidationIdiot = 0;
$victimesLapidation = array();
$victimesLapidationVillageois = array();

$totalVictimesChasseur = 0;
$totalVictimesChasseurLoups = 0;

$totalVictimesCrises = 0;

$totalVictimesSorciere = 0;
$totalVictimesSorciereLoups = 0;

$totalVictimesAmoureux = 0;
$totalVictimesAmoureuxLoups = 0;

$maires = array();
$totalElectionsMaire = 0;
$totalElectionsMaireLoup = 0;

$totalTraitres = 0;
$totalEnfants = 0;
$totalMessagesMurs = 0;

// Ouverture de chacun des logs
foreach($dir as $file)
{
    if($file->isFile())
    {
        $filename = $file->getFilename();

        // Ouverture du log
        $log = @simplexml_load_file('./logs/' . $filename);

        if($log === false)
        {
            $logRaw = file_get_contents('./logs/' . $filename);

            // Retirer les caractères Unicode invalides
            // http://www.phpwact.org/php/i18n/charsets
            $logRaw = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $logRaw);
            $log = simplexml_load_string($logRaw);

            // Erreur, on oublie le log et on passe au suivant
            if($log === false)
            {
                continue;
            }
        }

        // Remplissage du tableau des pseudos pour toutes les années
        foreach($log->joueurs->joueur as $joueur)
        {
            $pseudoJoueur = strval($joueur);
            $pseudos[strtolower($pseudoJoueur)] = $pseudoJoueur;
        }

        // Analyse de la date
        $date = DateTime::createFromFormat("d/m/y H:i:s", $log->date);

        // On passe au suivant si l'année n'est pas celle demandée
        if($annee != "-1" && strpos($annee, $date->format("Y")) === FALSE)
        {
            continue;
        }

        // STATS DE BASE

        $totalParties++;

        // Personnalité
        $personnalite = strval($log->personnalite);

        if(strpos($personnalite, ' (') !== FALSE)
            $personnalite = strstr($personnalite, ' (', true);

        increment($personnalites, $personnalite, false);

        // Messages sur le chat
        $totalMessages += count($log->xpath('//chat[@mp="false"]'));

        // RÔLES ET PARTICIPANTS

        $participantsPartie = array();
        foreach($log->joueurs->joueur as $joueur)
        {
            $totalJoueurs++;

            $role = strval($joueur['role']);
            $pseudoJoueur = strval($joueur);
            
            $pseudos[strtolower($pseudoJoueur)] = $pseudoJoueur;
            $participantsPartie[strtolower($pseudoJoueur)] = $role;
            increment($joueursParticipations, $pseudoJoueur);
            increment($roles, $role);
        }

        // Copie des participants qu'on gardera jusqu'à la fin
        $participantsPartieInit = $participantsPartie;

        // Traître devenu loup
        $traitre = $log->xpath('//action[@type="traitre"]');

        if($traitre)
        {
            $totalTraitres++;
            $participantsPartie[strtolower(strval($traitre[0]))] = "loup";
        }

        // Enfant devenu loup
        $enfant = $log->xpath('//action[@type="enfant"]');

        if($enfant)
        {
            $totalEnfants++;
            $participantsPartie[strtolower(strval($enfant[0]))] = "loup";
        }

        // Réanalyse des rôles maintenant qu'on sait qui était loup
        foreach($participantsPartie as $pseudo => $role)
        {
            if($role == "loup")
                increment($joueursRoleLoup, $pseudo);
            else
            {
                increment($joueursRoleVillageois, $pseudo);

                if($role == "villageois")
                    increment($joueursRoleSimple, $pseudo);
            }
        }

        // PHASES DU JEU

        // Tours
        foreach($log->xpath("//tour") as $tour)
        {
            $totalTours++;
        }

        // Décision des loups
        foreach($log->xpath("//action[@type='loup']") as $victimeNode)
        {
            $victime = strval($victimeNode);

            if(!empty($victime))
            {
                $totalVictimesLoups++;
                increment($victimesLoups, $victime);
            }
        }

        // Lapidation
        foreach($log->xpath("//votes[@type='lapidation']") as $lapidation)
        {
            $totalLapidations++;

            if($lapidation->xpath("./resultat[@type='egalite']"))
                $totalLapidationsEgalitees++;

            // Pour chacun des votes
            foreach($lapidation->xpath("./votant") as $vote)
            {
                $votant = strtolower(strtok(strval($vote), '!'));

                // On ignore les votes corbeaux
                if($votant != "corbeau1" && $votant != "corbeau2")
                {
                    $votePour = strval($vote->attributes()->vote);

                    if(empty($votePour))
                    {
                        increment($joueursVotesBlanc, $votant);
                    }
                    else if($participantsPartie[$votant] != "loup")
                    {
                        increment($joueursVotesTotal, $votant);

                        if($participantsPartie[$votePour] == "loup")
                            increment($joueursVotesContreLoups, $votant);
                    }
                }
            }
        }

        // Voyante
        foreach($log->xpath("//action[@type='voyante']") as $voyanteNode)
        {
            $totalPhasesVoyante++;

            if($voyanteNode->attributes()->role == "loup")
                $totalPhasesVoyanteLoups++;
        }

        // Policier
        $totalPhasesPolicier += count($log->xpath('//action[@type="policier"]'));

        // Corbeau
        $totalPhasesCorbeau += count($log->xpath('//action[@type="corbeau"]'));

        // Salvateur
        foreach($log->xpath('//action[@type="salvateur"]') as $node)
        {
            $totalPhasesSalvateur++;

            $prochaineVictimeLoup = $node->xpath('following-sibling::action[@type="loup"][1]')[0];

            if(strtolower(strval($node)) == strtolower(strval($prochaineVictimeLoup)))
                $totalPhasesSalvateurSauves++;
        }

        // Murs murs
        foreach($log->xpath("//murs") as $mursNode)
        {
            $totalMessagesMurs += count($mursNode->xpath("./message"));
        }

        // Séance de spiritisme
        foreach($log->xpath("//spr") as $node)
        {
            $totalPhasesSpiritisme++;
        }

        // Élection du maire
        if($maireNode = $log->xpath("//votes[@type='maire']"))
        {
            $totalElectionsMaire++;

            $maire = strval($maireNode[0]->xpath("resultat")[0]);

            if(!empty($maire))
                increment($maires, $maire);
            
            if($log->xpath("//joueur[@role='loup' and text()='".$maire."']"))
                $totalElectionsMaireLoup++;
        }

        // MORTS

        // Victimes de la nuit
        foreach ($log->xpath("//action[@typeMort='nuit']") as $victimeNode)
        {
            $victime = strval($victimeNode);

            if(!empty($victime))
            {
                unset($participantsPartie[$victime]);
            }
        }

        // Victimes de la lapidation
        foreach($log->xpath("//action[@typeMort='lapidation']") as $victimeNode)
        {
            $victime = strval($victimeNode);

            if(!empty($victime))
            {
                $totalVictimesLapidation++;

                if($victimeNode->attributes()->role == "loup")
                    $totalVictimesLapidationLoups++;
                else
                {
                    if($victimeNode->attributes()->role == "ancien")
                        $totalVictimesLapidationAncien++;
                    increment($victimesLapidationVillageois, $victime);
                }

                increment($victimesLapidation, $victime);
                unset($participantsPartie[strtolower($victime)]);
            }
        }

        // Idiot victime de la lapidation
        foreach($log->xpath("//action[@typeMort='idiot']") as $victimeNode)
        {
            $totalVictimesLapidationIdiot++;

            $victime = strval($victimeNode);
            increment($victimesLapidation, $victime);
        }

        // Victimes du chasseur
        foreach($log->xpath("//action[@typeMort='chasseur']") as $victimeNode)
        {
            $victime = strval($victimeNode);

            if(!empty($victime))
            {
                $totalVictimesChasseur++;

                if($victimeNode->attributes()->role == "loup")
                    $totalVictimesChasseurLoups++;

                unset($participantsPartie[strtolower($victime)]);
            }
        }

        // Victimes de la sorcière
        foreach($log->xpath("//action[@type='sorciereMort']") as $victimeNode)
        {
            $victime = strval($victimeNode);

            if(!empty($victime))
            {
                $totalVictimesSorciere++;

                if($log->xpath("//action[@type='mort' and @role='loup' and text()='".$victime."']"))
                    $totalVictimesSorciereLoups++;

                unset($participantsPartie[strtolower($victime)]);
            }
        }

        // Victimes amoureux
        foreach($log->xpath("//action[@typeMort='amoureux']") as $victimeNode)
        {
            $victime = strval($victimeNode);

            if(!empty($victime))
            {
                $totalVictimesAmoureux++;

                if($victimeNode->attributes()->role == "loup")
                    $totalVictimesAmoureuxLoups++;

                unset($participantsPartie[strtolower($victime)]);
            }
        }

        // Crises cardiaques
        foreach ($log->xpath("//action[@typeMort='absent']") as $victimeNode)
        {
            $victime = strval($victimeNode);

            if(!empty($victime))
            {
                $totalVictimesCrises++;

                unset($participantsPartie[strtolower($victime)]);
            }
        }

        // Gagnants
        switch($log->gagnant)
        {   
            case 'villageois':
                foreach($participantsPartieInit as $pseudo => $role)
                {
                    if($role != 'loup')
                        increment($joueursVictoires, $pseudo);
                    else
                        increment($joueursDefaites, $pseudo);
                }

                $victoiresVillageois++;
                break;
            
            case 'loups_0':
            case 'loups_1':
                foreach($participantsPartieInit as $pseudo => $role)
                {
                    if($role == 'loup')
                        increment($joueursVictoires, $pseudo);
                    else
                        increment($joueursDefaites, $pseudo);
                }

                foreach($participantsPartie as $pseudo => $role)
                {
                    if($role != 'loup')
                        unset($participantsPartie[$pseudo]);
                }

                $victoiresLoups++;
                break;

            case 'amoureux':
                foreach($participantsPartieInit as $pseudo => $role)
                {
                    if(array_key_exists($pseudo, $participantsPartie))
                        increment($joueursVictoires, $pseudo);
                    else
                        increment($joueursDefaites, $pseudo);
                }
                $victoiresAmoureux++;
                break;

            case 'ange':
                foreach($participantsPartieInit as $pseudo => $role)
                {
                    if($role != 'ange')
                        increment($joueursVictoires, $pseudo);
                    else
                        increment($joueursDefaites, $pseudo);
                }

                $victoiresAnge++;
                break;
                
            case 'personne':
                foreach($participantsPartieInit as $pseudo => $role)
                {
                    increment($joueursDefaites, $pseudo);
                }

                $victoiresPersonne++;
                break;
        }

        // Nombre de victoires par joueur
        foreach ($participantsPartie as $pseudo => $role)
        {
            increment($joueursSurvies, $pseudo);
        }
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Loups-Garous - Statistiques</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link rel="stylesheet" media="screen" type="text/css" title="Style" href="style.css" />
        <style type="text/css">
        #annees
        {
            margin: 0px;
            font-size: 60%;
        }

        #annees a
        {
            color: black;
        }

        #annees #anneeCourante
        {
            font-weight: bold;
        }

        #liens
        {
            text-align: center;
        }

        h1, h2
        {
            text-align: center;
        }

        h1 a, h2 a, h3 a, h4 a, h5 a, h6 a
        {
            color: black;
        }

        .flex
        {
            display: flex;
            justify-content: space-around;
        }

        .nb
        {
            text-align: center;
        }
        </style>
    </head>
    <body>
        <div id="header">
            <p>Statistiques <?php if($annee != "-1") { echo $annee; } ?></p>

            <p id="annees">
            <?php
            foreach($annees as $a)
            {
                if($a != $annee)
                    echo '<a href="stats.php?annee='.$a.'">' . $a . '</a> ';
                else
                    echo '<a href="stats.php" id="anneeCourante">' . $a . '</a> ';
            }
            ?>
            </p>
        </div>

        <p id="liens">Vous pouvez également accéder <a href="./">à l'éditeur de personnalité</a> ainsi qu'aux <a href="log.php">résumés des parties</a>.</p>

        <div class="flex">

        <div>
        <h1><a href="#generales" id="generales">Statistiques générales</a></h1>

        <ul>
            <li><?php printf("%d parties ont été jouées par %d joueurs (%d joueurs uniques)", $totalParties, $totalJoueurs, count($joueursParticipations)); ?></li>

            <li><?php printf("%d joueurs par partie en moyenne", round($totalJoueurs/$totalParties)); ?></li>

            <li><?php printf("%d " . ngettext("victoire", "victoires", $victoiresVillageois) . " des villageois, soit %s des parties",
                        $victoiresVillageois, pourcentage($victoiresVillageois, $totalParties)); ?></li>

            <li><?php printf("%d " . ngettext("victoire", "victoires", $victoiresLoups) ." des loups, soit %s des parties",
                        $victoiresLoups, pourcentage($victoiresLoups, $totalParties)); ?></li>

            <?php
            if($victoiresAmoureux > 0) { ?>
                <li><?php printf("%d " . ngettext("victoire", "victoires", $victoiresAmoureux) . " des amoureux, soit %s des parties",
                            $victoiresAmoureux, pourcentage($victoiresAmoureux, $totalParties)); ?></li>
            <?php }

            if($victoiresAnge > 0) { ?>
                <li><?php printf("%d " . ngettext("victoire", "victoires", $victoiresAnge) . " de l'ange, soit %s des parties",
                            $victoiresAnge, pourcentage($victoiresAnge, $totalParties)); ?></li>
            <?php }

            if($victoiresPersonne > 0) { ?>
                <li><?php printf("%d " . ngettext("match nul", "matchs nuls", $victoiresPersonne) . ", soit %s des parties",
                            $victoiresPersonne, pourcentage($victoiresPersonne, $totalParties)); ?></li>
            <?php } ?>

            <li><?php printf("%d messages envoyés sur le chat", $totalMessages); ?></li>
        </ul>
        </div>

        <div>
        <h1><a href="#persos" id="persos">Personnalités les plus utilisées</a></h1>

        <ol start="0">
            <?php
            asort($personnalites,  SORT_NUMERIC);
            $i = 0;

            foreach (array_reverse($personnalites, true) as $personnalite => $nbUtilisations) {
                if($i++ > 10)
                    break;

                printf("<li><strong>%s</strong>, utilisé dans %d " . ngettext("partie", "parties", $nbUtilisations) . ", soit %s</li>", $personnalite, $nbUtilisations, pourcentage($nbUtilisations, $totalParties));
            }
            ?>
        </ol>
        </div>

        </div>

        <div class="flex">
        <div>

        <h1><a href="#roles" id="roles">Répartition des rôles</a></h1>

        <ul>
            <?php
            foreach($roles as $role => $apparitions)
            {
                ?>
                <li>
                    <?php 
                    printf("%s %s", $apparitions, formatRole($role, $apparitions));

                    if($apparitions < $totalParties)
                        printf(", dans %s des parties", pourcentage($apparitions, $totalParties));
                    ?>
                </li>
                <?php
            }
            ?>
            <?php if($totalTraitres > 0){ ?>
                <li><?php printf("%d " . ngettext("traître devenu loup", "traîtres devenus loups", $totalTraitres), $totalTraitres); ?></li>
            <?php } ?>

            <?php if($totalEnfants > 0){ ?>
                <li><?php printf("%d " . ngettext("enfant devenu loup", "enfants devenus loups", $totalEnfants), $totalEnfants); ?></li>
            <?php } ?>
        </ul>
        </div>

        <div>
        <h1><a href="#phases" id="phases">Phases de jeu</a></h1>

        <ul>
            <li><?php printf("%d nuits", $totalTours); ?></li>

            <?php if($totalPhasesPolicier > 0) { ?>
                <li><?php printf("%d " . ngettext("personne mise", "personnes mises", $totalPhasesPolicier) . " en prison par le policier", $totalPhasesPolicier); ?></li>
            <?php } ?>

            <li><?php printf("%d observations de la voyante", $totalPhasesVoyante); ?>
                <ul><li>
                    <?php printf("%d loups découverts, soit %s des observations", $totalPhasesVoyanteLoups, pourcentage($totalPhasesVoyanteLoups, $totalPhasesVoyante)); ?>
                </li></ul>
            </li>

            <li><?php printf("%d votes ajoutés par le corbeau", $totalPhasesCorbeau * 2); ?></li>

            <li><?php printf("%d interventions du salvateur", $totalPhasesSalvateur); ?>
                <ul><li>
                    <?php printf("%d sauvetages des griffes des loups", $totalPhasesSalvateurSauves); ?>
                </li></ul>
            </li>

            <li><?php printf("%d élections de maires", $totalElectionsMaire); ?>
                <ul><li>
                    <?php printf(ngettext("%d loup élu", "%d loups élus", $totalElectionsMaireLoup) . " maire, soit %s",
                            $totalElectionsMaireLoup, pourcentage($totalElectionsMaireLoup, $totalElectionsMaire)); ?>
                </li></ul>
            </li>

            <li><?php printf("%d phases de lapidation", $totalLapidations); ?>
                <ul><li>
                    <?php printf("%d égalités", $totalLapidationsEgalitees); ?>
                </li></ul>
            </li>

            <li><?php printf("%d " . ngettext("séance", "séances", $totalPhasesSpiritisme) . " de spiritisme", $totalPhasesSpiritisme); ?></li>

            <li><?php printf("%d messages laissés sur le mur", $totalMessagesMurs); ?></li>
        </ul>
        </div>

        <div>
        <h1><a href="#victimes" id="victimes">Victimes</a></h1>

        <ul>
            <li><?php printf("%d victimes des loups", $totalVictimesLoups); ?></li>

            <li><?php printf("%d lapidés", $totalVictimesLapidation); ?>
            <ul>
                <li><?php printf("dont %d loups, soit %s", $totalVictimesLapidationLoups, pourcentage($totalVictimesLapidationLoups, $totalVictimesLapidation)); ?></li>
                <?php if($totalVictimesLapidationAncien > 0) { ?>
                        <li><?php printf("dont %d " . ngettext('ancien', 'anciens', $totalVictimesLapidationAncien),
                                    $totalVictimesLapidationAncien); ?></li>
                <?php } 
                      if($totalVictimesLapidationIdiot > 0) { ?>
                        <li><?php printf("dont %d " . ngettext('idiot...qui y a', 'idiots...qui y ont', $totalVictimesLapidationIdiot) . " échappé",
                                    $totalVictimesLapidationIdiot); ?></li>
                <?php } ?>
            </ul>
            </li>

            <li><?php printf("%d ".ngettext("victime", "victimes", $totalVictimesChasseur)." du chasseur", $totalVictimesChasseur); ?>
            <?php if($totalVictimesChasseurLoups > 0) { ?>
                <ul><li><?php printf("dont %d " . ngettext('loup', 'loups', $totalVictimesChasseurLoups) . ", soit %s", 
                                $totalVictimesChasseurLoups, pourcentage($totalVictimesChasseurLoups, $totalVictimesChasseur)); ?></li></ul>
            <?php } ?>
            </li>

            <li><?php printf("%d victimes de la sorcière", $totalVictimesSorciere); ?>
            <?php if($totalVictimesSorciereLoups > 0) { ?>
                <ul><li><?php printf("dont %d " . ngettext("loup", "loups", $totalVictimesSorciereLoups) . ", soit %s",
                                $totalVictimesSorciereLoups, pourcentage($totalVictimesSorciereLoups, $totalVictimesSorciere)); ?></li></ul>
            <?php } ?>
            </li>

            <?php if($totalVictimesAmoureux > 0) { ?>
                <li><?php printf("%d morts d'un chagrin d'amour", $totalVictimesAmoureux); ?></li>
            <?php } ?>

            <?php if($totalVictimesCrises > 0) { ?>
                <li><?php printf("%d crises cardiaques", $totalVictimesCrises); ?></li>
            <?php } ?>
        </ul>
        </div>

        </div>

        <h1><a href="#joueurs" id="joueurs">Joueurs</a></h1>

        <?php
        // Nombre minimum de participations pour être pris en compte dans les stats
        define("MIN_PARTICIPATIONS", 10);
        ?>

        <div class="flex">
            <div>
            <h2><a href="#joueurs_participation" id="joueurs_participation">Participation</a></h2>

            <ol>
                <?php
                asort($joueursParticipations,  SORT_NUMERIC);
                $i = 0;

                foreach (array_reverse($joueursParticipations, true) as $pseudo => $nbParticipation)
                {
                    if($i++ < 10)
                    {
                        printf("<li><strong>%s</strong> a participé à %d parties, soit %s du total des parties</li>",
                            $pseudos[$pseudo], $nbParticipation, pourcentage($nbParticipation, $totalParties));
                    }
                }
                ?>
            </ol>
            </div>
        </div>

        <h2><a href="#joueurs_roles" id="joueurs_roles">Rôles</a></h2>

        <?php
        $joueursRoleSimpleRatio = array();
        foreach($joueursRoleSimple as $pseudo => $nbRoles)
        {
            if($joueursParticipations[$pseudo] >= MIN_PARTICIPATIONS)
                $joueursRoleSimpleRatio[$pseudo] = $nbRoles / $joueursParticipations[$pseudo];
        }

        $joueursRoleLoupRatio = array();
        foreach($joueursRoleLoup as $pseudo => $nbRoles)
        {
            if($joueursParticipations[$pseudo] >= MIN_PARTICIPATIONS)
                $joueursRoleLoupRatio[$pseudo] = $nbRoles / $joueursParticipations[$pseudo];
        }
        ?>

        <div class="flex">
            <div>
                <h3>Simple villageois</h3>
                <ol>
                <?php
                asort($joueursRoleSimpleRatio,  SORT_NUMERIC);
                $i = 0;

                foreach (array_reverse($joueursRoleSimpleRatio, true) as $pseudo => $ratio)
                {
                    if($i++ < 10)
                    {
                        printf("<li><strong>%s</strong> était simple villageois dans %d%% (%d/%d) de ses parties</li>",
                            $pseudos[$pseudo], round($ratio*100), $joueursRoleSimple[$pseudo], $joueursParticipations[$pseudo]);
                    }
                }
                ?>
                </ol>
            </div>

            <div>
                <h3>Loup garou</h3>
                <ol>
                <?php
                asort($joueursRoleLoupRatio,  SORT_NUMERIC);
                $i = 0;

                foreach (array_reverse($joueursRoleLoupRatio, true) as $pseudo => $ratio)
                {
                    if($i++ < 10)
                    {
                        printf("<li><strong>%s</strong> était loup garou dans %d%% (%d/%d) de ses parties</li>",
                            $pseudos[$pseudo], round($ratio*100), $joueursRoleLoup[$pseudo], $joueursParticipations[$pseudo]);
                    }
                }
                ?>
                </ol>
            </div>
        </div>

        <h2><a href="#joueurs_survies" id="joueurs_survies">Survies</a></h2>

        <div class="flex">
            <div>
                <h3>Valeur absolue</h3>

                <ol>
                    <?php
                    $joueursMorts = array();
                    $joueursSurviesRatio = array();
                    asort($joueursSurvies,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($joueursSurvies, true) as $pseudo => $nbSurvies)
                    {
                        $joueursMorts[$pseudo] = $joueursParticipations[$pseudo] - $nbSurvies;
                        $ratio = $nbSurvies / $joueursParticipations[$pseudo];

                        if($joueursParticipations[$pseudo] >= MIN_PARTICIPATIONS)
                            $joueursSurviesRatio[$pseudo] = $ratio;

                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a survécu dans %d ". ngettext("partie", "parties", $nbSurvies) ."</li>", $pseudos[$pseudo], $nbSurvies);
                        }
                    }
                    ?>
                </ol>
            </div>

            <div>
                <h3>Pourcentage</h3>

                <ol>
                    <?php
                    asort($joueursSurviesRatio,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($joueursSurviesRatio, true) as $pseudo => $ratio)
                    {
                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a survécu dans %d%% (%d/%d) de ses parties</li>",
                                $pseudos[$pseudo], round($ratio*100), $joueursSurvies[$pseudo], $joueursParticipations[$pseudo]);
                        }
                    }
                    ?>
                </ol>
            </div>
        </div>

        <h2><a href="#joueurs_victoires" id="joueurs_victoires">Victoires</a></h2>

        <p class="nb">Un joueur est considéré comme ayant gagné si son équipe gagne, même si le joueur en question est mort.</p>

        <div class="flex">
            <div>
                <h3>Valeur absolue</h3>

                <ol>
                    <?php
                    $joueursVictoiresRatio = array();
                    asort($joueursVictoires,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($joueursVictoires, true) as $pseudo => $nbVictoires)
                    {
                        $ratio = $nbVictoires / $joueursParticipations[$pseudo];

                        if($joueursParticipations[$pseudo] >= MIN_PARTICIPATIONS)
                            $joueursVictoiresRatio[$pseudo] = $ratio;

                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a remporté %d ". ngettext("partie", "parties", $nbVictoires) ."</li>", $pseudos[$pseudo], $nbVictoires);
                        }
                    }
                    ?>
                </ol>
            </div>

            <div>
                <h3>Pourcentage</h3>

                <ol>
                    <?php
                    asort($joueursVictoiresRatio,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($joueursVictoiresRatio, true) as $pseudo => $ratio)
                    {
                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a remporté %d%% (%d/%d) de ses parties</li>",
                                $pseudos[$pseudo], round($ratio*100), $joueursVictoires[$pseudo], $joueursParticipations[$pseudo]);
                        }
                    }
                    ?>
                </ol>
            </div>
        </div>

        <h2><a href="#joueurs_morts" id="joueurs_morts">Morts</a></h2>

        <div class="flex">
            <div>
                <h3>Valeur absolue</h3>

                <ol>
                    <?php
                    $joueursMortsRatio = array();
                    asort($joueursMorts,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($joueursMorts, true) as $pseudo => $nbMorts)
                    {
                        $ratio = $nbMorts / $joueursParticipations[$pseudo];

                        if($joueursParticipations[$pseudo] >= MIN_PARTICIPATIONS)
                            $joueursMortsRatio[$pseudo] = $ratio;

                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> est mort dans %d ". ngettext("partie", "parties", $nbMorts) ."</li>", $pseudos[$pseudo], $nbMorts);
                        }
                    }
                    ?>
                </ol>
            </div>

            <div>
                <h3>Pourcentage</h3>

                <ol>
                    <?php
                    asort($joueursMortsRatio,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($joueursMortsRatio, true) as $pseudo => $ratio)
                    {
                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> est mort dans %d%% (%d/%d) de ses parties</li>",
                                $pseudos[$pseudo], round($ratio*100), $joueursMorts[$pseudo], $joueursParticipations[$pseudo]);
                        }
                    }
                    ?>
                </ol>
            </div>
        </div>

        <h2><a href="#joueurs_defaites" id="joueurs_defaites">Défaites</a></h2>

        <div class="flex">
            <div>
                <h3>Valeur absolue</h3>

                <ol>
                    <?php
                    $joueursDefaitesRatio = array();
                    asort($joueursDefaites,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($joueursDefaites, true) as $pseudo => $nbDefaites)
                    {
                        $ratio = $nbDefaites / $joueursParticipations[$pseudo];

                        if($joueursParticipations[$pseudo] >= MIN_PARTICIPATIONS)
                            $joueursDefaitesRatio[$pseudo] = $ratio;

                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a perdu %d ". ngettext("partie", "parties", $nbSurvies) ."</li>", $pseudos[$pseudo], $nbDefaites);
                        }
                    }
                    ?>
                </ol>
            </div>

            <div>
                <h3>Pourcentage</h3>

                <ol>
                    <?php
                    asort($joueursDefaitesRatio,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($joueursDefaitesRatio, true) as $pseudo => $ratio)
                    {
                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a perdu %d%% (%d/%d) de ses parties</li>",
                                $pseudos[$pseudo], round($ratio*100), $joueursDefaites[$pseudo], $joueursParticipations[$pseudo]);
                        }
                    }
                    ?>
                </ol>
            </div>
        </div>

        <h2><a href="#joueurs_victime_loups" id="joueurs_victime_loups">Victimes préférées des loups</a></h2>

        <div class="flex">
            <div>
                <h3>Valeur absolue</h3>

                <ol>
                    <?php
                    $vicimesLoupsRatio = array();
                    asort($victimesLoups,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($victimesLoups, true) as $pseudo => $nbVictimesLoups)
                    {
                        $ratio = $nbVictimesLoups / $joueursParticipations[$pseudo];

                        if($joueursParticipations[$pseudo] >= MIN_PARTICIPATIONS)
                            $vicimesLoupsRatio[$pseudo] = $ratio;

                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a été attaqué par les loups %d fois</li>", $pseudos[$pseudo], $nbVictimesLoups);
                        }
                    }
                    ?>
                </ol>
            </div>

            <div>
                <h3>Pourcentage</h3>

                <ol>
                    <?php
                    asort($vicimesLoupsRatio,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($vicimesLoupsRatio, true) as $pseudo => $ratio)
                    {
                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a été visé par les loups dans %d%% (%d/%d) de ses parties</li>",
                                $pseudos[$pseudo], round($ratio*100), $victimesLoups[$pseudo], $joueursParticipations[$pseudo]);
                        }
                    }
                    ?>
                </ol>
            </div>
        </div>

        <h2><a href="#joueurs_victime_villageois" id="joueurs_victime_villageois">Victimes préférées des villageois</a></h2>

        <div class="flex">
            <div>
                <h3>Valeur absolue</h3>

                <ol>
                    <?php
                    $victimesLapidationRatio = array();
                    asort($victimesLapidation,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($victimesLapidation, true) as $pseudo => $nbVictimesLapidation)
                    {
                        $ratio = $nbVictimesLapidation / $joueursParticipations[$pseudo];

                        if($joueursParticipations[$pseudo] >= MIN_PARTICIPATIONS)
                            $victimesLapidationRatio[$pseudo] = $ratio;

                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a été choisi pour être lapidé %d fois</li>", $pseudos[$pseudo], $nbVictimesLapidation);
                        }
                    }
                    ?>
                </ol>
            </div>

            <div>
                <h3>Pourcentage</h3>

                <ol>
                    <?php
                    asort($victimesLapidationRatio,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($victimesLapidationRatio, true) as $pseudo => $ratio)
                    {
                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a été condamné par les villageois dans %d%% (%d/%d) de ses parties</li>",
                                $pseudos[$pseudo], round($ratio*100), $victimesLapidation[$pseudo], $joueursParticipations[$pseudo]);
                        }
                    }
                    ?>
                </ol>
            </div>
        </div>

        <h2><a href="#joueurs_victime_innocentes_villageois" id="joueurs_victime_innocentes_villageois">Victimes (innocentes) préférées des villageois</a></h2>

        <p class="nb">Contrairement aux stats au dessus, on ne compte ici que les occurrences où un joueur a été lapidé alors qu'il n'était pas loup.</p>

        <div class="flex">
            <div>
                <h3>Valeur absolue</h3>

                <ol>
                    <?php
                    $victimesLapidationVillageoisRatio = array();
                    asort($victimesLapidationVillageois,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($victimesLapidationVillageois, true) as $pseudo => $nbVictimesLapidation)
                    {
                        $ratio = $nbVictimesLapidation / $joueursRoleVillageois[$pseudo];

                        if($joueursParticipations[$pseudo] >= MIN_PARTICIPATIONS)
                            $victimesLapidationVillageoisRatio[$pseudo] = $ratio;

                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a été lapidé %d fois en étant villageois</li>", $pseudos[$pseudo], $nbVictimesLapidation);
                        }
                    }
                    ?>
                </ol>
            </div>

            <div>
                <h3>Pourcentage</h3>

                <ol>
                    <?php
                    asort($victimesLapidationVillageoisRatio,  SORT_NUMERIC);
                    $i = 0;

                    foreach (array_reverse($victimesLapidationVillageoisRatio, true) as $pseudo => $ratio)
                    {
                        if($i++ < 10)
                        {
                            printf("<li><strong>%s</strong> a été lapidé dans %d%% (%d/%d) de ses parties en tant que villageois</li>",
                                $pseudos[$pseudo], round($ratio*100), $victimesLapidationVillageois[$pseudo], $joueursRoleVillageois[$pseudo]);
                        }
                    }
                    ?>
                </ol>
            </div>
        </div>

        <div class="flex">

        <?php
        $joueursVotesRatio = array();
        foreach($joueursVotesTotal as $pseudo => $nbVotes)
        {
            if(array_key_exists($pseudo, $joueursVotesContreLoups))
                $ratio = $joueursVotesContreLoups[$pseudo] / $nbVotes;
            else
                $ratio = 0;

            if($nbVotes > 10)
                $joueursVotesRatio[$pseudo] = $ratio;
        }

        if(!empty($joueursVotesRatio))
        {
        ?>

        <div>
        <h2><a href="#joueurs_sharp" id="joueurs_sharp">Sharpshooters</a></h2>

        <p class="nb">Ne sont comptés que les votes des villageois;<br />un loup va rarement voter contre un autre loup.</p>

        <ol>
            <?php
            asort($joueursVotesRatio,  SORT_NUMERIC);
            $i = 0;

            foreach (array_reverse($joueursVotesRatio, true) as $pseudo => $ratio)
            {
                if($i++ < 10)
                {
                    printf("<li><strong>%s</strong> : %d%% (%d/%d) de ses votes étaient contre un loup</li>",
                        $pseudos[$pseudo], round($ratio*100), $joueursVotesContreLoups[$pseudo], $joueursVotesTotal[$pseudo]);
                }
            }
            ?>
        </ol>
        </div>

        <?php
        }
        
        if(!empty($joueursVotesBlanc))
        {
        ?>

        <div>
        <h2><a href="#joueurs_indecis" id="joueurs_indecis">Indécis</a></h2>

        <ol>
            <?php
            asort($joueursVotesBlanc,  SORT_NUMERIC);
            $i = 0;

            foreach (array_reverse($joueursVotesBlanc, true) as $pseudo => $nbVotesBlancs)
            {
                if($i++ < 10)
                {
                    printf("<li><strong>%s</strong> a voté blanc %d fois</li>", $pseudos[$pseudo], $joueursVotesBlanc[$pseudo]);
                }
            }
            ?>
        </ol>
        </div>

        <?php
        }
        ?>

        </div>
        <div class="flex">

        <div>
        <h2><a href="#joueurs_maires" id="joueurs_maires">Maires</a></h2>

        <ol>
            <?php
            asort($maires,  SORT_NUMERIC);
            $i = 0;

            foreach (array_reverse($maires, true) as $pseudo => $nbMaires)
            {
                if($i++ < 10)
                {
                    printf("<li><strong>%s</strong> a été élu maire %d fois</li>", $pseudos[$pseudo], $nbMaires);
                }
            }
            ?>
        </ol>
        </div>

        </div>
    </body>
</html>