$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path $PSScriptRoot -Parent
$releaseDirectory = Join-Path $projectDirectory 'release'
New-Item -ItemType Directory -Path $releaseDirectory -Force | Out-Null
$archivePath = Join-Path $releaseDirectory ('alinbughius-site-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.zip')
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::Open($archivePath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    $files = @()
    foreach ($directory in @('app', 'public', 'vendor', 'tools')) {
        $files += Get-ChildItem -LiteralPath (Join-Path $projectDirectory $directory) -Recurse -File -Force
    }
    foreach ($relativePath in @('config/example.php', 'storage/.gitkeep', 'README.md', 'LICENSE', '.htaccess')) {
        $files += Get-Item -LiteralPath (Join-Path $projectDirectory $relativePath)
    }
    foreach ($file in $files) {
        $entry = $file.FullName.Substring($projectDirectory.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $file.FullName, $entry, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
} finally { $archive.Dispose() }
Get-Item -LiteralPath $archivePath | Select-Object Name, Length, FullName
