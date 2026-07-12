@echo off

set "APP=%~dp0Wan2GP WebUI Launcher.exe"

powershell -Command ^
"$s=(New-Object -ComObject WScript.Shell).CreateShortcut([Environment]::GetFolderPath('Desktop')+'\Wan2GP WebUI Launcher.lnk'); ^
$s.TargetPath='%APP%'; ^
$s.WorkingDirectory='%~dp0'; ^
$s.IconLocation='%APP%'; ^
$s.Save()"

echo Desktop shortcut created.
set "APP1=%~dp0Wan2GP API Launcher.exe"

powershell -Command ^
"$s=(New-Object -ComObject WScript.Shell).CreateShortcut([Environment]::GetFolderPath('Desktop')+'\Wan2GP API Launcher.lnk'); ^
$s.TargetPath='%APP1%'; ^
$s.WorkingDirectory='%~dp0'; ^
$s.IconLocation='%APP1%'; ^
$s.Save()"

echo Desktop shortcut created.
pause