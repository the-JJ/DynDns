#!/bin/bash
APIURL=""
DOMAINID=
PASSWORD="" # keep this to alphanumeric characters

################

ip=$(curl -s "$APIURL/myip")

curl -H "Accept: application/json" -X POST --data "ipAddress=$ip" --data "password=$PASSWORD" "$APIURL/update-simple/$DOMAINID"
