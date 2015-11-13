<?php
//Liste de tous les fichiers en attente (admin)
if ($user->data['user_id'] == 3)
{
	$dir = new DirectoryIterator('./personnalites/pending');
	$nb = 0;
	foreach ($dir as $file)
	{
		if ($file->isFile() && $file->isDot() == false)
			$pendingAdmin[] = $file->getFilename();
	}
	
	if(isset($pendingAdmin))
	{
		?>
		<div class="liste">
			<p class="titre">Admin : Personnalités en attente de validation</p>
			<ul>
				<?php
				foreach($pendingAdmin as $fichier)
				{
					$xml = simplexml_load_file('./personnalites/pending/'. $fichier);
					echo '<li><a href="editer.php?type=admin&amp;id='.$fichier.'">' . $xml->nom . '</a></li>';
				}
				?>
			</ul>
		</div>
		<?php
	}
}

//Liste de tous les fichiers validés
$dir = new DirectoryIterator('./personnalites/accepted');
$nb = 0;
foreach ($dir as $file)
{
	if ($file->isFile() && strtok($file->getFilename(), '_') == $user->data['user_id'])
		$accepted[] = str_replace('.xml', '', strtok('_'));
}

if(isset($accepted))
{
	$default = simplexml_load_file('./personnalites/default/default.xml');
	$total_default = sizeof($default->repliques->dire);
	?>
	<div class="liste">
	<p class="aide"><strong><em>Aide :</em></strong><br />
	Ces personnalités ont été validées et peuvent donc être choisies au hasard par le bot lors d'une partie. Si vous modifiez une personnalité déjà validée, vous n'aurez pas à la valider à nouveau, mais faites-le uniquement pour ajouter de nouvelles phrases ou corriger des fautes&nbsp;!</p>
		<p class="titre">Personnalités activées</p>
		<ul>
			<?php
			foreach($accepted as $fichier)
			{
				$xml = simplexml_load_file('./personnalites/accepted/' . $user->data['user_id'] . '_' . $fichier . '.xml');
				$total = sizeof($xml->repliques->dire);
				if ($total != $total_default)
					$update = ' - <span class="update">MISE À JOUR NÉCESSAIRE</span>';
				else
					$update = '';
				echo '<li class="liSupprimer" id="type=accepted&amp;id='.$fichier.'"><a href="#" class="supprimer"><img src="croix.png" alt="Supprimer la personnalité" title="Supprimer la personnalité" /></a> <a href="editer.php?type=accepted&amp;id='.$fichier.'">' . $xml->nom . '</a>'.$update.'</li>';
			}
			?>
		</ul>
	</div>
	<?php
}

//Liste de tous les fichiers non soumis
$dir = new DirectoryIterator('./personnalites/awaiting');
$nb = 0;
foreach ($dir as $file)
{
	if ($file->isFile() && strtok($file->getFilename(), '_') == $user->data['user_id'])
		$awaiting[] = str_replace('.xml', '', strtok('_'));
}

?>
<div class="liste">
	<p class="aide"><strong><em>Aide :</em></strong><br />
	Ces personnalités n'ont pas encore été soumises à la validation, elles ne pourront donc pas apparaitre lors d'une partie. Quand vous pensez qu'une personnalité est prête à être envoyée et intégrée au jeu, cliquez sur son nom et choisissez "Sauvegarder et soumettre à la validation" en bas de la page.</p>
	<p class="titre">Personnalités non-soumises</p>
	<ul>
		<?php
		if(isset($awaiting))
		{
			foreach($awaiting as $fichier)
			{
				$xml = simplexml_load_file('./personnalites/awaiting/' . $user->data['user_id'] . '_' . $fichier . '.xml');
				if(!isset($xml->raison))
					echo '<li class="liSupprimer" id="type=awaiting&amp;id='.$fichier.'"><a href="#" class="supprimer"><img src="croix.png" alt="Supprimer la personnalité" title="Supprimer la personnalité" /></a> <a href="editer.php?type=awaiting&amp;id='.$fichier.'">' . $xml->nom . '</a></li>';
			}
		}
		?>
		<li><a href="editer.php?type=new&amp;id=<?php echo time(); ?>">Créer une nouvelle personnalité</a></li>
	</ul>
</div>
<?php

