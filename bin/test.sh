#!/bin/bash

. .env

ENDPOINT="http://localhost:${API_PORT}/api/v1/GetSystemStatus?apikey=${SUPERVISOR_APIKEY}"

curl -sL ${ENDPOINT}