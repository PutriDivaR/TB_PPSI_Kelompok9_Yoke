@echo off
cd /d "%~dp0"
echo Waypoint kelompok: http://127.0.0.1:8001/pelaporan
echo Nyalakan MySQL melalui Laragon.
"D:\laragon\bin\php\php-8.5.12-nts-Win32-vs17-x64\php.exe" artisan serve --host=127.0.0.1 --port=8001 --no-reload
