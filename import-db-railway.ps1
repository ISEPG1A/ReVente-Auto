# Script PowerShell pour importer le schéma SQL sur Railway
# Usage: .\import-db-railway.ps1

Write-Host "🚂 Import de la base de données sur Railway" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier si Railway CLI est installé
$railwayCommand = Get-Command railway -ErrorAction SilentlyContinue
if (-not $railwayCommand) {
    Write-Host "❌ Railway CLI n'est pas installé." -ForegroundColor Red
    Write-Host ""
    Write-Host "Pour installer Railway CLI:" -ForegroundColor Yellow
    Write-Host "  npm install -g @railway/cli" -ForegroundColor White
    Write-Host ""
    Write-Host "Puis exécutez: railway login" -ForegroundColor Yellow
    exit 1
}

# Vérifier si le projet est lié
if (-not (Test-Path ".railway")) {
    Write-Host "⚠️  Projet Railway non lié." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Exécutez d'abord: railway link" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

Write-Host "📋 Import du schéma SQL..." -ForegroundColor Green
Write-Host ""

# Récupérer les variables d'environnement de Railway
Write-Host "🔑 Récupération des credentials MySQL..." -ForegroundColor Cyan

$env:RAILWAY_SERVICE = "MySQL"
$credentials = railway variables | ConvertFrom-Json

if (-not $credentials) {
    Write-Host "❌ Impossible de récupérer les credentials." -ForegroundColor Red
    exit 1
}

# Import du schéma
$schemaPath = "database\schema.sql"
if (-not (Test-Path $schemaPath)) {
    Write-Host "❌ Fichier schema.sql introuvable: $schemaPath" -ForegroundColor Red
    exit 1
}

# Exécuter l'import via Railway CLI
Write-Host "📤 Upload du schéma..." -ForegroundColor Cyan
$content = Get-Content $schemaPath -Raw

# Utiliser railway run pour exécuter la commande mysql
railway run --service MySQL -- mysql -u `$MYSQLUSER -p`$MYSQLPASSWORD -h `$MYSQLHOST -P `$MYSQLPORT `$MYSQLDATABASE -e $content

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✅ Schéma importé avec succès!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Vous pouvez maintenant:" -ForegroundColor Cyan
    Write-Host "  1. Vérifier les tables: railway run --service MySQL -- mysql -e 'SHOW TABLES;'" -ForegroundColor White
    Write-Host "  2. Déployer votre application" -ForegroundColor White
} else {
    Write-Host ""
    Write-Host "❌ Erreur lors de l'import du schéma." -ForegroundColor Red
    Write-Host ""
    Write-Host "Vérifiez que:" -ForegroundColor Yellow
    Write-Host "  - Le service MySQL est bien démarré sur Railway" -ForegroundColor White
    Write-Host "  - Les credentials sont corrects" -ForegroundColor White
    Write-Host ""
    Write-Host "Alternative: Utilisez un client MySQL (TablePlus, DBeaver, etc.)" -ForegroundColor Cyan
}
