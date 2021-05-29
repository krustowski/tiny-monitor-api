# tiny-monitor-api Makefile

#
# VARS
#

-include .env

# make additional consts = excluded from .env
ENV?=deploy
DOCKER_EXEC_COMMAND?=${SHELL}
FUNCTION?=GetSystemStatus
JSON_FILE?=''
RUN_FROM_MAKEFILE?=true
SWAGGER_JSON_FILE?=doc/swagger.json

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

export

#
# TARGETS
#

.PHONY: all bin call clean composer doc docker info init key mods public run src test vendor

all: info

info:
	@echo -e "\n${GREEN} ${APP_NAME} / Makefile ${RESET}\n"

	@echo -e "${YELLOW} make config${RESET} \t check the local environment (to develop/deploy)"
	@echo -e "${YELLOW} make deploy${RESET} \t (re)build, run and test the container"
	@echo -e "${YELLOW} make test${RESET}   \t run unit tests on __existing__ container\n"

	@echo -e "${YELLOW} make doc${RESET}    \t generate API documentation"
#	@echo -e "${YELLOW} make scan${RESET}   \t scan built image for vulnerabilities (using snyk)"
	@echo -e "${YELLOW} make exec${RESET}   \t execute command in container (def. ${DOCKER_EXEC_COMMAND})"
	@echo -e "${YELLOW} make call${RESET}   \t make an API call\n"

	@echo -e "${YELLOW} make stop${RESET}   \t destroy the cluster/container stack"
	@echo -e "${YELLOW} make log${RESET}    \t show docker logs and nginx errorlog\n"

# deployment simplistic 'pipeline'
deploy: docker_pull git_pull composer key link_init_file doc build run call

config:
	@echo -e "\n${YELLOW} Checking and configuring local environment ...${RESET}\n"
	@bash `pwd`/bin/config.sh

docker_pull:
	@echo -e "\n${YELLOW} Pulling actual '${DOCKER_USED_IMAGE}' image from Docker Hub ...${RESET}\n"
	@docker pull ${DOCKER_USED_IMAGE}

git_pull:
	@echo -e "\n${YELLOW} Pulling from repository ...${RESET}\n"
	@git pull

composer:
	@echo -e "\n${YELLOW} Setting the 'vendor' dir using composer ...${RESET}\n"
	@composer update

key:
	@echo -e "\n${YELLOW} Generating new SUPERVISOR_APIKEY ...${RESET}\n"
	@bash `pwd`/bin/key.sh

link_init_file:
	@echo -e "\n${YELLOW} Linking app-related files and vars into '${INIT_APP_FILE}' ...${RESET}\n"
	@bash `pwd`/bin/link_init.sh

build:
	@echo -e "\n${YELLOW} Building docker image ...${RESET}\n"
	@docker-compose build \
		&& exit 0 \
		|| echo "\n${RED} docker is not running!${RESET}\n"; exit 1

run:
	@echo -e "\n${YELLOW} Starting container ...${RESET}\n"
	@docker-compose up --detach

test:
	@echo -e "\n${YELLOW} Running unit tests ...${RESET}\n"
	@bash `pwd`/bin/test.sh

doc:
	@echo -e "\n${YELLOW} Generating new documentation revision ...${RESET}\n"
	@mkdir -p doc && \
		./vendor/zircote/swagger-php/bin/openapi --format json src/Api.php --output doc/${SWAGGER_JSON_FILE} && \
		echo -e " ${GREEN}JSON file from OpenAPI annotation created.${RESET}"

call:
	@echo -e "\n${YELLOW} Making the API call ...${RESET}\n"
	@sleep 3
	@bash `pwd`/bin/call.sh

exec:
	@echo -e "\n${YELLOW} Executing '${DOCKER_EXEC_COMMAND}' in container ...${RESET}\n"
	@docker exec -it ${CONTAINER_NAME} ${DOCKER_EXEC_COMMAND}
	@exit 0

log:
	@echo -e "\n${YELLOW} Docker logs${RESET}\n"
	@docker logs ${CONTAINER_NAME}
#	@echo -e "\n${YELLOW} Nginx error.log${RESET}\n"
#	@docker exec -i ${CONTAINER_NAME} cat /var/log/nginx/error.log

stop:
	@echo -e "\n${YELLOW} Destroying the whole cluster ...${RESET}\n"
	@docker-compose down

