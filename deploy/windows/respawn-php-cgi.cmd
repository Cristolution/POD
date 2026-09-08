@echo off
REM Detached respawn of the php-cgi pool. Intended to be launched from any
REM context (cmd, bash, Claude's Bash tool) without inheriting a parent
REM console that would kill the children on exit.
setlocal
set PHP_CGI=C:\tools\php85\php-cgi.exe
set PHP_INI=C:\tools\php85\php.ini
if not exist "%PHP_CGI%" (
    echo [ERROR] php-cgi.exe not found
    exit /b 1
)
set PHP_FCGI_MAX_REQUESTS=0
for %%P in (9000 9001 9002 9003) do (
    if not defined PHP_CGI_DETACHED (
        "%PHP_CGI%" -b 127.0.0.1:%%P -c "%PHP_INI%"
    )
)
endlocal
