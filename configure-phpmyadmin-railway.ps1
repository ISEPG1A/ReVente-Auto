# Script PowerShell pour configurer phpMyAdmin XAMPP avec Railway MySQL
# Usage: .\configure-phpmyadmin-railway.ps1

Write-Host "`n╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   🐬 Configuration phpMyAdmin pour Railway MySQL      ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

# Vérifier si XAMPP est installé
$xamppPath = "C:\xampp"
$phpMyAdminConfig = "$xamppPath\phpMyAdmin\config.inc.php"

if (-not (Test-Path $xamppPath)) {
    Write-Host "❌ XAMPP n'est pas installé dans C:\xampp" -ForegroundColor Red
    Write-Host "   Veuillez installer XAMPP ou ajuster le chemin." -ForegroundColor Yellow
    exit 1
}

if (-not (Test-Path $phpMyAdminConfig)) {
    Write-Host "❌ Fichier config.inc.php introuvable" -ForegroundColor Red
    Write-Host "   Chemin attendu: $phpMyAdminConfig" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ XAMPP trouvé !`n" -ForegroundColor Green

# Demander les credentials Railway
Write-Host "📋 Entrez vos credentials Railway MySQL" -ForegroundColor Cyan
Write-Host "   (Trouvez-les dans: Railway > MySQL > Connect)`n" -ForegroundColor Gray

$railwayHost = Read-Host "MYSQLHOST (ex: containers-us-west-123.railway.app)"
$railwayPort = Read-Host "MYSQLPORT (ex: 1234)"
$railwayDatabase = Read-Host "MYSQLDATABASE (ex: railway)"
$railwayUser = Read-Host "MYSQLUSER (généralement: root)"

Write-Host "`n🔐 Mot de passe Railway" -ForegroundColor Cyan
$railwayPasswordSecure = Read-Host "MYSQLPASSWORD" -AsSecureString
$railwayPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($railwayPasswordSecure)
)

# Vérifier que les champs ne sont pas vides
if ([string]::IsNullOrWhiteSpace($railwayHost) -or 
    [string]::IsNullOrWhiteSpace($railwayPort) -or
    [string]::IsNullOrWhiteSpace($railwayDatabase) -or
    [string]::IsNullOrWhiteSpace($railwayUser) -or
    [string]::IsNullOrWhiteSpace($railwayPassword)) {
    Write-Host "`n❌ Tous les champs sont obligatoires !" -ForegroundColor Red
    exit 1
}

Write-Host "`n📝 Configuration en cours..." -ForegroundColor Cyan

# Backup de la config actuelle
$backupPath = "$phpMyAdminConfig.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Copy-Item $phpMyAdminConfig $backupPath
Write-Host "💾 Backup créé: $backupPath" -ForegroundColor Gray

# Lire le contenu actuel
$configContent = Get-Content $phpMyAdminConfig -Raw

# Vérifier si la configuration Railway existe déjà
if ($configContent -match "Railway MySQL") {
    Write-Host "`n⚠️  Une configuration Railway existe déjà !" -ForegroundColor Yellow
    $overwrite = Read-Host "Voulez-vous la remplacer ? (o/n)"
    
    if ($overwrite -ne "o") {
        Write-Host "❌ Annulé par l'utilisateur." -ForegroundColor Red
        exit 0
    }
    
    # Supprimer l'ancienne configuration Railway
    $configContent = $configContent -replace "(?s)/\* Serveur Railway \*/.*?(?=\$i\+\+;|\?>|$)", ""
}

# Préparer la nouvelle configuration
$railwayConfig = @"

