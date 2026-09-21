param([int]$Port = 8077)
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
$phpDirectory = Split-Path (Get-Command php).Source
New-Item -ItemType Directory -Path '.runtime' -Force | Out-Null
if (-not (Test-Path -LiteralPath '.runtime/cacert.pem')) {
    Invoke-WebRequest -Uri 'https://curl.se/ca/cacert.pem' -OutFile '.runtime/cacert.pem'
}
php -d "extension_dir=$phpDirectory\ext" -d extension=openssl -d "openssl.cafile=.runtime/cacert.pem" -S "127.0.0.1:$Port" -t public
