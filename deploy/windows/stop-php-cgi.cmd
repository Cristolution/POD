@echo off
REM Stop every php-cgi worker in the pool.
taskkill /F /IM php-cgi.exe 2>nul
if errorlevel 1 (
    echo No php-cgi processes were running.
) else (
    echo php-cgi pool stopped.
)
