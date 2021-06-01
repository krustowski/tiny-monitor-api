# Tiny Monitor API (pre-release v1.9)

Public status page:

https://mon.n0p.cz/status

## API documentation

### Production example

https://mon.n0p.cz/doc

For auth the supervisor API key is required (for now). Better deploy it locally to be able to fully test it through.

### Locally run example

```
# have docker engine installed

# deploy app
make deploy 

# run unit tests
make test

# check .env file for SWAGGER_EXPOSE_PORT and open it locally in browser
http://localhost:${SWAGGER_EXPOSE_PORT}/
```

## Makefile

Show the structure of Makefile targets
```
make
```

### config

Check and configure the host environment (docker, docker-compose, curl,...)
```
make config
make config ENV=devel
```

### deploy

Deploy the app (build, run and test the image) to docker
```
make deploy
```

### test

Run unit tests – tries to run basic scenario with all API calls used at a time 
```
make test
```

### call

Test the API calls, even ones requiring the JSON payload!
```
make call FUNCTION=GetSystemStatus
make call FUNCTION=AddGroup JSON_FILE=test/AddGroup.json
```

### doc

Generate API documentation to PDF
```
make doc
```

# notes from 27/02/21

## Getters

```
GetStatus
GetDetail
GetGroups
GetUsers
GetHosts
```

## Setters

```
AddUser
AddUserGroup
AddHost
AddService
AddHostGroup

SetUser
SetUserGroup
SetHost
SetService
SetHostGroup
```

## System calls

```
StopAll
StartAll
```

## Features TODO

– downtime per service (optional)
– telegram_chat_id per group
– user permissions (super, power, basic)
