# Get the directory where this script is located
$sourceDir = $PSScriptRoot
$folderName = "Cardknox"
$zipFile = Join-Path $sourceDir "$folderName.zip"
$scriptName = $MyInvocation.MyCommand.Name

# Delete existing zip if it exists
if (Test-Path $zipFile) {
    Remove-Item $zipFile -Force
}

Write-Host "Creating zip file: $zipFile"
Write-Host "Excluding: .git, .vs, .github, and this script"

# Directories and files to exclude
$excludeDirs = @('.git', '.vs', '.github')
$excludeFiles = @($scriptName, 'create_release_zip.bat', "$folderName.zip")

# Create a temporary directory
$tempDir = Join-Path $env:TEMP ("zip_temp_" + [guid]::NewGuid().ToString())
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

try {
    # Copy items excluding the specified directories and files
    Get-ChildItem -Path $sourceDir -Force | Where-Object {
        $excludeDirs -notcontains $_.Name -and
        $excludeFiles -notcontains $_.Name
    } | ForEach-Object {
        if ($_.PSIsContainer) {
            Copy-Item -Path $_.FullName -Destination (Join-Path $tempDir $_.Name) -Recurse -Force
        } else {
            Copy-Item -Path $_.FullName -Destination $tempDir -Force
        }
    }

    # Create the zip file
    Compress-Archive -Path (Join-Path $tempDir '*') -DestinationPath $zipFile -Force

    Write-Host ""
    Write-Host "Done! Zip file created at: $zipFile" -ForegroundColor Green
}
catch {
    Write-Host ""
    Write-Host "Error creating zip file: $_" -ForegroundColor Red
}
finally {
    # Clean up temp directory
    if (Test-Path $tempDir) {
        Remove-Item -Path $tempDir -Recurse -Force
    }
}

Read-Host "Press Enter to exit"
