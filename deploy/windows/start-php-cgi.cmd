@echo off
REM ---------------------------------------------------------------------------
REM Start the php-cgi FastCGI pool that nginx (conf/sites-enabled/pod.conf)
REM proxies to. Windows has no PHP-FPM; each process serves one request at a
REM time, so we run four on ports 9000-9003.
REM ---------------------------------------------------------------------------
setlocal

set PHP_DIR=C:\tools\php85
set PHP_CGI=%PHP_DIR%\php-cgi.exe
set PHP_INI=%PHP_DIR%\php.ini

if not exist "%PHP_CGI%" (
    echo [ERROR] php-cgi.exe not found at %PHP_CGI%
    exit /b 1
)

REM 0 = never recycle the worker after N requests (default 500 kills it mid-dev)
set PHP_FCGI_MAX_REQUESTS=0

for %%P in (9000 9001 9002 9003) do (
    echo Starting php-cgi on 127.0.0.1:%%P
    start "php-cgi %%P" /min "%PHP_CGI%" -b 127.0.0.1:%%P -c "%PHP_INI%"
)

echo.
echo php-cgi pool up on 9000-9003.
echo Now run start-nginx.cmd (or reload-nginx.cmd if nginx is already running).
endlocal
