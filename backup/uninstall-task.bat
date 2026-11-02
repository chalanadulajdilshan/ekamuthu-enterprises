@echo off
REM Removes the cloud backup task (run as Administrator).
schtasks /Query /TN "Ekamuthu DB Cloud Backup" >nul 2>&1
if errorlevel 1 (
    echo Task "Ekamuthu DB Cloud Backup" is not installed on this PC. Nothing to remove.
    pause
    exit /b 0
)
schtasks /Delete /F /TN "Ekamuthu DB Cloud Backup"
if errorlevel 1 echo Failed to remove the task. Right-click this file and choose "Run as administrator".
pause
