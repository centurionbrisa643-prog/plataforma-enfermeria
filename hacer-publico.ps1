# Cambia el repositorio de GitHub a PUBLICO.
$ErrorActionPreference = 'Continue'
$log = Join-Path $PSScriptRoot 'github.log'
function L($t) { Add-Content -Path $log -Value $t -Encoding UTF8; Write-Host "  $t" }
Set-Location $PSScriptRoot
L "=== HACER PUBLICO $(Get-Date) ==="
$tmp = Join-Path $env:TEMP 'enf-cred.txt'
[IO.File]::WriteAllText($tmp, "protocol=https`nhost=github.com`n`n")
$salida = cmd /c "git -c credential.helper= -c credential.helper=manager credential fill < `"$tmp`" 2>nul"
Remove-Item $tmp -ErrorAction SilentlyContinue
$pw = ($salida | Where-Object { $_ -like 'password=*' }) -replace '^password=', ''
if (-not $pw) { L 'ERROR: no se obtuvo autorizacion de GitHub.'; exit 1 }
$h = @{ Authorization = "token $pw"; 'User-Agent' = 'plataforma-enfermeria'; Accept = 'application/vnd.github+json' }
$url = (git remote get-url origin) -replace '\.git$', ''
$partes = $url -split '/'
$dueno = $partes[-2]; $repo = $partes[-1]
try {
  $r = Invoke-RestMethod -ErrorAction Stop -Method Patch -Uri "https://api.github.com/repos/$dueno/$repo" -Headers $h -Body (@{ private = $false } | ConvertTo-Json) -ContentType 'application/json'
  L "Visibilidad: $(if ($r.private) { 'privado' } else { 'PUBLICO' })"
  L "LISTO: $($r.html_url)"
} catch { L "ERROR al cambiar la visibilidad: $($_.Exception.Message)"; exit 1 }
