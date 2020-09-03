#!/bin/bash

PATTERN='todo[[:space:]@:].*'
DESCRIPTION="Checking project for missing ToDos"

if [[ -n "$1" ]]; then
    IFS='/'
    BRANCH=($1)
    unset IFS;

    TICKETNR="${BRANCH[0]}"
    PATTERN+="${TICKETNR}\\s.*"
    DESCRIPTION+=" regarding the current ticket number: ${TICKETNR}"
fi

DESCRIPTION+="\n"
printf "${DESCRIPTION}"

RESULT=$(grep -inRw "${PATTERN}" --exclude-dir={\*node_modules,dist,vendor,public,.git\*} --exclude=\*.md | awk -F":" '{print "\033[1;37m"$1"\n\033[0;31m"$2":\t"$3"\033[0m\n"}')

if [[ -n "$RESULT" ]]; then
    printf "${RESULT}"
    printf "\n"
    exit 1
else
    echo "Everything is A-OK"
    printf "\n"
fi