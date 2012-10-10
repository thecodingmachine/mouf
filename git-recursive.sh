#!/bin/bash

IFS="
"

olddir=`pwd`
dir=
        for i in $( ls -1 vendor/mouf/); do
                cd $olddir/vendor/mouf/$i

		echo "Running git $@ on $i"
		git $@
        done

cd $olddir
