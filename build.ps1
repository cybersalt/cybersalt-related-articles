<#
    Build script for mod_currentcat_children_and_articles (Cybersalt Related Articles)

    Produces a Joomla-installable ZIP with proper directory entries using 7-Zip.
    Output is written to the project root in the format:
        mod_currentcat_children_and_articles_v{VERSION}_{YYYYMMDD}_{HHMM}.zip
    With -Release, also writes the clean-named copy for the GitHub release:
        mod_currentcat_children_and_articles_v{VERSION}.zip

    USAGE:
        powershell -ExecutionPolicy Bypass -File build.ps1 [-Release]

    REQUIRES:
        7-Zip installed at C:\Program Files\7-Zip\7z.exe
        Joomla-Brain beside this repo, or in .joomla-brain (tools/integrity)
#>
param([switch]$Release)

$ErrorActionPreference = 'Stop'

$root     = $PSScriptRoot
$src      = Join-Path $root 'src'
$manifest = Join-Path $src 'mod_currentcat_children_and_articles.xml'
$sevenZip = 'C:\Program Files\7-Zip\7z.exe'
$name     = 'mod_currentcat_children_and_articles'

if (-not (Test-Path $sevenZip)) { throw "7-Zip not found at $sevenZip." }

[xml]$xml = Get-Content $manifest -Raw -Encoding UTF8
# SelectSingleNode, because $xml.extension.version also returns the version="5.0" attribute.
$version  = [string]$xml.extension.SelectSingleNode('version').InnerText
if (-not $version) { throw 'Could not read <version> from manifest.' }

# The changelogs ship at the zip root; refuse to build without them.
foreach ($log in @('CHANGELOG.md', 'CHANGELOG.html')) {
    if (-not (Test-Path (Join-Path $root $log))) { throw "Missing $log at the repo root." }
}

$integrityTool = @(
    (Join-Path $root '.joomla-brain\tools\integrity\New-IntegrityList.ps1'),
    (Join-Path (Split-Path $root -Parent) 'Joomla-Brain\tools\integrity\New-IntegrityList.ps1')
) | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $integrityTool) { throw 'Joomla-Brain tools/integrity not found.' }

& $integrityTool -ExtensionDir $src

$items = @("$name.xml", "$name.php", 'integrity.json', 'src', 'tmpl', 'language')
foreach ($item in $items) {
    if (-not (Test-Path (Join-Path $src $item))) { throw "Missing required item: src\$item" }
}

$timestamp = Get-Date -Format 'yyyyMMdd_HHmm'
$zipPath   = Join-Path $root "${name}_v${version}_${timestamp}.zip"

Push-Location $src
try {
    & $sevenZip a -tzip $zipPath @items | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "7-Zip exited with code $LASTEXITCODE" }
}
finally {
    Pop-Location
}

Push-Location $root
try {
    & $sevenZip a -tzip $zipPath 'CHANGELOG.md' 'CHANGELOG.html' 'LICENSE' | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "7-Zip exited with code $LASTEXITCODE" }
}
finally {
    Pop-Location
}

& $sevenZip l $zipPath | Select-String -Pattern 'D\.\.\.\.' | ForEach-Object { Write-Host "  $_" }

Write-Host "Built: $zipPath"
Write-Host "SHA256: $((Get-FileHash -Algorithm SHA256 -Path $zipPath).Hash.ToLower())"

if ($Release) {
    $clean = Join-Path $root "${name}_v${version}.zip"
    Copy-Item $zipPath $clean -Force
    Write-Host "Release copy: $clean"
    Write-Host "SHA256: $((Get-FileHash -Algorithm SHA256 -Path $clean).Hash.ToLower())"
}
