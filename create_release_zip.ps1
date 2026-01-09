# Get the directory where this script is located
$sourceDir = $PSScriptRoot

# Read composer.json to get package name and version
$composerJson = Get-Content (Join-Path $sourceDir "composer.json") | ConvertFrom-Json
$packageName = $composerJson.name -replace '/', '-'
$version = $composerJson.version
$zipFileName = "$packageName-$version.zip"
$zipFile = Join-Path $sourceDir $zipFileName
$scriptName = $MyInvocation.MyCommand.Name

# Delete existing zip if it exists
if (Test-Path $zipFile) {
    Remove-Item $zipFile -Force
}

Write-Host "Creating zip file: $zipFile"
Write-Host "Package: $packageName"
Write-Host "Version: $version"
Write-Host "Excluding: .git, .vs, .github, and this script"

# Directories and files to exclude
$excludeDirs = @('.git', '.vs', '.github')
$excludeFiles = @($scriptName, 'create_release_zip.bat', '*.zip')

# 7-Zip executable path
$7Zip = 'C:\"Program Files"\7-Zip\7zG.exe'

# Create a temporary directory
$tempBase = if ($env:TEMP) { $env:TEMP } elseif ($env:TMPDIR) { $env:TMPDIR } else { "/tmp" }
$tempDir = Join-Path $tempBase ("zip_temp_" + [guid]::NewGuid().ToString())
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

try {
    # Copy items excluding the specified directories and files
    Get-ChildItem -Path $sourceDir -Force | Where-Object {
        $item = $_
        $excludeDirs -notcontains $item.Name -and
        -not ($excludeFiles | Where-Object { $item.Name -like $_ })
    } | ForEach-Object {
        if ($_.PSIsContainer) {
            Copy-Item -Path $_.FullName -Destination (Join-Path $tempDir $_.Name) -Recurse -Force
        } else {
            Copy-Item -Path $_.FullName -Destination $tempDir -Force
        }
    }

    # Create the zip file with all contents at root level
    # 7-Zip is needed because Compress-Archive does not pass the validation script due to Windows path separators used
    $tempFiles = Join-Path $tempDir '*'
    Invoke-Expression "$7Zip a `"$zipFile`" `"$tempFiles`""

    Write-Host ""
    Write-Host "Zip file created at: $zipFile" -ForegroundColor Green
}
catch {
    Write-Host ""
    Write-Host "Error creating zip file: $_" -ForegroundColor Red
}
finally {
    # Prompt to ensure 7-Zip releases file handles before cleanup
    Read-Host "Press Enter to continue"
    
    # Clean up temp directory
    if (Test-Path $tempDir) {
        Remove-Item -Path $tempDir -Recurse -Force
    }
}

Read-Host "Press Enter to exit"
