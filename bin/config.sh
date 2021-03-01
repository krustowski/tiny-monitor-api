#!/bin/bash

RED=$(tput setaf 1)
GREEN=$(tput setaf 2)
BLUE=$(tput setaf 6)
RESET=$(tput sgr0)

TOOLS=(
    docker-compose
    docker
    curl
    sqlite3
)

FAILS=0

#
# CHECK
#

for t in ${TOOLS[@]}; do
    printf "${BLUE} Checking '${t}' ...${RESET}"
    [[ ( -f $(which ${t}) ) && ( $(${t} --version) ) ]] \
        && echo "${GREEN} ok${RESET}" && continue \
        || echo "${RED} failed${RESET} (missing or not running)"; FAILS=$((FAILS+1));
done

printf "\n"
[[ ${FAILS} -eq 0 ]] && exit 0

#
# CONFIGURE
#

echo -e "\n${BLUE} Configuring the system...${RESET}"

# TODO
case $(uname) in
    Darwin)
        # brew install docker docker-compose curl sqlite3
        # brew install ${TOOLS[@]}
        ;;
    Linux)
        # apt install docker docker-compose curl sqlite3
        # yum install ...
        # pacman install ...
        # apk add ...
        ;;
    *)
        echo "${RED} Unsupported system...${RESET}"
        ;;
esac

printf "\n"