@echo off
title Wan2GP API Server
cd /d "%~dp0"

:: ---------------------------------------------------------------------------
:: Configuration (override with environment variables)
:: ---------------------------------------------------------------------------
if not defined API_HOST set "API_HOST=Your IP_ADDRESS"
if not defined API_PORT set "API_PORT=8001"
if not defined CONDA_ENV set "CONDA_ENV=wan2gp"

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
    echo Install miniconda: https://docs.conda.io/en/latest/miniconda.html
    pause
    exit /b 1
)

:: ---------------------------------------------------------------------------
:: Check conda environment exists
:: ---------------------------------------------------------------------------
"%CONDA_EXE%" env list | findstr /b /c:"%CONDA_ENV% " >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Conda environment '%CONDA_ENV%' not found.
    echo Create it: conda create -n %CONDA_ENV% python=3.11
    pause
    exit /b 1
)

:: ---------------------------------------------------------------------------
:: Display info
:: ---------------------------------------------------------------------------
echo ========================================
echo   Wan2GP API Server
echo ========================================
echo Conda:       %CONDA_EXE%
echo Environment: %CONDA_ENV%
echo Host:        %API_HOST%
echo Port:        %API_PORT%

:: ---------------------------------------------------------------------------
:: Build command
:: ---------------------------------------------------------------------------
set "CMD="%CONDA_EXE%" run -n %CONDA_ENV% --no-capture-output python api_server.py --host %API_HOST% --port %API_PORT%"

if defined API_USERNAME if defined API_PASSWORD (
    set "CMD=%CMD% --username %API_USERNAME% --password %API_PASSWORD%"
    echo Auth:        using credentials from environment
) else if exist ".api_auth.json" (
    echo Auth:        using saved credentials from .api_auth.json
) else (
    echo Auth:        will auto-generate on first run
)

echo ========================================
echo Starting...
echo.

%CMD%
pause
