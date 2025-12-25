  set -e

  FOLDER=~/Projects/flags-api/app/scripts

  echo "Building image..."
  "$FOLDER/build-tag-push.local"

  echo "Deploying to remote..."
  ssh hq@florence-hq.local 'cd ~/Apps/Flags-quiz/k8s && ./redeploy.sh'
