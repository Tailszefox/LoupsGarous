#!/bin/bash

# Lance le bot, redirige l'output et le fait redémarrer en cas de plantage

cd /etc/unrealircd/loup/
killall loup.py

echo $$ > pid.pid

while [ 1 ]
do
	date
	./loup.py -r
	sleep 5
done
