# Tiny Monitor API

## Makefile

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
