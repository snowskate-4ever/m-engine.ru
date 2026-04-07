# Запуск Gradle без глобального JAVA_HOME: ищет JBR из Android Studio / Android SDK.
# Пример: .\run-gradle.ps1 assembleStagingDebug
#         .\run-gradle.ps1 assembleProdDebug

$ErrorActionPreference = "Stop"
$here = $PSScriptRoot

$localProps = Join-Path $here "local.properties"
if (-not (Test-Path $localProps)) {
    $sdkDefault = Join-Path $env:LOCALAPPDATA "Android\Sdk"
    if (Test-Path $sdkDefault) {
        $sdkUnix = ($sdkDefault -replace '\\', '/')
        "sdk.dir=$sdkUnix" | Set-Content -Path $localProps -Encoding UTF8
        Write-Host "Создан local.properties → sdk.dir=$sdkUnix" -ForegroundColor DarkGray
    }
}

if (-not $env:JAVA_HOME -or -not (Test-Path "$env:JAVA_HOME\bin\java.exe")) {
    $candidates = @(
        "$env:LOCALAPPDATA\Android\Sdk\jbr",
        "$env:ProgramFiles\Android\Android Studio\jbr",
        "${env:ProgramFiles(x86)}\Android\Android Studio\jbr"
    ) | Where-Object { Test-Path "$_\bin\java.exe" }

    $jbr = $candidates | Select-Object -First 1
    if (-not $jbr) {
        Write-Host "Не найден JDK. Установите Android Studio или задайте JAVA_HOME вручную." -ForegroundColor Red
        exit 1
    }
    $env:JAVA_HOME = $jbr
    $env:Path = "$jbr\bin;$env:Path"
    Write-Host "JAVA_HOME=$jbr" -ForegroundColor DarkGray
}

& "$here\gradlew.bat" @args
exit $LASTEXITCODE
