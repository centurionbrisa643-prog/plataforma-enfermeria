@echo off
chcp 65001 >nul
title Publicar app en GitHub Pages
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0publicar-pages.ps1"
echo.
pause
