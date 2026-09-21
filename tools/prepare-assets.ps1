$ErrorActionPreference = 'Stop'
$projectPath = Split-Path $PSScriptRoot -Parent
$sourcePath = Join-Path $projectPath 'site-old\alinbughius.ro\alinbughius.ro'
$targetPath = Join-Path $projectPath 'public\assets\images'
New-Item -ItemType Directory -Path $targetPath -Force | Out-Null
$assets = @{
    'hero.jpg' = 'alinbughiusro.jpg'
    'alin.jpg' = 'assets\img\alin-bughius.jpeg'
    'terapeutic.jpg' = 'assets\img\portfolio\Masaj-terapeutic.jpg'
    'deep-tissue.jpg' = 'assets\img\portfolio\deep-tissue.jpg'
    'relaxare.jpg' = 'assets\img\portfolio\Masaj-de-relaxare2.jpg'
    'lomi-lomi.jpg' = 'assets\img\portfolio\lomi-lomi2.jpg'
    'anticelulitic.jpg' = 'assets\img\portfolio\Masaj-Anticelulitic.jpg'
    'reflexoterapie.jpg' = 'assets\img\portfolio\reflexoterapie3.jpg'
    'suedez.jpg' = 'assets\img\portfolio\Masaj-suedez.jpg'
    'drenaj.jpg' = 'assets\img\portfolio\drenaj-limfatic3.jpg'
    'diploma-terapeutic.jpg' = 'assets\img\gallery\Diploma-Masaj-Terapeutic.jpg'
    'diploma-deep-tissue.jpg' = 'assets\img\gallery\Diploma-Masaj-Deep-Tissue.jpg'
    'diploma-lomi-lomi.jpg' = 'assets\img\gallery\diploma-masaj-lomi-lomi.jpg'
    'instagram-1.jpg' = 'assets\img\gallery\222.jpeg'
    'instagram-2.jpg' = 'assets\img\gallery\444.jpeg'
    'instagram-3.jpg' = 'assets\img\gallery\666.jpeg'
}
foreach ($asset in $assets.GetEnumerator()) {
    Copy-Item -LiteralPath (Join-Path $sourcePath $asset.Value) -Destination (Join-Path $targetPath $asset.Key)
}
Write-Output "$($assets.Count) supplied assets copied."
