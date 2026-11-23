@echo off
cd /d "%~dp0"
echo Waypoint: http://127.0.0.1:8001/distributor
echo Nyalakan MySQL melalui Laragon.
php artisan serve --host=127.0.0.1 --port=8001 --no-reload
