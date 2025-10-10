<<<<<<<< Update Guide >>>>>>>>>>>
Immediate Older Version: 1.1.0
Current Version: 1.2.0

Feature Update:
1. Admin can now add new Users and Vendors based on project registration data.
2. Added the ability to manage dynamic sections from the admin panel.
3. Updated the Extensions section to check whether credentials are filled in.
4. Added an All Notifications page to the admin panel.
5. Admins can now create support tickets on behalf of Users and Vendors.
6. Error logs are now viewable in the admin panel, with the option to clear them.
7. Added support for dynamic admin URL access.
8. Updated the roles and permissions management system.
9. Added Authorize.net payment gateway integration.


Please Use This Commands On Your Terminal To Run Full System
1. Update Composer To Update All PHP/Laravel Packages 
    composer update

2. Seed Database With Necessary Data 
    php artisan migrate:fresh --seed

3. Create Token For API Authentication By Run The Command Below 
    php artisan passport:install
