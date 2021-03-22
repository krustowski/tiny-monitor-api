#!/bin/bash

#
# tiny-monitor-api / unit tests
#

[[ -z ${RUN_FROM_MAKEFILE} ]] && \
    echo "This script has to be run by 'make test'!" &&
    exit 1

function call() {
    [[ -z $1 ]] \
        && echo "Function not specified!" \
        && return 1

    FUNCTION=$1

    [[ -z $2 ]] \
        && echo "Expected output not specified!" \
        && return 1

    EXPECTED=$2

    [[ -n $3 && -f $3 ]] \
        && PAYLOAD="$(cat $3)" \
        || PAYLOAD='{}'

    echo -e "\n ${BLUE}Calling ${FUNCTION} (expecting: ${EXPECTED})...${RESET}\n"

    ENDPOINT="http://localhost:${TM_API_PORT}/api/v2/${FUNCTION}?apikey=${SUPERVISOR_APIKEY}"
    curl -sL -d "${PAYLOAD}" ${ENDPOINT} | echo " $(jq '.api.message')"

    unset FUNCTION PAYLOAD && \
        printf "\n" && \
        sleep 1.5
}

#
# tests
#

# get system status

call GetSystemStatus "ok"

# create group, get GROUP_ID

call AddGroup "ok" test/AddGroup.json

# create the same => already exists

call AddGroup "exists!" test/AddGroup.json

# get group detail

call GetGroupDetail "ok"

# rename group

call SetGroupDetail "ok" test/SetGroupDetail.json

# get group detail

call GetGroupDetail "ok"

# list groups

call GetGroupList "ok"

# create user, get APIKEY, USER_ID

call AddUser "ok" test/AddUser.json

# create the same => already exists

call AddUser "exists!" test/AddUser.json

# get user detail

call GetUserDetail "ok"

# rename user, change GROUP_ID, acl,...

call SetUserDetail "ok" test/SetUserDetail.json

# get user detail

call GetUserDetail "ok"

# add host, get HOST_ID

call AddHost "ok" test/AddHost.json

# create the same => already exists

call AddHost "exists!" test/AddHost.json

# get host detail

call GetHostDetail "ok"

# rename host, reset GROUP_ID

call SetHostDetail "ok" test/SetHostDetail.json

# get host detail

call GetHostDetail "ok"

# add service, get SERVICE_ID

call AddService "ok" test/AddService.json 

# create the same => already exists

call AddService "exists!" test/AddService.json 

# get service detail

call GetServiceDetail "ok"

# change service, set HOST_ID

call SetServiceDetail "ok" test/SetServiceDetail.json

# get service detail

call GetServiceDetail "ok"

# run test on service as USER_ID (APIKEY)

call TestService "ok" test/TestService.json

# get service status

call GetServiceStatus "ok"

# get service status detail

call GetServiceStatusDetail "ok"

# change activated on service

call SetServiceDetail "ok" test/SetServiceDetailDisable.json

# run the same test => fail on inactive service

call TestService "fail!" test/TestService.json

# remove service

call DeleteService "ok" test/DeleteService.json

# remove service => not exists

call DeleteService "not exists!" test/DeleteService.json

# remove host

call DeleteHost "ok" test/DeleteHost.json

# remove host => not exists

call DeleteHost "not exists!" test/DeleteHost.json

# remove user

call DeleteUser "ok" test/DeleteUser.json

# remove user => not exists

call DeleteUser "not exists!" test/DeleteUser.json

# remove group

call DeleteGroup "ok" test/DeleteGroup.json

# remove group => not exists

call DeleteGroup "not exists!" test/DeleteGroup.json
