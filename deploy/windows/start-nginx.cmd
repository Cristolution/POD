@echo off
REM Start nginx. Must be launched from the nginx prefix dir, hence the /d cd.
setlocal
set NGINX_DIR=C:\nginx-1.30.4

cd /d "%NGINX_DIR%" || exit /b 1

nginx.exe -t
if errorlevel 1 (
    echo [ERROR] nginx config test failed - not starting.
    exit /b 1
)

tasklist /FI "IMAGENAME eq nginx.exe" | find /I "nginx.exe" >nul
if not errorlevel 1 (
    echo nginx already running - use reload-nginx.cmd instead.
    exit /b 0
)

start "" nginx.exe
echo nginx started -^> http://pod.test
endlocal
