#!/bin/bash

IFS="
"

olddir=`pwd`
dir=
        for i in $( ls -1 vendor/mouf/); do
                cd $olddir/vendor/mouf/$i

		echo "Commiting/Pushing $i"
                git add *
		git commit -m $@
		git push
        done

cd $olddir
