#!/bin/bash

#
# tiny-monitor-api / unit tests
#

## structure
#
# get system status
# create group, get GROUP_ID
# create the same => already exists
# rename group
# create user, get APIKEY, USER_ID
# create the same => already exists
# get user detail
# rename user, change GROUP_ID, acl,...
# get user detail
# add host, get HOST_ID
# create the same => already exists
# get host detail
# rename host, reset GROUP_ID
# get host detail
# add service, get SERVICE_ID
# create the same => already exists
# get service detail
# change service, set HOST_ID
# get service detail
# run test on service as USER_ID (APIKEY)
# get service status
# get service status detail
# change activated on service
# run the same test => fail on inactive service
# remove service
# remove service => not exists
# remove host
# remove host => not exists
# remove user
# remove user => not exists
# remove group
# remove group => not exists



ENDPOINT="http://localhost:${API_PORT}/api/v2/GetSystemStatus?apikey=${SUPERVISOR_APIKEY}"

curl -sL ${ENDPOINT}