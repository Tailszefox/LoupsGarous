#!/bin/bash

echo "Script d'installation du bot IRC Loups Garous"
echo ""

echo "Réglage du bit d'exécution sur les scripts Python"
chmod -v u+x ./loup.py
chmod -v u+x ./setup/*.py
echo ""

echo "Vérification de la présence de Python..."
if ! [ -x "$(command -v python2)" ]; then
    echo "Python 2.X n'est pas installé ou n'existe pas dans le PATH. Veuillez installer Python >= 2.7 et vous assurer qu'il est présent dans le PATH."
    exit 1
fi
echo "Python 2 trouvé, version :"
python2 --version
echo ""

echo "Vérification de la présence des modules nécessaires..."
./setup/check_irclib.py
if [[ $? -eq 1 ]]; then
    exit 1
fi
echo "Modules installés."
echo ""

if [[ -f ./prefs.ini ]]; then
    echo "Le fichier de configuration existe déjà."
else
    echo "Copie du fichier de configuration d'exemple..."
    cp -v ./prefs.example.ini ./prefs.ini

    echo "Veuillez maintenant ouvrir le fichier prefs.ini et y renseigner les variables nécessaires. Appuyez sur Entrée une fois que vous avez terminé."
    read z
fi
echo ""

echo "Lecture du fichier de configuration"
./setup/check_prefs.py
echo "Vérifiez les valeurs renseignées, puis appuyez sur Entrée pour continuer."
read z
echo ""

echo "Vous devez maintenant préparer quelques éléments sur le serveur IRC"
echo "1- Connectez vous au serveur en utilisant le pseudo que vous avez défini pour le bot"
echo "2- Enregistrez le pseudo après de nickserv avec le mot de passe défini"
echo "3- Enregistrez les trois canaux (canal principal, canal des loups, canal des morts) auprès de chanserv"
echo "Une fois que tout est fait, appuyez sur Entrée pour continuer."
read z
echo ""

echo "Le bot va maintenant être lancé. Si une erreur apparaît, corrigez-la avant de continuer. Utilisez Ctrl+C pour arrêter le bot."
./loup.py

echo ""
echo "L'installation est terminée. Vous pouvez exécuter ./loup.py pour lancer le bot, ou utiliser le script ./launch.sh pour le forcer à redémarrer en cas de plantage."
