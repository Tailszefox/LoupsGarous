<?php
$phpbb_root_path = '/www/Forum/';
require_once('/www/is_connected.php');
if((!$connected || ($_GET['type'] != 'accepted' && $_GET['type'] != 'awaiting') || !is_numeric($_GET['id'])))
	die('?_?');

if($_GET['type'] == 'accepted')
	$fichier = './personnalites/accepted/'.$user->data['user_id'].'_'.$_GET['id'].'.xml';
elseif($_GET['type'] == 'awaiting')
	$fichier = './personnalites/awaiting/'.$user->data['user_id'].'_'.$_GET['id'].'.xml';

if(!file_exists($fichier))
	die('Fichier introuvable. Si vous êtes arrivé sur cette page en cliquant sur un lien, contactez Meuh.');
unlink($fichier);
header('Location: index.php?type=deleted');
?>
