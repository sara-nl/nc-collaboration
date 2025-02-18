#!/bin/bash

docker container stop nginx-proxy
docker container stop nc-1
docker container stop nc-2
docker container stop mariadb-nc-1
docker container stop mariadb-nc-2
docker system prune -f
docker volume rm -f docker_files-nc-1
docker volume rm -f docker_mysql-nc-1
docker volume rm -f docker_files-nc-2
docker volume rm -f docker_mysql-nc-2
