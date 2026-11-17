@echo off
chcp 65001 >nul
cd /d "%~dp0"
set LOG=%~dp0diagnostico.txt
echo === DIAGNOSTICO %date% %time% === > "%LOG%"
echo --- where php --- >> "%LOG%"
where php >> "%LOG%" 2>&1
php -v >> "%LOG%" 2>&1
echo --- extensiones --- >> "%LOG%"
php -m >> "%LOG%" 2>&1
php --ini >> "%LOG%" 2>&1
echo --- carpetas PHP conocidas --- >> "%LOG%"
for %%D in ("C:\php" "C:\xampp\php" "C:\laragon\bin\php" "C:\wamp64\bin\php" "C:\tools\php") do if exist %%D (echo EXISTE %%~D >> "%LOG%" & dir /b %%D >> "%LOG%" 2>&1)
dir /b /s "%LOCALAPPDATA%\Microsoft\WinGet\Packages\*php*" >> "%LOG%" 2>&1
echo --- PostgreSQL --- >> "%LOG%"
dir /b "C:\Program Files\PostgreSQL" >> "%LOG%" 2>&1
where psql >> "%LOG%" 2>&1
sc query state= all | findstr /i "postgres" >> "%LOG%" 2>&1
netstat -ano | findstr ":5432" >> "%LOG%" 2>&1
echo --- pgAdmin --- >> "%LOG%"
dir /b "C:\Program Files\pgAdmin 4" >> "%LOG%" 2>&1
dir /b "%LOCALAPPDATA%\Programs\pgAdmin 4" >> "%LOG%" 2>&1
echo --- VS Code --- >> "%LOG%"
where code >> "%LOG%" 2>&1
echo --- winget --- >> "%LOG%"
where winget >> "%LOG%" 2>&1
echo LISTO >> "%LOG%"
echo Diagnostico terminado. Ya podes cerrar esta ventana.
timeout /t 5 >nul
