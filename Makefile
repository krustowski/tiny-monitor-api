# tiny-monitor-api Makefile

-include .env

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

	@echo "${YELLOW} make install${RESET} \t build and run the container"
	@echo "${YELLOW} make build${RESET} \t build core image"
	@echo "${YELLOW} make rebuild${RESET} \t rebuild image and restart the container"
	@echo "${YELLOW} make test${RESET}  \t test the application/container\n"

install: build, run, test

build:
	@echo "\n${YELLOW} Building the image...${RESET}\n"
	#@bash ./bin/build.sh
	@docker-compose build

rebuild:
	@echo "\n${YELLOW} Rebuilding and reruning the container...${RESET}\n"
	@git pull 2> /dev/null && docker-compose build && docker-compose up --detach

start:
run:
	@echo "\n${YELLOW} Starting the container...${RESET}\n"
	@docker-compose up --detach

test:
	@echo "\n${YELLOW} Testing the application/container...${RESET}\n"
	@bash ./bin/test.sh

