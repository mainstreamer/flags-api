# Flags quiz api

### development environment:
dc up -d
(uses docker-compose.yml + docker-compose.override.yml for dev files sync)

### deploy (from dev env)
To create a deployment you need to tag your images to be deployed:

docker tag flags-api-php swiftcode/flags:php-latest
docker tag flags-api-nginx swiftcode/flags:nginx-latest
docker tag flags-api-db swiftcode/flags:db-latest

push images to dockerhub repo swiftcode/flags
docker push swiftcode/flags --all-tags

to generate certificates cert.sh 
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
