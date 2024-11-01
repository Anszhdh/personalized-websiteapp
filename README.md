md


 Framework and Tools


# Prerequisites

1. [XAMPP](httpswww.apachefriends.orgindex.html) - Make sure to install MySQL server as well during the installation.
2. [Composer](httpsgetcomposer.orgdownload)
3. [NodeJs](httpsnodejs.orgen)
4. [Git](httpsgit-scm.comdownloads) - Watch Youtube in case you are unfamiliar with the git

# Installation

1. Install PHP dependencies for backend

bash
composer install


2. Install NPM dependencies for frontend

bash
npm install


3. Open XAMPP and start Apache and MySQL servers.
4. Copy `.env.example` to `.env`(create new file) and update the database configuration (mysql).
5. Run migration

bash
php artisan migrate


6. Generate application key

bash
php artisan keygenerate


7. Seed the database

bash
php artisan dbseed


8. To link storage with file system

bash
php artisan storagelink


9. Run the application using 2 terminals

bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
