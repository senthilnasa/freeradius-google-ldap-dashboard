@echo off
REM FreeRADIUS Admin Password Reset Script for Windows
REM This script resets the admin password to default values

echo ===================================
echo FreeRADIUS Admin Password Reset
echo ===================================
echo.

REM Load environment variables from .env file
if exist .env (
    for /f "usebackq tokens=1,2 delims==" %%a in (.env) do (
        set %%a=%%b
    )
) else (
    echo Warning: .env file not found. Using default values.
    set DB_HOST=mysql
    set DB_NAME=radius
    set DB_USER=radius
    set DB_PASSWORD=radiuspass
    set ADMIN_USERNAME=admin
    set ADMIN_PASSWORD=admin123
)

echo Database Host: %DB_HOST%
echo Database Name: %DB_NAME%
echo Admin Username: %ADMIN_USERNAME%
echo New Password: %ADMIN_PASSWORD%
echo.

REM Confirm reset
set /p confirm="Are you sure you want to reset the admin password? (y/N): "
if /i not "%confirm%"=="y" (
    echo Password reset cancelled.
    exit /b 0
)

echo.
echo Resetting password...

REM Generate password hash using PHP
for /f "delims=" %%i in ('php -r "echo password_hash('%ADMIN_PASSWORD%', PASSWORD_BCRYPT, ['cost' => 12]);"') do set HASHED_PASSWORD=%%i

REM Reset password in database
docker exec -i %MYSQL_CONTAINER% mysql -u %DB_USER% -p%DB_PASSWORD% %DB_NAME% -e "UPDATE admin_users SET password_hash = '%HASHED_PASSWORD%', first_login = TRUE, password_changed_at = NOW() WHERE username = '%ADMIN_USERNAME%';"

if %errorlevel% equ 0 (
    echo ✅ Password reset successful!
    echo.
    echo New login credentials:
    echo Username: %ADMIN_USERNAME%
    echo Password: %ADMIN_PASSWORD%
    echo.
    echo ⚠️  You will be required to change this password on first login.
) else (
    echo ❌ Password reset failed!
    echo Make sure the MySQL container is running and accessible.
    exit /b 1
)

pause