<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:mitjarobic/social-sync.git');
set('branch', 'main');

add('shared_files', ['.env']);
add('shared_dirs', ['storage']);
add('writable_dirs', ['storage', 'bootstrap/cache']);

// Hosts

host('production')
    ->setHostname('148.251.45.235')
    ->set('remote_user', 'stillcoding')
    ->set('port', 37111)
    ->set('http_user', 'nobody')
    ->set('deploy_path', '~/social-sync-production');

host('development')
    ->setHostname('148.251.45.235')
    ->set('remote_user', 'stillcoding')
    ->set('port', 37111)
    ->set('http_user', 'nobody')
    ->set('deploy_path', '~/social-sync-development');

// Hooks

after('deploy:failed', 'deploy:unlock');

task('build', function () {
    run('cd {{release_path}} && npm install && npm run build');
});

after('deploy:vendors', 'build');

task('deploy:laravel:optimize', function () {
    run('{{bin/php}} {{release_path}}/artisan optimize:clear');
    run('{{bin/php}} {{release_path}}/artisan config:cache');
    run('{{bin/php}} {{release_path}}/artisan route:cache');
    run('{{bin/php}} {{release_path}}/artisan view:cache');
});
after('deploy:symlink', 'deploy:laravel:optimize');

task('horizon:restart', function () {
    run('bash /home/stillcoding/social-sync-development/deploy.sh');
});

after('deploy:symlink', 'horizon:restart');



