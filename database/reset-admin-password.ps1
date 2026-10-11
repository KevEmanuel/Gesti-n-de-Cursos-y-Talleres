$ErrorActionPreference = 'Stop'

$php = Get-Command php -ErrorAction Stop
$scriptPath = Join-Path $PSScriptRoot 'reset-admin-password.php'
$firstPassword = Read-Host 'Nueva contraseña del administrador (mínimo 12 caracteres)' -AsSecureString
$confirmation = Read-Host 'Confirma la nueva contraseña' -AsSecureString
$firstPointer = [IntPtr]::Zero
$confirmationPointer = [IntPtr]::Zero
$process = $null

try {
    $firstPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($firstPassword)
    $confirmationPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($confirmation)
    $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($firstPointer)
    $confirmationText = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($confirmationPointer)

    if ($password -cne $confirmationText) {
        throw 'Las contraseñas no coinciden. No se realizaron cambios.'
    }

    $startInfo = New-Object System.Diagnostics.ProcessStartInfo
    $startInfo.FileName = $php.Source
    $quotedScriptPath = '"' + $scriptPath.Replace('"', '\"') + '"'
    $startInfo.Arguments = $quotedScriptPath
    $startInfo.UseShellExecute = $false
    $startInfo.RedirectStandardInput = $true
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    $startInfo.CreateNoWindow = $true

    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $startInfo
    [void] $process.Start()
    $process.StandardInput.WriteLine($password)
    $process.StandardInput.Close()
    $standardOutput = $process.StandardOutput.ReadToEnd()
    $standardError = $process.StandardError.ReadToEnd()
    $process.WaitForExit()

    if ($standardOutput) {
        Write-Output $standardOutput.TrimEnd()
    }
    if ($process.ExitCode -ne 0) {
        throw $standardError.Trim()
    }
    if ($standardError) {
        Write-Warning $standardError.Trim()
    }
}
finally {
    if ($firstPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($firstPointer)
    }
    if ($confirmationPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($confirmationPointer)
    }
    $password = $null
    $confirmationText = $null
    $firstPassword = $null
    $confirmation = $null
    if ($process) {
        $process.Dispose()
    }
}
