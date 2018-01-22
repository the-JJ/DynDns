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
    - if you decide to use password version for this domain, create `storage/keys/ID.password` instead.
5.  Set up config files (located in `config` directory)

## Usage (keypair version)
- Client sends a token request to the server: `GET /token/{domainId}`
- Server responds with s token JSON response:

    ```
    {"token":"DZhFhCStJyQnSpkZp79NzhCiRcpRoAvrGflVTCpYFJE=","ip":"123.45.67.89"}
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

## Simple (password-based) version
This is a simpler, less secure option. Instead of signing a request, client simply sends a hashed password to the server. Use this only over SSL.

- Client sends an update request to the server: `POST /update-simple/{domainId}`, with the following body:

    ```
    ipAddress: {ipAddress}
    password: {password}
    ```
    The `ipAddress` parameter can be omitted if the IP address used is the client's public IP address.

The simple client is available at `bin/client-simple.sh`. Set up environment variables `APIURL`, `DOMAINID`, and `PASSWORDHASH`.

# API endpoints

## GET /myip
Returns client's IP address.

### Example response
    ```
    123.45.67.89
    ```

## GET /domain/:domainId:
Returns the JSON response containing all NS records for the domain with given domainId.

### Example reponse

    ```json
    [
      {
        "domain_id": "1",
        "name": "domain.dyndns.juricicjuraj.loc",
        "type": "SOA",
        "content": "ns.dyndns.juricicjuraj.loc noreply.dyndns.juricicjuraj.loc 1486776963 60 60 60 60",
        "ttl": "60",
        "change_date": "1486776963"
      },
      {
        "domain_id": "1",
        "name": "domain.dyndns.juricicjuraj.loc",
        "type": "A",
        "content": "123.45.67.89",
        "ttl": "60",
        "change_date": "1486776963"
      }
    ]
    ```

## GET /token/:domainId:
Gets the authentication token for the given domain id. Returns token and current IP address in JSON format by default.

### Query parameters
    - `pure` - if set, will return just the token in plain text format. 

### Example response:
    ```json
    {
      "token": "nZhFhCStJyQnSakZp79NzhCiRcp0oAvrGflVTCp9FJE=",
      "ip": "133.44.55.66"
    }
    ```

With ?pure=1:
    ```
    nZhFhCStJyQnSakZp79NzhCiRcp0oAvrGflVTCp9FJE=
    ```

## POST /update/:domainId:
Pushes the NS update to server. The server will only accept the request if generated token (requested via `GET /token/:domainId:`) is successfully signed.

The message to sign should be in the following format (ending with \n):
```
{token}|{domainId}|{ipAddress}\n
```

### Example request body:
    ```
    signature:yi4KiTZYTqxzI9jGszje2fONa2RSlYNXjnNuuuDzVhYEnq/KFUF+CgSuGvSSu0pBDBDO5blbTbvQjsq9dzE8H1/xmsy/KMre3OlgdyHWRsOdVk2sm8LeCa+8JT1ZflF6k4eJjYS5qlV3F+3mjjuiqk/6rSw//i8IVWzZDcAUr+Q=
    ```

### Example response:
    ```
    ok
    ```

## POST /update-simple/:domainId:
Pushes the NS update to server. The server will only accept the request if it contains a valid password, and password file exists for the selected domain.

For simplicity, keep the password to alphanumeric characters (but it can be long).

### Example request body:
    ```
    password:ThisIsMyPassword
    ```

### Example response:
    ```
    ok
    ```
