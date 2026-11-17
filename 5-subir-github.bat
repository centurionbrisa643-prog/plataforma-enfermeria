@echo off
chcp 65001 >nul
title Subir proyecto a GitHub
cd /d "%~dp0"
echo.
echo   Subiendo el proyecto a GitHub como repositorio PRIVADO...
echo.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0subir-github.ps1"
echo.
pause
