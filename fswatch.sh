#!/bin/bash
DIR=$1

if [ ! -n "$DIR" ] ;then
    echo "Error: You have not choice Application directory!"
    exit
fi

php easyswoole stop
php easyswoole start d

fswatch -m poll_monitor -rt -l5 -e"\.idea/" -e"_$" $DIR | while read file
do
   echo "Message: ${file} was modify!"
   #php easyswoole reload 只重启task进程
   php easyswoole reload all  重启task + worker进程
done