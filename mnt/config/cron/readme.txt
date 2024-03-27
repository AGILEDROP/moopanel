# Running laravel scheduler on production
1. SSH to server and add open cron entry file (run "crontab -e" in linux terminal).
2. Add this entry to the file: '* * * * * docker exec map-php php artisan schedule:run > /dev/null 2>&1'
3. Check if crontab is working (try to check status with 'systemctl status cron' command). If the status is
   ‘Active (Running)’ then it will be confirmed that crontab is working well or otherwise if not.

# Documentation
- Laravel documentation - Running The Scheduler link: https://laravel.com/docs/10.x/scheduling#running-the-scheduler
- Setting Up Supervisor and Cron for Laravel in production -> Running the scheduler section - link: https://gblend.medium.com/setting-up-supervisor-and-cron-for-laravel-queue-in-production-163a89603355
- Laravel task scheduling guide -> last section of getting started - link: https://betterstack.com/community/guides/scaling-php/laravel-task-scheduling/