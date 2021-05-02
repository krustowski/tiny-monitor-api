#!/bin/bash

#
# tiny-monitor-api / unit tests
#

[[ -z ${RUN_FROM_MAKEFILE} ]] && \
    echo "This script has to be run by 'make test'!" &&
    exit 1

[[ ! -f ${APIKEY_FILE} ]] \
    && echo "API key file '${APIKEY_FILE}' not found! Run 'make deploy' and try again!"
    
export SUPERVISOR_APIKEY=$(cat ${APIKEY_FILE})

COUNTER=0

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

    ENDPOINT="http://localhost:${APP_EXPOSE_PORT}/api/v2/${FUNCTION}?apikey=${SUPERVISOR_APIKEY}"
    COUNTER=$((COUNTER+1))

    echo -e "\n [${COUNTER}] ${BLUE}Calling ${FUNCTION} (expecting: ${EXPECTED})...${RESET}\n"

    # prefinal curl check
    [[ $(curl -sL ${ENDPOINT} 2>&1 > /dev/null; echo $?) -gt 0 ]] && \
        echo -e " ${RED}cannot connect to API/API error ...${RESET}\n" && \
        exit 1

    # final call
    curl -sL -d "${PAYLOAD}" ${ENDPOINT} | \
        jq '. | "message: " + .api.message, .data'

    unset FUNCTION PAYLOAD && \
        printf "\n" && \
        sleep 1.5
}

#
# tests
#

# match default case
call RandomGibberish "unknown function"

# get system status
call GetSystemStatus "ok"

# create group, get GROUP_ID
call AddGroup "ok" test/AddGroup.json

# create the same => already exists
call AddGroup "exists!" test/AddGroup.json

# list groups
call GetGroupList "ok"

# get group detail
call GetGroupDetail "ok" test/GetGroupDetail.json

# rename group
call SetGroupDetail "ok" test/SetGroupDetail.json

# get group detail
call GetGroupDetail "ok" test/GetGroupDetail.json

# create user, get APIKEY, USER_ID
call AddUser "ok" test/AddUser.json

# create the same => already exists
call AddUser "exists!" test/AddUser.json

# list users
call GetUserList "ok"

# get user detail
call GetUserDetail "ok" test/GetUserDetail.json

# rename user, change GROUP_ID, acl,...
call SetUserDetail "ok" test/SetUserDetail.json

# get user detail
call GetUserDetail "ok, new params" test/GetUserDetail.json

# add host, get HOST_ID
call AddHost "ok" test/AddHost.json

# create the same => already exists
call AddHost "exists!" test/AddHost.json

# get host list
call GetHostList "ok"

# get host detail
call GetHostDetail "ok" test/GetHostDetail.json

# rename host, reset GROUP_ID
call SetHostDetail "ok" test/SetHostDetail.json

# get host detail
call GetHostDetail "ok" test/GetHostDetail.json

# add service, get SERVICE_ID
call AddService "ok" test/AddService.json 

# create the same => already exists
call AddService "exists!" test/AddService.json 

# list services
call GetServiceList "ok"

# get service detail
call GetServiceDetail "ok" test/GetServiceDetail.json

# change service, set HOST_ID
call SetServiceDetail "ok" test/SetServiceDetail.json

# get service detail
call GetServiceDetail "ok" test/GetServiceDetail.json

# run test on service as USER_ID (APIKEY)
call TestService "ok" test/TestService.json

# get service status
call GetServiceStatus "ok" test/GetServiceDetail.json

# get service status detail
#call GetServiceStatusDetail "ok"

# change activated on service
call SetServiceDetail "ok" test/SetServiceDetailDisable.json

# run the same test => fail on inactive service
call TestService "fail!" test/TestService.json

# remove service
call DeleteService "ok" test/DeleteService.json

# remove service => not exists
call DeleteService "not exists!" test/DeleteService.json

# get service list
call GetServiceList "ok + empty list"

# remove host
call DeleteHost "ok" test/DeleteHost.json

# remove host => not exists
call DeleteHost "not exists!" test/DeleteHost.json

# get host list
call GetHostList "ok + empty list"

# remove user
call DeleteUser "ok" test/DeleteUser.json

# remove user => not exists
call DeleteUser "not exists!" test/DeleteUser.json

# get user list
call GetUserList "ok + empty list"

# remove group
call DeleteGroup "ok" test/DeleteGroup.json

# remove group => not exists
call DeleteGroup "not exists!" test/DeleteGroup.json

# get group list
call GetGroupList "ok + empty list"
