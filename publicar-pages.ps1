# Sube los cambios y publica la demo en GitHub Pages (carpeta /docs).
$ErrorActionPreference = 'Continue'
$log = Join-Path $PSScriptRoot 'github.log'
function L($t) { Add-Content -Path $log -Value $t -Encoding UTF8; Write-Host "  $t" }
Set-Location $PSScriptRoot
L "=== PUBLICAR PAGES $(Get-Date) ==="
git add -A 2>&1 | Out-Null
git commit -m "Demo funcional en GitHub Pages y README con link para probar la app" 2>&1 | ForEach-Object { Add-Content -Path $log -Value "$_" -Encoding UTF8 }
git -c credential.helper= -c credential.helper=manager push origin main 2>&1 | ForEach-Object { Add-Content -Path $log -Value "$_" -Encoding UTF8 }
if ($LASTEXITCODE -ne 0) { L 'ERROR: no se pudieron subir los archivos.'; exit 1 }
L 'Archivos subidos.'

$tmp = Join-Path $env:TEMP 'enf-cred.txt'
[IO.File]::WriteAllText($tmp, "protocol=https`nhost=github.com`n`n")
$salida = cmd /c "git -c credential.helper= -c credential.helper=manager credential fill < `"$tmp`" 2>nul"
Remove-Item $tmp -ErrorAction SilentlyContinue
$pw = ($salida | Where-Object { $_ -like 'password=*' }) -replace '^password=', ''
if (-not $pw) { L 'ERROR: no se obtuvo autorizacion de GitHub.'; exit 1 }
$h = @{ Authorization = "token $pw"; 'User-Agent' = 'plataforma-enfermeria'; Accept = 'application/vnd.github+json' }
$url = (git remote get-url origin) -replace '\.git$', ''
$p = $url -split '/'; $dueno = $p[-2]; $repo = $p[-1]
$cuerpo = '{"source":{"branch":"main","path":"/docs"}}'
try {
  Invoke-RestMethod -ErrorAction Stop -Method Post -Uri "https://api.github.com/repos/$dueno/$repo/pages" -Headers $h -Body $cuerpo -ContentType 'application/json' | Out-Null
  L 'GitHub Pages activado (rama main, carpeta /docs).'
} catch {
  $code = 0; if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
  if ($code -eq 409) {
    try { Invoke-RestMethod -ErrorAction Stop -Method Put -Uri "https://api.github.com/repos/$dueno/$repo/pages" -Headers $h -Body $cuerpo -ContentType 'application/json' | Out-Null; L 'GitHub Pages ya estaba activo; configuracion confirmada.' }
    catch { L "Aviso al actualizar Pages: $($_.Exception.Message)" }
  } else { L "ERROR al activar GitHub Pages ($code): $($_.Exception.Message)"; exit 1 }
}
$web = "https://$dueno.github.io/$repo/"
try {
  Invoke-RestMethod -ErrorAction Stop -Method Patch -Uri "https://api.github.com/repos/$dueno/$repo" -Headers $h -Body (@{ homepage = $web } | ConvertTo-Json) -ContentType 'application/json' | Out-Null
} catch {}
$web | Set-Content -Path (Join-Path $PSScriptRoot 'link-app.txt') -Encoding UTF8
L "LISTO: $web  (puede tardar 1 o 2 minutos en aparecer la primera vez)"
