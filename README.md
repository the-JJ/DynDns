# JJ's DynDNS

## Requirements
- OpenSSL private / public key pair for signing tokens. The following will generate private key `private_key.key` and associated public key `public_key.pem`:

    ```
    openssl genpkey -algorithm RSA -out private_key.key -pkeyopt rsa_keygen_bits:4096
    openssl rsa -pubout -in private_key.key -out public_key.pem
    ```

## Installation
1.  Run `composer install`
2.  Set up your webserver to point to Silex's webroot (see: http://silex.sensiolabs.org/doc/2.0/web_servers.html)
3.  Set up PowerDNS with MySQL backend (see: https://blog.heckel.xyz/2016/12/31/your-own-dynamic-dns-server-powerdns-mysql/)
4.  Copy your public key to `storage/keys/ID.pem`, where `ID` is the domain ID for which the key is associated

## Usage
- Client sends a token request to the server: `GET /token/{domainId}`
- Server responds with s token JSON response:

    ```
    {"token":"DZhFhCStJyQnSpkZp79NzhCiRcpRoAvrGflVTCpYFJE=","ip":"214.133.21.54"}
    ```

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

The client is available at `bin/client.sh`. Set up environment variables `APIURL`, `DOMAINID`, and `PRIVATEKEY`.