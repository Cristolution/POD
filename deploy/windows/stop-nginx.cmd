@echo off
REM Graceful nginx shutdown; falls back to taskkill if the signal is ignored.
setlocal
set NGINX_DIR=C:\nginx-1.30.4
cd /d "%NGINX_DIR%" || exit /b 1

nginx.exe -s quit 2>nul
if errorlevel 1 taskkill /F /IM nginx.exe 2>nul

echo nginx stopped.
endlocal
