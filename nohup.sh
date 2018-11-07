#!/bin/bash
LOG_FILE=$1

if [ ! -n "$LOG_FILE" ] ;then
    echo "Error: You have not choice log file!"
    exit
fi

nohup ./fswatch.sh ./App > $LOG_FILE 2>&1 &