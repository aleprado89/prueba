# Recrea la base de prueba: esquema (adaptado al nombre de BD) + seed mínimo.
# Requisitos: cliente mysql en PATH (ej. WAMP: C:\wamp64\bin\mysql\mysql8.x.x\bin\mysql.exe).

$ErrorActionPreference = 'Stop'
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$Schema = Join-Path $ProjectRoot 'shema_sistemasescolares.sql'
$Seed = Join-Path $ProjectRoot 'tests\fixtures\seed_minimal.sql'

$dbHost = if ($env:SESYSTEM_DB_HOST) { $env:SESYSTEM_DB_HOST } else { '127.0.0.1' }
$dbUser = if ($env:SESYSTEM_DB_USER) { $env:SESYSTEM_DB_USER } else { 'root' }
$dbPass = if ($null -ne $env:SESYSTEM_DB_PASSWORD) { $env:SESYSTEM_DB_PASSWORD } else { '' }
$dbName = if ($env:SESYSTEM_DB_NAME) { $env:SESYSTEM_DB_NAME } else { 'sesystem_test' }

$mysqlArgs = @("-h$dbHost", "-u$dbUser")
if ($dbPass -ne '') { $mysqlArgs += "-p$dbPass" }

Write-Host "Creando base $dbName ..."
& mysql @mysqlArgs -e "DROP DATABASE IF EXISTS ``$dbName``; CREATE DATABASE ``$dbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if ($LASTEXITCODE -ne 0) { throw "mysql fallo al crear la base" }

Write-Host "Importando esquema (sesystem_prueba -> $dbName)..."
$schemaSql = (Get-Content $Schema -Raw) -replace 'sesystem_prueba', $dbName
$schemaSql | & mysql @mysqlArgs
if ($LASTEXITCODE -ne 0) { throw "mysql fallo al importar esquema" }

Write-Host "Aplicando seed..."
Get-Content $Seed -Raw | & mysql @mysqlArgs $dbName
if ($LASTEXITCODE -ne 0) { throw "mysql fallo al aplicar seed" }

Write-Host "Listo: $dbName"
