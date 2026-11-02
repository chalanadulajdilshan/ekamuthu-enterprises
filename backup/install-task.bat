@echo off
REM Run once on the client PC (as Administrator) to schedule the cloud backup every 1 minute.
set PHP=C:\xampp\php\php.exe
set SCRIPT=%~dp0backup-upload.php

schtasks /Create /F /TN "Ekamuthu DB Cloud Backup" /SC MINUTE /MO 1 /RU SYSTEM /RL HIGHEST /TR "\"%PHP%\" \"%SCRIPT%\""
if errorlevel 1 (
    echo Failed to create the task. Right-click this file and choose "Run as administrator".
    pause
    exit /b 1
)

REM Keep running on laptops on battery, catch up missed runs, never run two at once
powershell -NoProfile -Command "$s = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -MultipleInstances IgnoreNew; Set-ScheduledTask -TaskName 'Ekamuthu DB Cloud Backup' -Settings $s | Out-Null"
if errorlevel 1 echo WARNING: could not update battery settings. In Task Scheduler, untick "Start only if on AC power".

echo Task created. Running the first backup now...
"%PHP%" "%SCRIPT%"
pause
