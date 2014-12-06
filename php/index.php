<?php
include_once('../is_connected.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
	<head>
		<title>Loups-Garous</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" media="screen" type="text/css" title="Style" href="style.css" />
		<script type="text/javascript" src="jquery.js"></script>
		<script type="text/javascript">
			$(document).ready(function(){
					$(".supprimer").click(function(){
							if(confirm('Êtes-vous sûr de vouloir supprimer cette personnalité ? Une fois supprimée, vous ne pourrez plus la récupérer !'))
							{
								var id = $(this).parent().attr('id')
								window.location.href = "supprimer.php?" + id 
							}
					});
			});
		</script>
	</head>
	<body>
	<div id="header"><p>Éditeur de personnalité du bot<br />des Loups-garous de Thiercelieux</p></div>
	<?php
	if($connected)
	{
		?>
	<p id="pseudo">Bienvenue <?php echo $user->data['username']; ?> !</p>
	<p class="explication">Cet outil va vous permettre de créer une nouvelle personnalitée pour le bot des Loups-Garous. Une personnalitée, c'est une liste de rôles, de phrases...que dira le bot pendant la partie.<br />
	À chaque nouvelle partie, le bot choisi une personnalité au hasard parmi celles disponibles.<br />
	Si c'est la personnalitée que vous avez créée qui est choisie, ce seront vos phrases et vos rôles qui seront utilisés pour jouer&nbsp;!</p>
	<p class="explication">Vous pouvez également accéder <a href="log.php">aux résumés des dernières parties</a>.</p>
	<noscript><p class="explication"><strong>Javascript est désactivé ! Pensez à l'activer pour ne pas risquer de perdre votre travail à cause d'une erreur.</strong></p></noscript>
	<?php 
	if($_GET['type'] == 'save')
		echo '<p id="confirm">La personnalité a bien été sauvegardée !</p>';
	elseif($_GET['type'] == 'submit')
		echo '<p id="confirm">La personnalité a été soumise à validation et sera vérifiée bientôt. Revenez de temps en temps sur cette page pour savoir si elle a été acceptée !</p>';
	elseif($_GET['type'] == 'already')
		echo '<p id="confirm">La personnalité a déjà été soumise. Soyez patient !</p>';
	elseif($_GET['type'] == 'deleted')
		echo '<p id="confirm">La personnalité a été correctement supprimée.</p>';
	elseif($_GET['type'] == 'accepted')
		echo '<p id="confirm">Personnalité acceptée.</p>';
	elseif($_GET['type'] == 'refused')
		echo '<p id="confirm">Personnalité refusée.</p>';
	?>
		<?php
		include ('list.php');
	}
	else
	{
		?>
	<div id="nom"><p>Vous devez être connecté au forum pour créer ou éditer une personnalité !</p></div>
		<?php
	}
	?>
	</body>
</html>
