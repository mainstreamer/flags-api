#!/usr/bin/env sh

vendor/bin/dep deploy $1

#printf '\e[0;32m \r%s\e[0m\n' 'Building...'
#yarn build
#printf '\e[0;32m \r%s\e[0m\n' 'Zipping...'
#zip -r build.zip build
#printf '\e[0;32m \r%s\e[0m\n' 'Uploading build...'
#scp build.zip root@api.izeebot.top:/var/www
#printf '\e[0;32m \r%s\e[0m\n' 'Unzipping...'
#ssh root@api.izeebot.top  'cd /var/www && unzip -o build.zip && rm -rf flags-app && mv build flags-app'
#
#

#
#if [ ! -f /usr/local/bin/now ] && [ ! -f /bin/now ];
#
#
#
#
#then
#    case "$(uname -s)" in
#        Darwin) cp now /usr/local/bin;;
#        Linux) cp now /bin;;
#    esac
#    echo "Now installed successfully"
#else
#    echo "Now is already installed"
#    printf '\e[0;31m%s\e[0m \n\r' 'Do you want to (R)einstall or (U)ninstall now? (A)bort [R/U/A]?'
#    read -n 1 x
#    if [ "$x" == "u" ] || [ "$x" == "U" ];
#    then
#        case "$(uname -s)" in
#            Darwin) rm /usr/local/bin/now;;
#            Linux) rm /bin/now;;
#        esac
#		printf '\e[0;32m \r%s\e[0m\n' 'Uninstalled successfully'
#	elif [ "$x" == "r" ] || [ "$x" == "R" ] ;
#	then
#	    case "$(uname -s)" in
#            Darwin) cp now /usr/local/bin;;
#            Linux) cp now /bin;;
#        esac
#        printf '\e[0;32m \r%s\e[0m\n' 'Now reinstalled successfully'
#    elif [ "$x" == "a" ] || [ "$x" == "A" ];
#    then
#        printf '\rno changes\n'
#    else
#        printf '\r🤷️\n'
#    fi
#fi
