@echo off
chcp 65001 >nul
title Plataforma Enfermeria - servidor (Internet)
cd /d "%~dp0"
set "CARPETA=%~dp0"
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

rem ---------- cloudflared (tunel publico) ----------
call :buscarcf
if not "%CF%"=="" goto tunel
echo.
echo   Instalando cloudflared (solo la primera vez). Si Windows pide permiso, toca "Si".
echo Instalando cloudflared con winget >> "%LOG%"
winget install --id Cloudflare.cloudflared -e --silent --accept-source-agreements --accept-package-agreements >> "%LOG%" 2>&1
call :buscarcf
if not "%CF%"=="" goto tunel
echo No se pudo instalar cloudflared >> "%LOG%"
echo   No se pudo instalar cloudflared. Revisa iniciar.log
pause
goto fin

:tunel
echo cloudflared: %CF% >> "%LOG%"
taskkill /im cloudflared.exe /f >nul 2>&1
if exist "%~dp0tunel.log" del "%~dp0tunel.log"
powershell -NoProfile -Command "Start-Process -FilePath $env:CF -ArgumentList 'tunnel','--url','http://127.0.0.1:8000','--no-autoupdate' -RedirectStandardError ($env:CARPETA + 'tunel.log') -RedirectStandardOutput ($env:CARPETA + 'tunel-salida.log') -WindowStyle Hidden"
echo.
echo   Creando el link publico, espera unos segundos...
set "URL="
set /a INTENTOS=0
:esperar
timeout /t 2 /nobreak >nul
set /a INTENTOS+=1
powershell -NoProfile -Command "$m = Select-String -Path ($env:CARPETA + 'tunel.log') -Pattern 'https://[a-z0-9-]+\.trycloudflare\.com' -ErrorAction SilentlyContinue | Select-Object -First 1; if ($m) { $m.Matches[0].Value }" > "%TMPR%"
set /p URL=<"%TMPR%"
del "%TMPR%" >nul 2>&1
if not "%URL%"=="" goto conurl
if %INTENTOS% lss 30 goto esperar
echo No se obtuvo el link del tunel >> "%LOG%"
echo   No se pudo crear el link. Revisa tu conexion a internet y tunel.log
pause
goto fin

:conurl
set "APP_URL=%URL%"
echo Link publico: %URL% >> "%LOG%"
echo %URL%> "%~dp0link-para-compartir.txt"
echo %URL%| clip
echo.
echo  ==============================================================
echo    LINK PARA COMPARTIR (ya esta copiado, pegalo con Ctrl+V):
echo.
echo        %URL%
echo.
echo    Funciona en cualquier celular o PC, con o sin tu Wi-Fi,
echo    mientras esta ventana siga abierta.
echo  ==============================================================
echo.
echo   Cualquiera con el link puede entrar: no lo publiques abiertamente.
echo   Cada vez que abras este archivo se genera un link nuevo.
echo.
start "" %URL%
"%PHP%" %EXT% -S 127.0.0.1:8000 router.php >> "%~dp0servidor.log" 2>&1
taskkill /im cloudflared.exe /f >nul 2>&1
goto fin

:buscarcf
set "CF="
for %%p in ("%LOCALAPPDATA%\Microsoft\WinGet\Links\cloudflared.exe" "%ProgramFiles(x86)%\cloudflared\cloudflared.exe" "%ProgramFiles%\cloudflared\cloudflared.exe") do if exist %%p set "CF=%%~p"
if "%CF%"=="" for /f "delims=" %%w in ('where cloudflared 2^>nul') do if "%CF%"=="" set "CF=%%w"
if "%CF%"=="" for /f "delims=" %%w in ('dir /b /s "%LOCALAPPDATA%\Microsoft\WinGet\Packages\cloudflared.exe" 2^>nul') do if "%CF%"=="" set "CF=%%w"
exit /b

:errorsql
echo ERROR al cargar el script SQL >> "%LOG%"
echo   Hubo un error al cargar la base. Revisa iniciar.log
pause

:fin
exit /b

:agregar
set "EXT=%EXT% -d extension=%1"
exit /b
