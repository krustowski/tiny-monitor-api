all: info

info:
	@echo "\e[1;32m👾 Welcome to Tiny Monitor API 👾\n"

	@echo "🆘 \e[0;1mmake build\e[0m - build image"
	@echo "🆘 \e[0;1mmake docs\e[0m - build documentation"
	@echo "🆘 \e[0;1mmake push\e[0m - push image into the registry"
	@echo "🆘 \e[0;1mmake test\e[0m - test image\n"

docs:
	@echo "🔨 \e[1;32m Building documentation\e[0m"
	@bash ./bin/create_pdf.sh

build:
	@echo "🔨 \e[1;32m Building Docker image\e[0m"
	#@bash ./bin/build.sh
	@docker-compose build

rebuild:
	@docker-compose build && docker-compose up --detach

run:
	@echo "Running Docker image"
	@docker-compose up --detach

test:
	@echo "🔨 \e[1;32m Testing Docker image\e[0m"
	@bash ./bin/test.sh

push:
	@echo "no way!"
	#@echo "🔨 \e[1;32m Pushing image to DockerHub\e[0m"
	#@docker push krustowski/php74:latest

everything: docs build test push

.PHONY: info
