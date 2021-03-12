#!/bin/bash

. .env || . ../.env

ENDPOINT="http://localhost:${API_PORT}/api/v2/GetSystemStatus?apikey=${SUPERVISOR_APIKEY}"

curl -sL ${ENDPOINT}