/* Serveur Railway */
`$i++;
`$cfg['Servers'][`$i]['verbose'] = 'Railway MySQL';
`$cfg['Servers'][`$i]['host'] = '$railwayHost';
`$cfg['Servers'][`$i]['port'] = '$railwayPort';
`$cfg['Servers'][`$i]['socket'] = '';
`$cfg['Servers'][`$i]['connect_type'] = 'tcp';
`$cfg['Servers'][`$i]['extension'] = 'mysqli';
`$cfg['Servers'][`$i]['auth_type'] = 'cookie';
`$cfg['Servers'][`$i]['AllowNoPassword'] = false;
`$cfg['Servers'][`$i]['user'] = '$railwayUser';
`$cfg['Servers'][`$i]['database'] = '$railwayDatabase';
"@

# Insérer avant le ?>
if ($configContent -match "\?>") {
    $configContent = $configContent -replace "\?>", "$railwayConfig`n?>"
} else {
    $configContent += $railwayConfig
}

# Écrire le nouveau contenu
Set-Content -Path $phpMyAdminConfig -Value $configContent -Encoding UTF8

Write-Host "✅ Configuration phpMyAdmin mise à jour !`n" -ForegroundColor Green

# Redémarrer Apache XAMPP
Write-Host "🔄 Redémarrage d'Apache..." -ForegroundColor Cyan

$apacheExe = "$xamppPath\apache\bin\httpd.exe"

if (Test-Path $apacheExe) {
    # Arrêter Apache
    $apacheProcess = Get-Process | Where-Object { $_.ProcessName -eq "httpd" }
    if ($apacheProcess) {
        Stop-Process -Name "httpd" -Force -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 2
        Write-Host "   Apache arrêté" -ForegroundColor Gray
    }
    
    # Démarrer Apache
    Start-Process -FilePath $apacheExe -WindowStyle Hidden
    Start-Sleep -Seconds 3
    Write-Host "   Apache redémarré ✅`n" -ForegroundColor Green
} else {
    Write-Host "⚠️  Veuillez redémarrer Apache manuellement via XAMPP Control Panel`n" -ForegroundColor Yellow
}

# Créer un fichier avec les credentials pour référence
$credentialsFile = "railway-credentials.txt"
$credentialsContent = @"
# Railway MySQL Credentials
# Généré le $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')

Host:     $railwayHost
Port:     $railwayPort
Database: $railwayDatabase
User:     $railwayUser
Password: [MASQUÉ - voir Railway]

# Pour se connecter à phpMyAdmin:
# 1. Ouvrez http://localhost/phpmyadmin
# 2. Choisissez 'Railway MySQL' dans le menu serveur
# 3. Utilisez les credentials ci-dessus
"@

Set-Content -Path $credentialsFile -Value $credentialsContent
Write-Host "💾 Credentials sauvegardés dans: $credentialsFile`n" -ForegroundColor Gray

# Résumé
Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║                  ✅ CONFIGURATION TERMINÉE !            ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════╝`n" -ForegroundColor Green

Write-Host "📍 Prochaines étapes:`n" -ForegroundColor Cyan
Write-Host "   1. Ouvrez votre navigateur" -ForegroundColor White
Write-Host "   2. Allez sur: " -NoNewline -ForegroundColor White
Write-Host "http://localhost/phpmyadmin" -ForegroundColor Yellow
Write-Host "   3. Dans le menu serveur, choisissez: " -NoNewline -ForegroundColor White
Write-Host "'Railway MySQL'" -ForegroundColor Green
Write-Host "   4. Connectez-vous avec:" -ForegroundColor White
Write-Host "      - Utilisateur: $railwayUser" -ForegroundColor Gray
Write-Host "      - Mot de passe: [votre mot de passe Railway]" -ForegroundColor Gray
Write-Host "   5. Importez: database\schema.sql`n" -ForegroundColor White

# Proposer d'ouvrir phpMyAdmin
$openBrowser = Read-Host "Voulez-vous ouvrir phpMyAdmin maintenant ? (o/n)"
if ($openBrowser -eq "o") {
    Start-Process "http://localhost/phpmyadmin"
    Write-Host "`n🌐 phpMyAdmin ouvert dans votre navigateur !`n" -ForegroundColor Green
}

Write-Host "💡 Tip: Les credentials sont dans: $credentialsFile`n" -ForegroundColor Cyan
Write-Host "📚 Documentation complète: docs\PHPMYADMIN_RAILWAY.md`n" -ForegroundColor Cyan
