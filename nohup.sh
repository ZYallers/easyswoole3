#!/bin/bash
base_dir=$1
if [ ! -n "$base_dir" ] ;then
    echo "Error: You have not choice log file!"
    exit
fi

cur_date=`date +%Y%m%d`
parent_dir_name=`pwd | awk -F "/" '{print $NF}'`
log_file=$base_dir/$cur_date/$parent_dir_name.fswatch_reload.log

nohup ./fswatch.sh ./App > $log_file 2>&1 &