@echo off
REM Removes the hourly cloud backup task (run as Administrator).
schtasks /Delete /F /TN "Ekamuthu DB Cloud Backup"
pause
