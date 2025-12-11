docker build -f .docker/php-fpm/Dockerfile-base -t swiftcode/flags:php-fpm-1.2 -t swiftcode/flags:php-fpm-latest .
docker push swiftcode/flags:php-fpm-1.2
docker push swiftcode/flags:php-fpm-latest
