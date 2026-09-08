@echo off
REM Test the config, then hot-reload nginx workers (no dropped connections).
setlocal
set NGINX_DIR=C:\nginx-1.30.4
cd /d "%NGINX_DIR%" || exit /b 1

nginx.exe -t
if errorlevel 1 (
    echo [ERROR] config invalid - reload aborted, old config still live.
    exit /b 1
)

nginx.exe -s reload
echo nginx reloaded.
endlocal
