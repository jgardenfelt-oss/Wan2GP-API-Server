@echo off
title WanGP API Server
cd /d "%~dp0"

:: ---------------------------------------------------------------------------
:: Find Conda
:: ---------------------------------------------------------------------------
set "CONDA_EXE="

for %%d in (
    "%USERPROFILE%\miniconda3"
    "%USERPROFILE%\Miniconda3"
    "%USERPROFILE%\anaconda3"
    "%USERPROFILE%\Anaconda3"
    "%LOCALAPPDATA%\miniconda3"
    "%LOCALAPPDATA%\Miniconda3"
    "C:\ProgramData\miniconda3"
    "C:\ProgramData\Miniconda3"
    "C:\ProgramData\anaconda3"
    "C:\ProgramData\Anaconda3"
) do (
    if exist "%%~d\Scripts\conda.exe" (
        set "CONDA_EXE=%%~d\Scripts\conda.exe"
        goto :found_conda
    )
)

where conda.exe >nul 2>&1
if %errorlevel% equ 0 (
    for /f "delims=" %%i in ('where conda.exe') do (
        if not defined CONDA_EXE set "CONDA_EXE=%%i"
    )
)

:found_conda
if not defined CONDA_EXE (
    echo [ERROR] Conda not found.
    pause
    exit /b 1
)

echo Found Conda: %CONDA_EXE%
echo Starting WanGP API Server on port 8001...

"%CONDA_EXE%" run -n wan2gp --no-capture-output python api-server.py --host 127.0.0.1 --port 8001
pause
