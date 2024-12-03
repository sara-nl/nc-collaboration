FROM php:8.2-cli

RUN apt update \
    && apt install -y curl

COPY nc-1/nc-1.nl/certs/cert.pem /usr/share/ca-certificates/rd-mesh/nc-1.pem
RUN echo 'rd-mesh/nc-1.pem' >> /etc/ca-certificates.conf \
    && update-ca-certificates \
    && export CURL_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt

COPY nc-2/nc-2.nl/certs/cert.pem /usr/share/ca-certificates/rd-mesh/nc-2.pem
RUN echo 'rd-mesh/nc-2.pem' >> /etc/ca-certificates.conf \
    && update-ca-certificates \
    && export CURL_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt

RUN mkdir -p /tmp/tests
COPY integration-tests/ /tmp/tests/
RUN chmod u+r+x  /tmp/tests/tests.sh

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN cd /tmp/tests/src && composer install

WORKDIR /
