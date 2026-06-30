# Run Laravel Pint to fix code style (run from repo root).
# Usage: .\run-pint.ps1              → auto: PHP in PATH, else Docker
#        .\run-pint.ps1 -Docker      → force Docker (php:8.4-cli o immagine locale se presente)
# Requires: PHP in PATH, oppure Docker; vendor/ presente (composer install).
Set-Location $PSScriptRoot

$useDocker = $args -contains "-Docker"
$phpExe = $null
$dockerImageCustom = "iartnetbackoffice-composer:php84-intlzip"
$dockerImageFallback = "php:8.4-cli"

if (!$useDocker) {
    if (Get-Command php -ErrorAction SilentlyContinue) {
        $phpExe = "php"
    }
    if (!$phpExe) {
        $paths = @(
            "$env:LOCALAPPDATA\Programs\PHP\php.exe",
            "C:\php\php.exe",
            "C:\laravel\herd\php\php.exe",
            "C:\xampp\php\php.exe",
            "C:\wamp64\bin\php\php*\php.exe",
            "${env:ProgramFiles}\PHP\php.exe",
            "${env:ProgramFiles(x86)}\PHP\php.exe"
        )
        foreach ($p in $paths) {
            if ($p -match '\*') {
                $resolved = Get-Item $p -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName
                if ($resolved) { $phpExe = $resolved; break }
            } elseif (Test-Path $p) { $phpExe = $p; break }
        }
    }
}

if (!$phpExe -and !$useDocker) {
    $useDocker = $true
    Write-Host "PHP non in PATH: uso Docker."
}

if (!(Test-Path "vendor\bin\pint")) {
    Write-Error "Manca vendor/bin/pint. In locale: composer install. In Docker: avvia il container con il volume montato e composer install."
    exit 1
}

if ($useDocker) {
    if (!(Get-Command docker -ErrorAction SilentlyContinue)) {
        Write-Error "Docker non trovato. Avvia Docker Desktop o aggiungi PHP al PATH."
        exit 1
    }
    $phpExe = $null
    $dir = (Get-Location).Path
    # Usa immagine custom solo se presente in locale; altrimenti php:8.4-cli (scaricabile da Docker Hub)
    $customExists = docker image inspect $dockerImageCustom 2>$null; if ($LASTEXITCODE -ne 0) { $customExists = $null }
    if ($customExists) {
        $dockerImage = $dockerImageCustom
        Write-Host "Using Docker (locale): $dockerImage"
    } else {
        $dockerImage = $dockerImageFallback
        Write-Host "Using Docker: $dockerImage (immagine custom non presente, uso immagine pubblica)"
    }
}

Write-Host "Running: pint (fix style)..."
if ($phpExe) {
    & $phpExe vendor/bin/pint
} else {
    docker run --rm -v "${dir}:/app" -w /app $dockerImage php vendor/bin/pint
}
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "Running: pint --test (verify)..."
if ($phpExe) {
    & $phpExe vendor/bin/pint --test
} else {
    docker run --rm -v "${dir}:/app" -w /app $dockerImage php vendor/bin/pint --test
}
exit $LASTEXITCODE
