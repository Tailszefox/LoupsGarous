<?php
$phpbb_root_path = '/www/Forum/';
require_once('/www/is_connected.php');
if($_GET['type'] == 'admin' && $user->data['user_id'] != 3)
	die('?_?');
if(($_GET['type'] != 'admin') && (!$connected || ($_GET['type'] != 'accepted' && $_GET['type'] != 'awaiting' && $_GET['type'] != 'new') || !is_numeric($_GET['id'])))
	die('?_?');
$_POST = array_map('trim', $_POST);

$xmlstr = '<caractere></caractere>';

$xml = new SimpleXMLElement($xmlstr);
$default = simplexml_load_file('./personnalites/default/default.xml');

//Ajout du nom de la personnalité
if(empty($_POST['nom']))
	die('Votre personnalité n\'a pas de nom ! Retournez en arrière et renseignez-le. Et activez Javascript !');
$xml->addChild('nom', $_POST['nom']);

//Ajout de la raison du refus (si y'en a une)
if($_GET['type'] == 'admin' && !empty($_POST['raison']))
	$xml->addChild('raison', $_POST['raison']);

//Ajout des rôles
$xml->addChild('roles');
foreach($default->roles->role as $role)
{
	if (!empty($_POST['role_' . $role['nom']]))
	{
		$new = $xml->roles->addChild('role', str_replace('&', ' &amp; ', $_POST['role_' . $role['nom']]));
		$new->addAttribute('nom', $role['nom']);
	}
}

//Ajout des déclencheurs
$xml->addChild('declencheurs');
foreach($default->declencheurs->declencheur as $declencheur)
{
	if (!empty($_POST['declencheur_' . $declencheur['nom']]))
	{
		if($_POST['declencheur_' . $declencheur['nom']][0] != '!')
			$_POST['declencheur_' . $declencheur['nom']] = '!' . $_POST['declencheur_' . $declencheur['nom']];
		$new = $xml->declencheurs->addChild('declencheur', str_replace('&', ' &amp; ', $_POST['declencheur_' . $declencheur['nom']]));
		$new->addAttribute('nom', $declencheur['nom']);
	}
}

//Ajout des répliques
$xml->addChild('repliques');
foreach($default->repliques->dire as $replique)
{
	$nb = 0;
	if (!empty($_POST[$replique->cle . '_0']))
	{
		$new = $xml->repliques->addChild('dire');
		$new->addChild('cle', $replique->cle);
		$new->addChild('phrases');
		while (!empty($_POST[$replique->cle . '_' . $nb]))
		{
			$new->phrases->addChild('phrase', str_replace('&', '&amp;', $_POST[$replique->cle . '_' . $nb]));
			$nb++;
		}
	}
	if($_POST['save_and_submit'] && $nb == 0)
		die('La phrase '.$replique->cle.' n\'a pas été renseignée. Vous devez remplir toutes les phrases avant de demander une validation. Retournez en arrière et remplissez tous les champs.<br />Si vous n\'avez pas le temps, sauvegardez seulement votre personnalité et revenez-y plus tard. Ah, et au fait, activez Javascript !');
}

$doc = new DOMDocument('1.0');
$doc->formatOutput = true;
$doc->encoding = "iso-8859-15";
$domnode = dom_import_simplexml($xml);
$domnode = $doc->importNode($domnode, true);
$domnode = $doc->appendChild($domnode);
file_put_contents('./personnalites/'.$user->data['user_id'].'_'.$_GET['id'].'.xml', stripslashes($doc->saveXML()));
chmod('./personnalites/'.$user->data['user_id'].'_'.$_GET['id'].'.xml', 0666);
if ($_GET['type'] == 'accepted')
{
	if(!file_exists('./personnalites/accepted/'.$user->data['user_id'].'_'.$_GET['id'].'.xml'))
		die('Petit malin.');
	rename('./personnalites/'.$user->data['user_id'].'_'.$_GET['id'].'.xml', './personnalites/accepted/'.$user->data['user_id'].'_'.$_GET['id'].'.xml');
	header('Location: index.php?type=save');
}
else if($_GET['type'] == 'admin')
{
	unlink('./personnalites/pending/' . $_GET['id']);
	if(empty($_POST['raison']))
	{
		rename('./personnalites/'.$user->data['user_id'].'_'.$_GET['id'].'.xml', './personnalites/accepted/'.$_GET['id']);
		header('Location: index.php?type=accepted');
	}
	else
	{
		rename('./personnalites/'.$user->data['user_id'].'_'.$_GET['id'].'.xml', './personnalites/awaiting/'.$_GET['id']);
		header('Location: index.php?type=refused');
	}
}
else
{
	if(isset($_POST['save']))
	{
		rename('./personnalites/'.$user->data['user_id'].'_'.$_GET['id'].'.xml', './personnalites/awaiting/'.$user->data['user_id'].'_'.$_GET['id'].'.xml');
		header('Location: index.php?type=save');
	}
	elseif(isset($_POST['save_and_submit']))
	{
		if(file_exists('./personnalites/awaiting/'.$user->data['user_id'].'_'.$_GET['id'].'.xml'))
			unlink('./personnalites/awaiting/'.$user->data['user_id'].'_'.$_GET['id'].'.xml');
		if(file_exists('./personnalites/pending/'.$user->data['user_id'].'_'.$_GET['id'].'.xml'))
		{
			unlink('./personnalites/'.$user->data['user_id'].'_'.$_GET['id'].'.xml');
			header('Location: index.php?type=already');
		}
		else
		{
			rename('./personnalites/'.$user->data['user_id'].'_'.$_GET['id'].'.xml', './personnalites/pending/'.$user->data['user_id'].'_'.$_GET['id'].'.xml');
			$message = 'Le membre ' . $user->data['username'] . ' demande la validation de '.$_POST['nom'].'. Voir https://www.mariouniversalis.fr/loups/editer.php?type=admin&id='.$user->data['user_id'].'_'.$_GET['id'].'.xml';
			mail('tails@tailszefox.no-ip.com', 'Nouvelle personnalité', $message);
			header('Location: index.php?type=submit');
		}
	}
}
?>
