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

# 7-Zip executable path - configurable via environment variable or auto-detected
$SevenZip = if ($env:SEVENZIP_PATH) {
    $env:SEVENZIP_PATH
} elseif ($IsWindows -or $env:OS -like '*Windows*') {
    # Try common Windows installation paths
    $possiblePaths = @(
        'C:\Program Files\7-Zip\7zG.exe',
        'C:\Program Files (x86)\7-Zip\7zG.exe',
        "${env:ProgramFiles}\7-Zip\7zG.exe",
        "${env:ProgramFiles(x86)}\7-Zip\7zG.exe"
    )
    $foundPath = $possiblePaths | Where-Object { Test-Path $_ } | Select-Object -First 1
    if ($foundPath) {
        $foundPath
    } else {
        throw "7-Zip not found. Please install 7-Zip or set SEVENZIP_PATH environment variable."
    }
} else {
    # For non-Windows systems, try '7z' command
    $sevenZCommand = Get-Command -Name '7z' -ErrorAction SilentlyContinue
    if ($sevenZCommand) {
        $sevenZCommand.Path
    } else {
        throw "7-Zip not found. Please install 7-Zip or set SEVENZIP_PATH environment variable."
    }
}

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

    # Normalize line endings to LF for text files
    Write-Host "Normalizing line endings to LF..."
    $textExtensions = @('.php', '.js', '.json', '.xml', '.css', '.html', '.txt', '.md', '.yml', '.yaml', '.ini', '.conf', '.sh', '.bat', '.ps1', '.sql', '.phtml', '.less', '.scss', '.xsd', '.wsdl', '.xslt')
    $fileCount = 0
    Get-ChildItem -Path $tempDir -Recurse -File | Where-Object {
        $ext = [System.IO.Path]::GetExtension($_.FullName).ToLower()
        $textExtensions -contains $ext
    } | ForEach-Object {
        try {
            $content = [System.IO.File]::ReadAllText($_.FullName)
            $normalized = $content -replace "`r`n", "`n" -replace "`r", "`n"
            if ($content -ne $normalized) {
                [System.IO.File]::WriteAllText($_.FullName, $normalized, [System.Text.UTF8Encoding]::new($false))
                $fileCount++
            }
        }
        catch {
            # Skip binary files or files that can't be read as text
        }
    }
    Write-Host "Normalized line endings in $fileCount file(s)" -ForegroundColor Cyan

    # Create the zip file with all contents at root level
    # 7-Zip is needed because Compress-Archive does not pass the validation script due to Windows path separators used
    $tempFiles = Join-Path $tempDir '*'
    & $SevenZip a "$zipFile" "$tempFiles"

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