//Liste de tous les fichiers refusés
$dir = new DirectoryIterator('./personnalites/awaiting');
$nb = 0;
if(isset($awaiting))
{
	$refused = '';
	foreach($awaiting as $fichier)
	{
		$xml = simplexml_load_file('./personnalites/awaiting/' . $user->data['user_id'] . '_' . $fichier . '.xml');
		if(isset($xml->raison))
			$refused .= '<li class="liSupprimer" id="type=awaiting&amp;id='.$fichier.'"><a href="#" class="supprimer"><img src="croix.png" alt="Supprimer la personnalité" title="Supprimer la personnalité" /></a> <a href="editer.php?type=awaiting&id='.$fichier.'">' . $xml->nom . '</a> - Raison : '.$xml->raison.'</li>';
	}
	if(!empty($refused))
	{
	?>
	<div class="liste">
	<p class="aide"><strong><em>Aide :</em></strong><br />
	Ces personnalités ont été refusées lors de la phase de validation et n'apparaitront pas dans le jeu. La raison du refus est indiquée à côté du nom de la personnalité. Prenez-la en compte, corrigez et RELISEZ votre personnalité, puis envoyez-la à nouveau quand elle sera prête !</p>
		<p class="titre">Personnalités refusées</p>
		<ul>
			<?php
			echo $refused;
			?>
		</ul>
	</div>
	<?php
	}
}

//Liste de tous les fichiers en attente
$dir = new DirectoryIterator('./personnalites/pending');
$nb = 0;
foreach ($dir as $file)
{
	if ($file->isFile() && strtok($file->getFilename(), '_') == $user->data['user_id'])
		$pending[] = str_replace('.xml', '', strtok('_'));
}

if(isset($pending))
{
	?>
	<div class="liste">
	<p class="aide"><strong><em>Aide :</em></strong><br />
	Ces personnalités ont été soumises à validation mais n'ont pas encore été revues. Tant que ce sera le cas, vous ne pourrez pas les modifier. Revenez plus tard pour savoir si elles ont été validées ou non !</p>
		<p class="titre">Personnalités en attente de validation</p>
		<ul>
			<?php
			foreach($pending as $fichier)
			{
				$xml = simplexml_load_file('./personnalites/pending/' . $user->data['user_id'] . '_' . $fichier . '.xml');
				echo '<li>' . $xml->nom . '</a></li>';
			}
			?>
		</ul>
	</div>
	<?php
}

//Liste de tous les fichiers disponibles
$dir = new DirectoryIterator('./personnalites/accepted');
$nb = 0;

$default = simplexml_load_file('./personnalites/default/default.xml');
$total_default = sizeof($default->repliques->dire);

foreach ($dir as $file)
{
	if ($file->isFile() && strtok($file->getFilename(), '_') != $user->data['user_id'])
	{
		$perso = simplexml_load_file('./personnalites/accepted/' . $file->getFilename());

		$total_perso = sizeof($perso->repliques->dire);
		$nom = $perso->nom;
		$pourcent = ($total_perso / $total_default) * 100;

		// Si ça dépasse 100%, il y a des phrases obsolètes
		if($pourcent > 100)
			$pourcent = 99;

		$all[] = array($nom, $pourcent);
	}
}

if(isset($all))
{
	$minDisabled = 80;

	function sortByPercent($a, $b)
	{
		$pa = $a[1];
		$pb = $b[1];

		if($pa > $pb)
			return 1;
		if($pb > $pa)
			return -1;

		// Égalité, comparaison du nom
		return -(strcmp($a[0], $b[0]));
	}

	usort($all, sortByPercent);
	$all = array_reverse($all);
	?>
	<div class="liste">
	<p class="aide"><strong><em>Aide :</em></strong><br />
	Voici la liste de toutes les personnalitées acceptées envoyées par les autres membres, pouvant être choisies par le bot. Cette liste vous évitera de créer une personnalité qui existe déjà !
	<br /><br />
	Le pourcentage indique la quantité de phrases traduites par rapport à la personnalité par défaut. Les personnalités qui comportent moins de <?php echo $minDisabled; ?>% de phrases traduites sont désactivées jusqu'à ce qu'elles soient mises à jour.
	</p>
		<p class="titre">Personnalités acceptées créées par les autres membres</p>
		<ul>
			<?php
			foreach($all as $perso)
			{
				$pourcent = $perso[1];

				if($pourcent == 100)
					$class = ' class="upToDate"';
				elseif($pourcent < $minDisabled)
					$class = ' class="outOfDate"';
				else
					$class = '';

				echo '<li'.$class.'>' . $perso[0] . ' <em>('.floor($pourcent).'%)</em></li>';
			}
			?>
		</ul>
	</div>
	<?php
}
?>
