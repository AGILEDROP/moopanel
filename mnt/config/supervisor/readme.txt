# Setup queue worker with supervisor:
1. Install Supervisor with 'sudo apt-get install supervisor' command.
2. Copy laravel-worker.conf file in newly created supervisor configuration folder '/etc/supervisor/conf.d'.
3. Instruct supervisor to read new changes with 'sudo supervisorctl reread' command.
4. Activate the new configuration with 'sudo supervisorctl update' command.
5. Start queue command: 'sudo supervisorctl start "laravel-worker:*"'
6. You can check if configured correctly with 'sudo supervisorctl status' command.

# Deployment script changes:
- After existing command "php artisan migrate" you should run command 'php artisan queue:restart'
  or 'sudo supervisorctl restart "laravel-worker:*"' (both command should work - last documentation link).

# Documentation for queue-worker/supervisor.
- Supervisor website: http://supervisord.org/index.html
- Laravel supervisor configuration website: https://laravel.com/docs/10.x/queues#supervisor-configuration
- Laravel Supervisor Setup with Example link: https://codeanddeploy.com/blog/laravel/laravel-supervisor-setup-with-example
- Setting up laravel queue workers using supervisor link: https://mhmdomer.com/setting-up-laravel-queue-workers-using-supervisor
- Laravel DevOps - Queues & Workers link: https://martinjoo.dev/laravel-queues-and-workers-in-production
- Setting Up Supervisor and Cron for Laravel in production -> Running up supervisor section - link: https://gblend.medium.com/setting-up-supervisor-and-cron-for-laravel-queue-in-production-163a89603355

# Documentation for deployment script changes:
- Laravel Queues & Deployments link: https://divinglaravel.com/laravel-queues-and-deployments
- How to restart supervisor for a Laravel deployment? : https://stackoverflow.com/a/63091468/8387055