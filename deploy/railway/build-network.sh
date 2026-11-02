# Optional build-only CA for restricted CI networks. It is never installed in the runtime trust store.
AR_BUILD_CA_FILE=/etc/ssl/certs/ca-certificates.crt
if [ -f /run/secrets/build_ca ]; then
    AR_BUILD_CA_FILE=/tmp/african-ranked-build-ca.crt
    if [ -f /etc/ssl/certs/ca-certificates.crt ]; then
        cat /etc/ssl/certs/ca-certificates.crt /run/secrets/build_ca > "$AR_BUILD_CA_FILE"
    else
        cp /run/secrets/build_ca "$AR_BUILD_CA_FILE"
    fi
    export SSL_CERT_FILE="$AR_BUILD_CA_FILE" CURL_CA_BUNDLE="$AR_BUILD_CA_FILE" COMPOSER_CAFILE="$AR_BUILD_CA_FILE"
    export NODE_EXTRA_CA_CERTS=/run/secrets/build_ca
fi
