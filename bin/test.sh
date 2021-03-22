#!/bin/bash

#
# tiny-monitor-api / unit tests
#

[[ -z ${RUN_FROM_MAKEFILE} ]] && \
    echo "This script has to be run by 'make'!" &&
    exit 1

function call() {
    [[ -z ${FUNCTION} ]] && \
        echo "No function specified!" && \
        return 1

    [[ -f ${JSON_FILE} ]] \
        && PAYLOAD="$(cat ${JSON_FILE})" \
        || PAYLOAD='{}'

    echo -e "\n ${BLUE}Calling ${FUNCTION} ...${RESET}\n"

    ENDPOINT="http://localhost:${TM_API_PORT}/api/v2/${FUNCTION}?apikey=${SUPERVISOR_APIKEY}"
    curl -sL -d "${PAYLOAD}" ${ENDPOINT} | echo " $(jq '.api.message')"

    unset FUNCTION PAYLOAD && \
        printf "\n" && \
        sleep 2
}

#
# tests
#

# get system status

#call GetSystemStatus

FUNCTION=GetSystemStatus \
    call 

# create group, get GROUP_ID

#call AddGroup test/AddGroup.json

FUNCTION=AddGroup \
    JSON_FILE=test/AddGroup.json \
    call 

# create the same => already exists

FUNCTION=AddGroup \
    JSON_FILE=test/AddGroup.json \
    call

# get group detail

FUNCTION=GetGroupDetail \
    call

# rename group

FUNCTION=SetGroupDetail \
    JSON_FILE=test/SetGroupDetail.json \
    call

# get group detail

FUNCTION=GetGroupDetail \
    call

# list groups

FUNCTION=GetGroupList \
    call

# create user, get APIKEY, USER_ID

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# create the same => already exists

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# get user detail

FUNCTION=GetUserDetail \
    call

# rename user, change GROUP_ID, acl,...

FUNCTION=SetUserDetail \
    JSON_FILE=test/SetUserDetail.json \
    call

# get user detail

FUNCTION=GetUserDetail \
    call

# add host, get HOST_ID

FUNCTION=AddHost \
    JSON_FILE=test/AddHost.json \
    call

# create the same => already exists

FUNCTION=AddHost \
    JSON_FILE=test/AddHost.json \
    call

# get host detail

FUNCTION=GetHostDetail \
    call

# rename host, reset GROUP_ID

FUNCTION=SetHostDetail \
    JSON_FILE=test/SetHostDetail.json \
    call

# get host detail

FUNCTION=GetHostDetail \
    call

# add service, get SERVICE_ID

FUNCTION=AddService \
    JSON_FILE=test/AddService.json \
    call

# create the same => already exists

FUNCTION=AddService \
    JSON_FILE=test/AddService.json \
    call

# get service detail

FUNCTION=GetServiceDetail \
    call

# change service, set HOST_ID

FUNCTION=SetServiceDetail \
    JSON_FILE=test/SetServiceDetail.json \
    call

# get service detail

FUNCTION=GetServiceDetail \
    JSON_FILE=test/GetServiceDetail.json \
    call

# run test on service as USER_ID (APIKEY)

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# get service status

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# get service status detail

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# change activated on service

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# run the same test => fail on inactive service

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# remove service

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# remove service => not exists

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# remove host

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# remove host => not exists

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# remove user

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# remove user => not exists

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# remove group

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call

# remove group => not exists

FUNCTION=AddUser \
    JSON_FILE=test/AddUser.json \
    call