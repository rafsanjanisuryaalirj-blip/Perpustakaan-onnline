@echo off
setlocal EnableDelayedExpansion
title Ngrok Launcher - Perpustakaan Online Multi-Tenant
color 0B
chcp 65001 >nul

echo ====================================================================
echo       PERPUSTAKAAN ONLINE MULTI-TENANT - NGROK LAUNCHER
echo ====================================================================
echo.

:: 0. Hentikan proses ngrok lama jika masih ada yang berjalan di background
taskkill /F /IM ngrok.exe >nul 2>nul

:: 1. Cek apakah Apache XAMPP aktif di Port 80
netstat -ano | findstr /R /C:":80 .*LISTENING" >nul
if %errorlevel% neq 0 (
    echo [PERINGATAN] Apache XAMPP belum terdeteksi berjalan di port 80!
    echo Harap buka XAMPP Control Panel dan klik 'Start' pada Apache terlebih dahulu.
    echo.
)

:: 2. Cek apakah ngrok terinstall
set "PATH=%~dp0;%PATH%"
where ngrok >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] File 'ngrok.exe' tidak ditemukan.
    echo Silakan pastikan ngrok.exe ada di folder ini atau terinstall di PATH.
    pause
    exit /b
)

:: 3. Cek authtoken ngrok
set "CONFIG_PATH=%LOCALAPPDATA%\ngrok\ngrok.yml"
set "NEED_TOKEN=0"
if exist "%CONFIG_PATH%" (
    findstr /i "authtoken" "%CONFIG_PATH%" >nul 2>nul
    if !errorlevel! neq 0 set "NEED_TOKEN=1"
) else (
    set "NEED_TOKEN=1"
)

if "!NEED_TOKEN!"=="1" (
    echo [INFO] Authtoken Ngrok belum terpasang di komputer Anda.
    echo Anda bisa melihat authtoken di: https://dashboard.ngrok.com/get-started/your-authtoken
    echo.
    set /p "USER_TOKEN=Masukkan Authtoken Ngrok Anda (lalu tekan Enter): "
    if defined USER_TOKEN (
        ngrok config add-authtoken !USER_TOKEN!
        echo [OK] Authtoken berhasil dikonfigurasi!
        echo.
    )
)

echo.
echo ====================================================================
echo                   STATUS MULTI-TENANT SIAP
echo ====================================================================
echo Domain Publik: https://earphone-either-swagger.ngrok-free.dev
echo.
echo Tautan Akses Multi-Tenant:
echo 1. Portal Utama (Landing Page / Cari Sekolah):
echo    https://earphone-either-swagger.ngrok-free.dev/perpustakaan-online/
echo.
echo 2. Portal Sekolah Australia Independents School:
echo    https://earphone-either-swagger.ngrok-free.dev/perpustakaan-online/school.php?s=australia-independents-school
echo.
echo 3. Portal Sekolah SMK Bina Mandiri Multimedia:
echo    https://earphone-either-swagger.ngrok-free.dev/perpustakaan-online/school.php?s=smk-bina-mandiri-multimedia
echo.
echo 4. Mobile Scanner Peminjaman (Australia Independents School):
echo    https://earphone-either-swagger.ngrok-free.dev/perpustakaan-online/public/scan-mobile.php?key=202cbc376a9ccfea1279ef3c20f0b0b2
echo.
echo 5. Mobile Scanner Peminjaman (SMK Bina Mandiri Multimedia):
echo    https://earphone-either-swagger.ngrok-free.dev/perpustakaan-online/public/scan-mobile.php?key=28a30e5d4fbb54bf7d31f2bd805b030a
echo ====================================================================
echo.
echo Memulai tunnel ngrok ke Apache (Port 80)...
echo (Tekan Ctrl+C jika ingin mematikan tunnel ngrok)
echo.

ngrok http --domain=earphone-either-swagger.ngrok-free.dev 80
