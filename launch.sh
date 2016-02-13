#!/bin/bash
cd /etc/unrealircd/loup/
killall loup.py

echo $$ > pid.pid

while [ 1 ]
do
	date
	./loup.py
	sleep 5
done
