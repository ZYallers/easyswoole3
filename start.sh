#!/bin/bash
DIR=$1

if [ ! -n "$DIR" ] ;then
    echo "you have not choice Application directory !"
    exit
fi

php easyswoole stop
php easyswoole start d

fswatch -rt -l2 -e"\.idea/" -e"_$" $DIR | while read file
do
   echo "${file} was modify!"
   php easyswoole reload
done