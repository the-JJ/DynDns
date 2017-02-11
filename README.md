# JJ's DynDNS

## Requirements
- OpenSSL private / public key pair for signing tokens. The following will generate private key `private_key.key` and associated public key `public_key.pem`:

```
openssl genpkey -algorithm RSA -out private_key.key -pkeyopt rsa_keygen_bits:4096
openssl rsa -pubout -in private_key.key -out public_key.pem
```

## Usage
- Client sends a token request to the server: `GET /token/{domainId}`; the server returns a token in JSON response
- Client signs the message digest (SHA256) in format `{token}|{domainId}|{ipAddress}\n` :

```
echo "{token}|{domainId}|{ipAddress}" > temp.txt
openssl dgst -sha256 -sign "private_key.key" -out sign.txt.sha256 temp.txt
rm temp.txt
```
- Client sends an update request to the server: `POST /update/{domainId}`, with the following body, where `{signed}` is the base64-encoded signed message:

```
ipAddress: {ipAddress}
signature: {signed}
```

The `ipAddress` parameter can be omitted if the IP address used is the client's public IP address.