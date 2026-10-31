@echo off
title S-PEACE - Web-Based Student Complaint & Concern Management System
echo ====================================================================
echo   S-PEACE: Web-Based Student Complaint and Concern Management System
echo   Polytechnic College of Botolan (PCB)
echo ====================================================================
echo.

set PHP_BIN=C:\xampp\php\php.exe
if not exist "%PHP_BIN%" (
    set PHP_BIN=php
)

:: Get Local IP Address for Mobile Testing
for /f "tokens=4" %%a in ('route print ^| findstr 0.0.0.0.*0.0.0.0') do (
    set LOCAL_IP=%%a
    goto :found_ip
)
:found_ip

echo [LOCAL ACCESS - THIS PC]
echo   Unified Portal Gateway: http://localhost:8000
echo   (Both Students and Admins log in here; the system automatically detects your role)
echo.
if defined LOCAL_IP (
    echo [MOBILE PHONE / FRIENDS TESTING - SAME WI-FI]
    echo   Tell your friends to open this link on their phones:
    echo   http://%LOCAL_IP%:8000
    echo.
)
echo Server is running. Press Ctrl+C to stop.
echo ====================================================================
echo.

start http://localhost:8000
"%PHP_BIN%" -S 0.0.0.0:8000
pause
