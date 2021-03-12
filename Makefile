# tiny-monitor-api Makefile

-include .env

DOCKER_EXEC_COMMAND=bash

# define standard colors
# https://gist.github.com/rsperl/d2dfe88a520968fbc1f49db0a29345b9
ifneq (,$(findstring xterm,${TERM}))
	BLACK        := $(shell tput -Txterm setaf 0)
	RED          := $(shell tput -Txterm setaf 1)
	GREEN        := $(shell tput -Txterm setaf 2)
	YELLOW       := $(shell tput -Txterm setaf 3)
	LIGHTPURPLE  := $(shell tput -Txterm setaf 4)
	PURPLE       := $(shell tput -Txterm setaf 5)
	BLUE         := $(shell tput -Txterm setaf 6)
	WHITE        := $(shell tput -Txterm setaf 7)
	RESET        := $(shell tput -Txterm sgr0)
else
	BLACK        := ""
	RED          := ""
	GREEN        := ""
	YELLOW       := ""
	LIGHTPURPLE  := ""
	PURPLE       := ""
	BLUE         := ""
	WHITE        := ""
	RESET        := ""
endif

all: info

info:
	@echo "\n${GREEN} tiny-monitor-api Makefile ${RESET}\n"

	@echo "${YELLOW} make config${RESET}  \t check the local environment (to develop/deploy)"
	@echo "${YELLOW} make deploy${RESET} \t (re)build, run and test the container"
	@echo "${YELLOW} make test${RESET}  \t test the application/container"
	@echo "${YELLOW} make exec${RESET}  \t execute command in container (def. bash)"
	@echo "${YELLOW} make call${RESET}  \t make an API call\n"
#@echo "${YELLOW} make build${RESET} \t build core image"
#@echo "${YELLOW} make redeploy${RESET} \t rebuild image, restart and test the container\n"
#@echo "${YELLOW} make test${RESET}  \t test the application/container\n"

config:
	@echo "\n${YELLOW} Checking and configuring the local environment ...${RESET}\n"
	@bash ./bin/config.sh

deploy: build run test

build:
	@echo "\n${YELLOW} Building the image ...${RESET}\n"
	@docker-compose build \
		&& exit 0 \
		|| echo "\n${RED} docker is not running!${RESET}\n"; exit 1

run:
	@echo "\n${YELLOW} Starting the container ...${RESET}\n"
	@docker-compose up --detach

test:
	@echo "\n${YELLOW} Testing the application/container ...${RESET}\n"
	@bash ./bin/test.sh

exec:
	@echo "\n${YELLOW} Executing '${DOCKER_EXEC_COMMAND}' in container ...${RESET}\n"
	@`which docker` exec -it ${CONTAINER_NAME} ${DOCKER_EXEC_COMMAND}

errorlog:
	@echo "\n${YELLOW} Docker logs ...${RESET}\n"
	@docker logs ${CONTAINER_NAME}
	@echo "\n${YELLOW} Nginx error.log ...${RESET}\n"
	@docker exec -i ${CONTAINER_NAME} cat /var/log/nginx/error.log

