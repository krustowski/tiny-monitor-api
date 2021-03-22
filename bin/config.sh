#!/bin/bash

#
# VARS
#

DEVEL_TOOLS=""

if [[ -n ${ENV} && ${ENV} == "devel" ]]; then 
    DEVEL_TOOLS=(
        php
        sqlite3
    );
fi

TOOLS=(
    docker-compose
    docker
    curl
    composer
    jq
    ${DEVEL_TOOLS[@]}
)

FAILS_N=0

#
# CHECK
#

for t in ${TOOLS[@]}; do
    printf "${BLUE} Checking '${t}' ...${RESET}"
    [[ ( -f $(which ${t}) ) && ( $(${t} --version) ) ]] \
        && echo "${GREEN} ok${RESET}" && continue \
        || echo "${RED} failed${RESET} (missing or not running)"; FAILS_N=$((FAILS_N+1));
done

printf "\n"
[[ ${FAILS_N} -eq 0 ]] && exit 0

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