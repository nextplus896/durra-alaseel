# Current project root (folder containing this script)
$ProjectRoot = $PSScriptRoot

$InputFile = Join-Path $ProjectRoot "input.txt"
$OutputFile = Join-Path $ProjectRoot "context.txt"

if (!(Test-Path $InputFile)) {
    Write-Host "input.txt not found."
    exit
}

# Clear output
"" | Set-Content $OutputFile -Encoding UTF8

$Files = Get-Content $InputFile

foreach ($File in $Files) {

    $File = $File.Trim()

    if ([string]::IsNullOrWhiteSpace($File)) {
        continue
    }

    $FullPath = Join-Path $ProjectRoot $File

    Add-Content $OutputFile "============================================================"
    Add-Content $OutputFile "FILE: $File"
    Add-Content $OutputFile "============================================================"
    Add-Content $OutputFile ""

    if (Test-Path $FullPath) {
        Get-Content $FullPath -Raw | Add-Content $OutputFile
    }
    else {
        Add-Content $OutputFile "<< FILE NOT FOUND >>"
    }

    Add-Content $OutputFile ""
    Add-Content $OutputFile ""
}

# Copy output to clipboard
Get-Content $OutputFile -Raw | Set-Clipboard

Write-Host ""
Write-Host "Done!"
Write-Host "Output: context.txt"
Write-Host "Copied to clipboard."