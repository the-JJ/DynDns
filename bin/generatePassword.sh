#!/bin/bash

echo -n "Password: "
read -s password

echo ""

echo -n "Domain id: "
read domainId

echo "<?php echo password_hash('$password', PASSWORD_BCRYPT);" | php > storage/keys/$domainId.password

echo "Created - check 'storage/keys/$domainId.password'"
