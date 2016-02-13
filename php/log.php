<?php

$dir = new GlobIterator('./logs/*.xml');
$nb = 0;
foreach ($dir as $file)
{
	if ($file->isFile())
	{
		$nom = str_replace('.xml', '', $file->getFilename());
		$date = explode('_', $nom);
		
		$jour = mktime('0', '0', '0', $date[1], $date[0], $date[2]);
		$heure = mktime($date[3], $date[4], $date[5], $date[1], $date[0], $date[2]);
		
		$logs[$jour][$heure] = $nom;
	}
}

krsort($logs);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
	<head>
		<title>Loups-Garous - Résumés des parties</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" media="screen" type="text/css" title="Style" href="style.css" />
	</head>
	<body>
	<div id="header"><p>Parties de loup-garou</p></div>
	
	<p>Vous pouvez également accéder <a href="./">à l'éditeur de personnalité</a> ainsi qu'aux <a href="stats.php">statistiques</a>.</p>

	<?php
	foreach($logs as $jour => $heures)
	{
		ksort($heures);
		
		echo '<h2>Parties du ' . strftime('%d/%m/%y' ,$jour) . '</h2>';
		
		echo '<ul>';
		foreach($heures as $heure => $log)
		{
			echo '<li><a href="log_lire.php?log='.$log.'">Partie de '.strftime('%H:%M' ,$heure).'</a></li>';
		}
		echo '</ul>';
	}
	?>
	
	</body>
</html>
