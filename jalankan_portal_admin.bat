@echo off
echo ===================================================
echo   PORTAL ADMIN - PT YOKE FOOD INDUSTRIES INDONESIA
echo ===================================================
echo Membuka Portal Admin di browser...
start http://localhost:8000/portal-admin.html
echo.
echo Server berjalan di http://localhost:8000/
echo Tekan Ctrl+C untuk menghentikan server.
echo.
php -S localhost:8000 -t public
pause

