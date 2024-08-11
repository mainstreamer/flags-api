# Flags quiz api

### development environment:
original .env of simply github secret env is required as pre-requisite 

dc up -d
(uses docker-compose.yml + docker-compose.override.yml for dev files sync)


### deploy (from dev env)
To create a deployment you need to tag your images to be deployed:

docker tag flags-api-php swiftcode/flags:php-latest
docker tag flags-api-nginx swiftcode/flags:nginx-latest
docker tag flags-api-db swiftcode/flags:db-latest

docker push swiftcode/flags:db-latest
docker push swiftcode/flags:nginx-latest

push images to dockerhub repo swiftcode/flags
docker push swiftcode/flags --all-tags

to generate certificates (for docker daemon) cert.sh 
required files:
-r--r--r--   1 artem  staff      1952 Oct 29 13:55 server-cert.pem
-r--------   1 artem  staff      3272 Oct 29 13:55 server-key.pem
-r--r--r--   1 artem  staff      1952 Oct 29 13:55 ca.pem
-r--r--r--   1 artem  staff      1944 Oct 29 13:55 cert.pem
-r--------   1 artem  staff      3272 Oct 29 13:55 key.pem

to create context (locally):
docker context create icu --docker "host=tcp://138.68.184.69:2376,ca=ca.pem,cert=cert.pem,key=key.pem"

switch docker context to icu
cdc icu

docker compose -f docker-compose-prod.yml pull
docker compose -f docker-compose-prod.yml up -d --remove-orphans
docker image prune

~/.zshrc
function change_docker_context {
if [ -z "$1" ]; then
docker context use desktop-linux
else
docker context use "$1"
fi
}

alias 'cdc'='change_docker_context'


################################
DOCKER CONTAINERS 

PHP - There are 3 stages:
1. All environments use base image (with all heavy things that need to be compiled) Docker-base 
2. Local dev environment mounts image to sync local files
3. For prod build base image is taken gthub project pulled and composer install run

Note! - docker-compose-prod.yml is only used to create containers, but never to build images!

####################
Env files
.env should only contain list of required vars (Problem is that docker build by default uses .env)
Local dev .env uses env.local
Prod uses .env.prod
Test uses .env.test

#####################
Secretes (overriden by env vars)

APP_RUNTIME_ENV=prod php bin/console secrets:set DATABASE_URL
APP_RUNTIME_ENV=prod php bin/console secrets:list --reveal
Decryption key needs to be added to CI as SYMFONY_DECRYPTION_SECRET in base64

#########
To add secret 
secret - set



##############
LOCAL PROD REPRODUCTION

docker compose -f docker-compose-prod.yml --env-file .env.prod pull
docker compose -f docker-compose-prod.yml --env-file .env.prod.local up -d
