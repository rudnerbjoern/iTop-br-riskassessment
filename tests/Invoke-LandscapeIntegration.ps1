[CmdletBinding()]
param(
    [Parameter(Mandatory)][string]$DevelopmentRoot,
    [Parameter(Mandatory)][string]$LandscapeRoot
)

$ErrorActionPreference = 'Stop'
$project = Split-Path $PSScriptRoot -Parent
$runner = (Resolve-Path (Join-Path $DevelopmentRoot 'scripts/tests/Invoke-Tests.ps1')).Path
$landscape = (Resolve-Path $LandscapeRoot).Path
[xml]$peer = Get-Content (Join-Path $landscape 'extension.xml') -Raw
if ($peer.SelectSingleNode('/extension/extension_code').InnerText.Trim() -ne 'br-landscape') {
    throw 'Expected a System Landscape extension package.'
}
$temp = (New-Item -ItemType Directory -Force (Join-Path $project '.tmp')).FullName
$fixture = (New-Item -ItemType Directory (Join-Path $temp ('landscape-' + [guid]::NewGuid().ToString('N')))).FullName
try {
    [xml]$bundle = Get-Content (Join-Path $project 'extension.xml') -Raw
    foreach ($entry in $bundle.extension.modules.module) {
        Copy-Item -LiteralPath (Join-Path $project ([string]$entry.id)) -Destination $fixture -Recurse
    }
    Copy-Item -LiteralPath (Join-Path $project '.dev') -Destination $fixture -Recurse
    Copy-Item -LiteralPath (Join-Path $landscape 'br-landscape') -Destination $fixture -Recurse
    # The shared runner selects regular fixture modules. iTop decides whether to add the bridge.
    $module = $bundle.CreateElement('module')
    foreach ($key in @('id', 'version')) {
        $node = $bundle.CreateElement($key)
        $node.InnerText = if ($key -eq 'id') { 'br-landscape' } else { $peer.SelectSingleNode('/extension/version').InnerText.Trim() }
        $null = $module.AppendChild($node)
    }
    $null = $bundle.extension.modules.AppendChild($module)
    $bundle.Save((Join-Path $fixture 'extension.xml'))
    & $runner -Suite Extensions -ExtensionPath $fixture -ExtensionSuite Integration -Profile Vanilla
} finally {
    $resolved = (Resolve-Path -LiteralPath $fixture).Path
    if ([IO.Path]::GetDirectoryName($resolved) -ne $temp -or [IO.Path]::GetFileName($resolved) -notmatch '^landscape-[0-9a-f]{32}$') {
        throw 'Unexpected fixture directory.'
    }
    Remove-Item -LiteralPath $resolved -Recurse -Force
}
