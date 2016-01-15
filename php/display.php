<?php
$request = explode('/', $_SERVER['REQUEST_URI']);
$filename = $request[sizeof($request)-1];

if(substr($filename, -4) != '.xml')
    die('Nom de fichier invalide');

if(! file_exists('./personnalites/accepted/' . $filename))
    die($filename . ' n\'existe pas');

header('Content-Type: application/octet-stream');
header('Content-Transfer-Encoding: Binary');
header('Content-disposition: attachment; filename="' . $filename . '"');

readfile('./personnalites/accepted/' . $filename);
?>