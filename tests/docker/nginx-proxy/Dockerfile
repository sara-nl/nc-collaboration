FROM nginx:1.25.2 as builder

COPY nc-1/nc-1.nl/certs /etc/nginx/certs/nc-1
COPY nc-1/nc-1.nl/nc-1-nginx-proxy.conf /etc/nginx/conf.d/nc-1.conf

COPY nc-2/nc-2.nl/certs /etc/nginx/certs/nc-2
COPY nc-2/nc-2.nl/nc-2-nginx-proxy.conf /etc/nginx/conf.d/nc-2.conf
