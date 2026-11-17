@echo off
chcp 65001 >nul
title Plataforma Enfermeria - servidor (Wi-Fi)
cd /d "%~dp0"
set "LOG=%~dp0iniciar.log"
set "TMPR=%TEMP%\enf_resultado.txt"
echo === INICIO %date% %time% === > "%LOG%"

rem ---------- PHP de XAMPP ----------
set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" (
  echo No se encontro PHP en C:\xampp\php >> "%LOG%"
  echo No se encontro PHP en C:\xampp\php
  pause
  exit /b 1
)
"%PHP%" -v >> "%LOG%" 2>&1
set "EXT="
for %%e in (pdo_pgsql pgsql mbstring openssl) do (
  "%PHP%" -m | findstr /i /b /c:"%%e" >nul || call :agregar %%e
)
echo Extensiones agregadas al iniciar: %EXT% >> "%LOG%"
"%PHP%" %EXT% -m | findstr /i "pgsql mbstring openssl" >> "%LOG%" 2>&1

rem ---------- ¿ya hay un servidor abierto? ----------
netstat -ano | findstr /r /c:":8000 .*LISTENING" >nul
if not errorlevel 1 (
  echo.
  echo   Ya hay otra ventana del servidor abierta en el puerto 8000.
  echo   Cerrala primero y volve a abrir este archivo.
  echo.
  pause
  exit /b 1
)

rem ---------- PostgreSQL 17 ----------
set "PATH=C:\Program Files\PostgreSQL\17\bin;%PATH%"
set PGCLIENTENCODING=UTF8
set PGHOST=localhost
set PGPORT=5432
set PGUSER=postgres
echo.
echo  ==========================================================
echo    Plataforma Academica - Carrera de Enfermeria (UniNorte)
echo  ==========================================================
echo.

:pedir
powershell -NoProfile -Command "$p = Read-Host '  Escribi la contrasena de PostgreSQL (usuario postgres) y apreta Enter' -AsSecureString; [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($p))" > "%TMPR%"
set "PGPASSWORD="
set /p PGPASSWORD=<"%TMPR%"
del "%TMPR%" >nul 2>&1
psql -d postgres -tAc "select version()" >> "%LOG%" 2>&1
if errorlevel 1 goto malclave
echo Conexion a PostgreSQL OK >> "%LOG%"
goto base

:malclave
echo Contrasena incorrecta o PostgreSQL apagado >> "%LOG%"
echo.
echo   La contrasena no funciono. Proba de nuevo.
echo.
goto pedir

:base
psql -d postgres -tAc "select count(*) from pg_database where datname = 'enfermeria_db'" > "%TMPR%" 2>> "%LOG%"
set /p EXISTE=<"%TMPR%"
if "%EXISTE%"=="0" (
  echo Creando base enfermeria_db >> "%LOG%"
  createdb -E UTF8 -T template0 enfermeria_db >> "%LOG%" 2>&1
)
psql -d enfermeria_db -tAc "select count(*) from information_schema.tables where table_name = 'usuarios'" > "%TMPR%" 2>> "%LOG%"
set /p TABLAS=<"%TMPR%"
del "%TMPR%" >nul 2>&1
if "%TABLAS%"=="1" goto servidor
echo Cargando sql\enfermeria_db.sql >> "%LOG%"
psql -d enfermeria_db -q -v ON_ERROR_STOP=1 -f "%~dp0sql\enfermeria_db.sql" >> "%LOG%" 2>&1
if errorlevel 1 goto errorsql

:servidor
psql -d enfermeria_db -tAc "select 'usuarios en la base: ' || count(*) from usuarios" >> "%LOG%" 2>&1
set "DB_PASS=%PGPASSWORD%"
set "PGPASSWORD="

rem ---------- IP de esta PC en la red ----------
powershell -NoProfile -Command "(Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -ne $null -and $_.NetAdapter.Status -eq 'Up' } | Select-Object -First 1).IPv4Address.IPAddress" > "%TMPR%"
set "IP="
set /p IP=<"%TMPR%"
del "%TMPR%" >nul 2>&1
if "%IP%"=="" set IP=localhost
set "APP_URL=http://%IP%:8000"
echo Compartiendo en la red: %APP_URL% >> "%LOG%"
echo %APP_URL%> "%~dp0link-wifi.txt"
echo.
echo  ==========================================================
echo    En el celular (conectado al MISMO Wi-Fi) abri:
echo.
echo        %APP_URL%
echo.
echo    En esta PC:  http://localhost:8000
echo  ==========================================================
echo.
echo   Si Windows pregunta por el Firewall, marca "Redes privadas" y toca "Permitir".
echo   NO cierres esta ventana mientras la usen.
echo.
start "" http://localhost:8000
"%PHP%" %EXT% -S 0.0.0.0:8000 router.php >> "%~dp0servidor.log" 2>&1
goto fin

:errorsql
echo ERROR al cargar el script SQL >> "%LOG%"
echo   Hubo un error al cargar la base. Revisa iniciar.log
pause

:fin
exit /b

:agregar
set "EXT=%EXT% -d extension=%1"
exit /b
