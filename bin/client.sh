#!/bin/bash
APIURL="http://dyndns.juricicjuraj.loc" #no trailing slash
DOMAINID=1
PRIVATEKEY="storage/keys/private.key"

################

token=$(curl -s "$APIURL/token/$DOMAINID?pure")
ip=$(curl -s "$APIURL")

data=$(mktemp)
echo "$token|$DOMAINID|$ip" > $data

signature=$(mktemp)
openssl dgst -sha256 -sign $PRIVATEKEY -out $signature $data
base64 $signature > $data       # Yes, we reuse data file 3:)

curl -H "Accept: application/json" -X POST --data "ipAddress=$ip" --data-urlencode "signature=$(cat $data)" "$APIURL/update/$DOMAINID"

# Cleanup
rm $data $signature

#echo "{token}|{domainId}|{ipAddress}" > temp.txt
#openssl dgst -sha256 -sign "private_key.key" -out sign.txt.sha256 temp.txt
#rm temp.txt