@echo off
chcp 65001 >nul
title Actualizar GitHub
cd /d "%~dp0"
set "LOG=%~dp0github.log"
echo === ACTUALIZAR %date% %time% === >> "%LOG%"
git add -A >> "%LOG%" 2>&1
git commit -m "Version funcional completa: README actualizado, capturas y scripts de Windows" >> "%LOG%" 2>&1
git -c credential.helper= -c credential.helper=manager push origin main >> "%LOG%" 2>&1
if errorlevel 1 (
  echo ERROR_PUSH >> "%LOG%"
  echo   No se pudo subir. Revisa github.log
) else (
  echo PUSH_OK >> "%LOG%"
  echo   Listo: GitHub actualizado.
)
echo.
pause
