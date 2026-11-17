@echo off
chcp 65001 >nul
title Hacer publico el repositorio
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0hacer-publico.ps1"
echo.
pause
