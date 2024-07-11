#!/bin/bash

docker compose -f docker-compose-local.yaml stop
docker system prune -f
docker volume rm -f docker_files-nc-1
docker volume rm -f docker_mysql-nc-1
