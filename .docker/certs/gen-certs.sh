#!/usr/bin/env bash

# Domain names to generate certificates for
# main CN:
DNS=dev.com 
# altnames:
DNS2=dev.local.com 
DNS3=localhost

# Create Root CA (Done once)
FILE1=rootCA.key
FILE2=rootCA.crt
FILE3=cert.key
FILE4=cert.csr
FILE5=cert.crt

if [ -f "$FILE1" ]; then
    echo "rootCA key already exists, skipping..."
else 
    echo "creating Root Key"
    ## Create Root Key
    openssl genrsa -out rootCA.key 4096
fi

if [ -f "$FILE2" ]; then
    echo "rootCA cert already exists, skipping..."
else 
    echo "creating Root certificate"
    ## Create and self sign the Root Certificate (then add it to your browser, keychain or wherever needed to make trusted - rootCA.crt )
    openssl req -x509 -new -nodes -subj "/C=US/ST=NY/L=New York/O=Monkeyknifefight.com/CN=$DNS" -key rootCA.key -sha256 -days 1024 -out rootCA.crt 
fi

# Create a certificate (Done for each server)

if [ -f "$FILE3" ]; then
    echo "cert key already exists, skipping..."
else 
    ## Create the certificate key
    openssl genrsa -out cert.key 2048
fi


if [ -f "$FILE4" ]; then
    echo "signing request already exists, skipping..."
else 
    ## Create signing request (csr)
    openssl req -new -sha256 \
        -key cert.key \
        -subj "/C=US/ST=NY/O=Monkeyknifefight.com, Inc./CN=$DNS" \
        -reqexts SAN \
        -config <(cat /etc/ssl/openssl.cnf \
            <(printf "\n[SAN]\nsubjectAltName=DNS:$DNS,DNS:$DNS2,DNS:$DNS3")) \
        -out cert.csr
fi

if [ -f "$FILE5" ]; then
    echo "certificate already exists, skipping..."
else 
    # Generate the certificate using the csr and key along with the CA Root key
    # openssl x509 -req -in cert.csr -CA rootCA.crt -CAkey rootCA.key -CAcreateserial -out cert.crt -days 500 -sha256 -extfile v3.ext
    openssl x509 -req -in cert.csr -CA rootCA.crt -CAkey rootCA.key -CAcreateserial -out cert.crt -days 500 -sha256 \
        -extfile <(cat /etc/ssl/openssl.cnf <(printf "[SAN]\nsubjectAltName=DNS:$DNS,DNS:$DNS2,DNS:$DNS3")) \
        -extensions SAN
fi