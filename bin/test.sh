#!/bin/bash

. .env

APIKEY=c7bf9e2731d58d986d145d036b9568947c5a59d7a1019b437b708accbd7a9e4d820ad1771a8e836848355f3a7a6a9796d8ebae67ef64cfee40d32738c0cc436e
ENDPOINT="http://localhost:${API_PORT}/api/v1/GetSystemStatus?apikey=${APIKEY}"

curl -sL ${ENDPOINT}