@echo off
REM Run once on the client PC (as Administrator) to schedule the hourly cloud backup.
set PHP=C:\xampp\php\php.exe
set SCRIPT=%~dp0backup-upload.php

schtasks /Create /F /TN "Ekamuthu DB Cloud Backup" /SC HOURLY /MO 1 /RU SYSTEM /RL HIGHEST /TR "\"%PHP%\" \"%SCRIPT%\""
if errorlevel 1 (
    echo Failed to create the task. Right-click this file and choose "Run as administrator".
    pause
    exit /b 1
)

echo Task created. Running the first backup now...
"%PHP%" "%SCRIPT%"
pause
