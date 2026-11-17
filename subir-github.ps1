# Crea el repositorio privado en la cuenta de GitHub autorizada y sube el proyecto.
$ErrorActionPreference = 'Stop'
$repo = 'plataforma-enfermeria'
$log  = Join-Path $PSScriptRoot 'github.log'
function L($t) { Add-Content -Path $log -Value $t -Encoding UTF8; Write-Host "  $t" }
Set-Location $PSScriptRoot
"=== GITHUB $(Get-Date) ===" | Set-Content -Path $log -Encoding UTF8

L 'Revisando Git...'
$helper = (git config --get credential.helper) 2>$null
Add-Content -Path $log -Value "credential.helper: $helper" -Encoding UTF8
$gcm = (git credential-manager --version) 2>&1
Add-Content -Path $log -Value "credential-manager: $gcm" -Encoding UTF8
$cfg = @('-c', 'credential.helper=', '-c', 'credential.helper=manager')

$tmp = Join-Path $env:TEMP 'enf-cred.txt'
[IO.File]::WriteAllText($tmp, "protocol=https`nhost=github.com`n`n")
$salida = cmd /c "git -c credential.helper= -c credential.helper=manager credential fill < `"$tmp`" 2>nul"
$pw = ($salida | Where-Object { $_ -like 'password=*' }) -replace '^password=', ''
if (-not $pw) {
  L 'Abriendo el inicio de sesion de GitHub en el navegador: toca "Authorize"...'
  git credential-manager github login --browser 2>&1 | ForEach-Object { Add-Content -Path $log -Value "$_" -Encoding UTF8 }
  $salida = cmd /c "git -c credential.helper= -c credential.helper=manager credential fill < `"$tmp`" 2>nul"
  $pw = ($salida | Where-Object { $_ -like 'password=*' }) -replace '^password=', ''
}
Remove-Item $tmp -ErrorAction SilentlyContinue
if (-not $pw) { L 'ERROR: no se obtuvo autorizacion de GitHub.'; exit 1 }
L 'Autorizacion de GitHub: OK'

$h = @{ Authorization = "token $pw"; 'User-Agent' = 'plataforma-enfermeria'; Accept = 'application/vnd.github+json' }
$usuario = (Invoke-RestMethod -Uri 'https://api.github.com/user' -Headers $h).login
L "Cuenta de GitHub: $usuario"

$cuerpo = @{ name = $repo; private = $true; description = 'Plataforma Academica de la Carrera de Enfermeria (UniNorte) - Grupo 04 - PHP + PostgreSQL' } | ConvertTo-Json
try {
  $r = Invoke-RestMethod -Method Post -Uri 'https://api.github.com/user/repos' -Headers $h -Body $cuerpo -ContentType 'application/json'
  L "Repositorio creado (privado): $($r.html_url)"
} catch {
  if ($_.Exception.Response -and [int]$_.Exception.Response.StatusCode -eq 422) { L 'El repositorio ya existia; se usa el mismo.' }
  else { L "ERROR al crear el repositorio: $($_.Exception.Message)"; exit 1 }
}

$url = "https://github.com/$usuario/$repo.git"
$remotos = git remote
if ($remotos -contains 'origin') { git remote set-url origin $url } else { git remote add origin $url }
git branch -M main
$ErrorActionPreference = 'Continue'
L 'Subiendo archivos...'
git @cfg push -u origin main 2>&1 | ForEach-Object { Add-Content -Path $log -Value "$_" -Encoding UTF8 }
if ($LASTEXITCODE -ne 0) { L 'ERROR: no se pudieron subir los archivos (ver github.log).'; exit 1 }
"https://github.com/$usuario/$repo" | Set-Content -Path (Join-Path $PSScriptRoot 'link-github.txt') -Encoding UTF8
L "LISTO: https://github.com/$usuario/$repo"
