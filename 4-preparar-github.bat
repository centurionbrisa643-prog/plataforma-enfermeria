@echo off
chcp 65001 >nul
title Preparar proyecto para GitHub
cd /d "%~dp0"
set "LOG=%~dp0git.log"
echo === GIT %date% %time% === > "%LOG%"

where git >> "%LOG%" 2>&1
if errorlevel 1 (
  echo Git no esta instalado. Instalando con winget... >> "%LOG%"
  echo.
  echo   Instalando Git ^(solo la primera vez^). Si Windows pide permiso, toca "Si".
  winget install --id Git.Git -e --silent --accept-source-agreements --accept-package-agreements >> "%LOG%" 2>&1
  set "PATH=%ProgramFiles%\Git\cmd;%PATH%"
)
where git >> "%LOG%" 2>&1
if errorlevel 1 (
  echo NO_GIT >> "%LOG%"
  echo   No se pudo instalar Git. Revisa git.log
  pause
  exit /b 1
)
git --version >> "%LOG%" 2>&1
echo --- identidad global --- >> "%LOG%"
git config --global user.name >> "%LOG%" 2>&1
git config --global user.email >> "%LOG%" 2>&1

if not exist ".git" git init -b main >> "%LOG%" 2>&1
git add -A >> "%LOG%" 2>&1
echo --- estado --- >> "%LOG%"
git status --short >> "%LOG%" 2>&1

git config user.name >nul 2>&1
if errorlevel 1 (
  echo SIN_IDENTIDAD >> "%LOG%"
  goto abrir
)
git rev-parse --verify HEAD >nul 2>&1
if errorlevel 1 (
  git commit -m "Plataforma Academica de Enfermeria - prototipo funcional" >> "%LOG%" 2>&1
)
echo --- commits --- >> "%LOG%"
git log --oneline -3 >> "%LOG%" 2>&1

:abrir
echo ABRIENDO_VSCODE >> "%LOG%"
call code "%~dp0." >> "%LOG%" 2>&1
echo LISTO >> "%LOG%"
