@echo off
cd /d "%~dp0"
echo ===================================================
echo   WAYPOINT - PT YOKE FOOD INDUSTRIES INDONESIA
echo ===================================================
echo Monitoring Pengiriman: http://127.0.0.1:8001/monitoring
echo Pelaporan kelompok   : http://127.0.0.1:8001/pelaporan
echo E-PO Distributor     : http://127.0.0.1:8001/distributor
echo.

if exist "C:\laragon\bin\php\php-8.5.13-nts-Win32-vs17-x64\php.exe" (
    "C:\laragon\bin\php\php-8.5.13-nts-Win32-vs17-x64\php.exe" artisan serve --host=127.0.0.1 --port=8001 --no-reload
) else if exist "D:\laragon\bin\php\php-8.5.12-nts-Win32-vs17-x64\php.exe" (
    "D:\laragon\bin\php\php-8.5.12-nts-Win32-vs17-x64\php.exe" artisan serve --host=127.0.0.1 --port=8001 --no-reload
) else (
    php artisan serve --host=127.0.0.1 --port=8001 --no-reload
)

