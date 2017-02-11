#!/bin/bash
openssl genpkey -algorithm RSA -out pdns_private.key -pkeyopt rsa_keygen_bits:4096
openssl rsa -pubout -in pdns_private.key -out pdns_public.pem