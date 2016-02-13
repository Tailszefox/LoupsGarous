<?php
$phpbb_root_path = '/www/Forum/';
require_once('/www/is_connected.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
	<head>
		<title>Loups-Garous</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" media="screen" type="text/css" title="Style" href="style.css" />
		<script type="text/javascript" src="jquery.js"></script>
		<script type="text/javascript" src="javascript.js"></script>
	</head>
	<body>
	<?php
	if($_GET['type'] == 'admin' && $user->data['user_id'] != 3)
		die('</body></html>');
	if(($_GET['type'] != 'admin') && (!$connected || ($_GET['type'] != 'accepted' && $_GET['type'] != 'awaiting' && $_GET['type'] != 'new') || (!is_numeric($_GET['id']))))
		die('</body></html>');
	?>
	<div id="header"><p>Édition d'une personnalité</p></div>
	<?php
	$default = simplexml_load_file('./personnalites/default/default.xml');
	$total_default = sizeof($default->repliques->dire);
	if ($_GET['type'] == 'accepted')
	{
		$file = @simplexml_load_file('./personnalites/accepted/' . $user->data['user_id'] . '_' . $_GET['id'] . '.xml');
		$total = sizeof($file->repliques->dire);
		if ($total < $total_default)
		{
			echo '<p id="confirm" class="update">Cette personnalité n\'est pas à jour : certaines phrases n\'ont pas d\'équivalence. Les phrases à rajouter ont un titre rouge.</p>';
		}
	}
	else if($_GET['type'] == 'awaiting')
		$file = @simplexml_load_file('./personnalites/awaiting/' . $user->data['user_id'] . '_' . $_GET['id'] . '.xml');
	else if($_GET['type'] == 'new')
		$file = new SimpleXMLElement('<?xml version="1.0" ?><caractere></caractere>');
	else if($_GET['type'] == 'admin')
		$file = @simplexml_load_file('./personnalites/pending/' . $_GET['id']);
	if($file === false)
		die('<p>Le fichier n\'existe pas ! Qui a touché à l\'adresse de la page ?</p></body></html>');
	?>
	<form method="post" id="formulaire" action="valider.php?type=<?php echo $_GET['type']; ?>&amp;id=<?php echo $_GET['id'];?>">
	
	<div class="liste">
	<p class="aide"><strong><em>Aide :</em></strong><br />Le nom de votre personnalité est indispensable, sans quoi vous ne pourrez même pas la sauvegarder. Il représente le nom du présentateur, et sera annoncé par le bot si c'est votre personnalité qui a été choisie. Prenez-en un qui décrit bien le style de votre personnalité&nbsp;!</p>
		<p class="titre">Nom</p>
		<p><input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($file->nom); ?>" /></p>
	</div>
	
	<div class="liste">
	<p class="aide"><strong><em>Aide :</em></strong><br />
	Vous pouvez modifier les noms des différents rôles disponibles, c'est même plus que conseillé pour créer une personnalité originale. Sinon, vous pouvez laisser les noms par défaut. Si vous les changez, utilisez des noms en rapport avec le style de votre personnalité, et essayez de les rendre le plus clair possible pour éviter que tout le monde se demande de quoi il s'agit&nbsp;!<br />
	Faites toujours précéder le nom du rôle par "un" ou "une" pour les rôles du loup et du simple villageois. Pour les autres, ne mettez rien s'il s'agit d'un nom propre, ou "le" ou "la" dans le cas d'un nom commun.</p>
		<p class="titre">Rôles</p>
		<?php
		foreach($default->roles->role as $role)
		{
			$custom = $file->xpath('//role[@nom="'.$role['nom'].'"]');
			echo '<p>' .ucfirst($role);
			echo '<input type="text" class="droite" spellcheck="true" name="role_'.$role['nom'].'" value="';
			if (empty($custom))
				echo $role;
			else
				echo htmlspecialchars($custom[0]);
			echo '" /></p>';
		}
		?>
	</div>
	
	<div class="liste">
	<p class="aide"><strong><em>Aide :</em></strong><br />Les déclencheurs sont les mots que les joueurs doivent dire dans certains cas pour signaler que, par exemple, ils veulent participer au jeu. Ils commencent forcément par un point d'exclamation. Vous n'êtes pas obligé de les modifier, ceux par défaut fonctionnent dans la plupart des situations.
	</p>
		<p class="titre">Déclencheurs</p>
		<?php
		foreach($default->declencheurs->declencheur as $declencheur)
		{
			$custom = $file->xpath('//declencheur[@nom="'.$declencheur['nom'].'"]');
			echo '<p>' .$declencheur['contexte'];
			echo '<input type="text" class="droite declencheur" spellcheck="true" name="declencheur_'.$declencheur['nom'].'" value="';
			if (empty($custom))
				echo $declencheur;
			else
				echo htmlspecialchars($custom[0]);
			echo '" /></p>';
		}
		?>
	</div>
	
	<div class="liste">
	<p class="aide"><strong><em>Aide :</em></strong><br />
	Les répliques représentent l'ensemble des phrases que le bot va dire pendant une partie. Ce sont surtout elles qui vont vous permettrent de créer une personnalité vraiment unique.<br />
	<strong>Le titre souligné</strong> est le nom interne de la réplique. Il s'agit d'une description très simple. <strong>Le contexte</strong> est une explication plus longue qui vous permet de savoir dans quel cas la réplique est utilisée.<br />
	<strong>Les variables</strong> sont des éléments qui seront automatiquement remplacées pendant la partie. Elles ont toujours la forme $1, $2, etc., et sont différentes pour chaque réplique. S'il est écrit que $1 est le pseudo du joueur et $2 son rôle, alors la phrase "$1 était $2" donnera par exemple "Bidule était la voyante". Observez bien la phrase d'exemple pour comprendre où et comment les placer. Vous pouvez insérer plusieurs fois la même variable ou changer leur ordre, tant que la phrase garde son sens. Par exemple "Oh, $2 $1 ! $1 est mort !" donnera "Oh, la voyante Bidule ! Bidule est mort !"<br />
	<strong>La phrase d'exemple</strong> est là uniquement pour vous donner un exemple de phrase en rapport avec la situation. Elle vous permet aussi de voir comment vous devez utiliser les variables. C'est dans le <strong>champ de texte en dessous</strong> que vous devez taper ce que le bot dira.<br />
	Enfin, le bouton <strong>Ajouter une autre ligne</strong> fait apparaitre un nouveau champ de texte. Si vous mettez plusieurs lignes pour une même réplique, le bot en choisisera une au hasard à chaque fois qu'il l'a dira. Ainsi, si vous avez deux phrases pour appeler la voyante, le bot dira un coup la première, un coup la deuxième. Plus vous créez de phrases pour une même réplique, plus votre personnalitée sera originale et moins elle sera lassante !</p>
		<p class="titre">Répliques</p>
		<noscript><p>Javascript est désactivé ! Vous ne pourrez pas ajouter de phrases supplémentaires tant qu'il ne sera pas activé.</p></noscript>
		<?php
		foreach($default->repliques->dire as $replique)
		{
			echo '<div class="sousliste">';
			$custom = ($file->xpath('//dire[cle="'.$replique->cle.'"]/phrases/phrase'));
			if (empty($custom) && $_GET['type'] != 'new')
				echo '<p class="cle update">'.$replique->cle.'</p><p>';
			else
				echo '<p class="cle">'.$replique->cle.'</p><p>';
			echo '<strong>Contexte :</strong> ' . $replique->contexte . '<br />';
			
			if (!empty($replique->variables))
			{
				$nbVariables = substr_count($replique->variables, '$');
				echo '<strong>Variables :</strong> ' .$replique->variables. '<br />';
			}
			else
				$nbVariables = 0;
				
			echo '<strong>Phrase d\'exemple :</strong><br />'.$replique->phrases->phrase[0].'</p>';
			echo '<p class="repliques" id="'.$replique->cle.'">';
			if (empty($custom))
			{
				echo '<input type="text" class="phrase" spellcheck="true" name="'.$replique->cle.'_0" variables="'.$nbVariables.'"/>';
			}
			else
			{
				$nb = 0;
				foreach($custom as $phrase)
				{
					echo '<input type="text" class="phrase" spellcheck="true" name="'.$replique->cle.'_'.$nb.'" variables="'.$nbVariables.'" value="'.htmlspecialchars($phrase).'" />';
					$nb++;
				}
			}
			echo '<br /><input type="button" class="add_line" value="Ajouter une autre phrase" /></p></div>';
		}p
		?>
	</div>
	
	<div class="liste">
	<p class="aide"><strong><em>Aide :</em></strong><br />
	<?php
	if($_GET['type'] == 'accepted')
			echo 'Puisque cette personnalité a déjà été acceptée, il vous suffit de cliquer sur le bouton <strong>Mettre à jour</strong>, et les changements seront immédiatement enregistrés. Si vous avez ajouté de nouvelles phrases, pensez à les relire !';
		else
			echo 'Si vous n\'avez pas terminé, cliquez sur le bouton <strong>Sauvegarder uniquement</strong> afin d\'enregistrer votre travail pour y revenir plus tard.<br />
			Si vous pensez par contre que votre personnalité est prête à entrer en jeu, cliquez sur <strong>Sauvegarder et soumettre à la validation</strong>. Attention, vous ne pouvez soumettre votre personnalité que si vous l\'avez entièrement terminée. De plus, vous ne pourrez ensuite plus la modifier tant qu\'elle n\'aura pas été acceptée ou refusée. Enfin, assurez-vous de l\'avoir bien relue et d\'avoir corrigé les fautes sous peine de refus immédiat !';
	?></p>
		<p class="titre">Enregistrement</p>
		<?php
		if($_GET['type'] == 'accepted')
			echo '<input type="submit" name="save" id="save" value="Mettre à jour" />';
		else if ($_GET['type'] == 'admin')
			echo 'Raison du refus : <br /><input type="text" name="raison" /><br /><br /><input type="submit" name="save" id="save" value="Accepter/Refuser" />';
		else
			echo '<input type="submit" id="save" name="save" value="Sauvegarder uniquement" /><br /><br />
			<input type="submit" id="save_and_submit" name="save_and_submit" value="Sauvegarder et soumettre à la validation" />';
		?>
	</div>
	</form>
	</body>
</html>
