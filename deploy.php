<?php

namespace Deployer;

require 'recipe/symfony.php';

// Configuration
set('repository', 'git@base.github.com:mainstreamer/flags-api.git');
set('git_tty', false); // [Optional] Allocate tty for git on first deployment
add('shared_files', ['.env']);
add('shared_dirs', ['public/uploads', 'config/jwt']);
add('writable_dirs', ['public/uploads']);
set('allow_anonymous_stats', false);
set('http_user', 'www-data');


// Hosts
host('production')
    ->setHostname('api.izeebot.top')
    ->setRemoteUser('root')
//    ->setIdentityFile('')
    ->set('username', 'root')
//    ->set('branch', 'php8')
    ->set('branch', 'fixes')
//    ->set('stage','production')
    ->set('deploy_path', '/var/www/flags-api')
;

// Tasks
desc('Restart PHP-FPM service');
task('php-fpm:restart', function () {
    // The user must have rights for restart service
    // /etc/sudoers: username ALL=NOPASSWD:/bin/systemctl restart php-fpm.service
    run('sudo service php8.0-fpm restart');
});
after('deploy:symlink', 'php-fpm:restart');
// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
// Migrate database before symlink new release.
//before('deploy:symlink', 'database:migrate');
