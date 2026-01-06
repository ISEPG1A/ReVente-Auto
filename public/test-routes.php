<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Routes - ReVente-Auto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #2563eb;
            margin-bottom: 20px;
        }
        .link {
            display: block;
            padding: 15px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 10px;
            text-align: center;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        .link:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        .info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔗 Test des Routes Admin FAQ</h1>
        
        <div class="info">
            <strong>⚠️ Instructions :</strong><br>
            Cliquez sur les liens ci-dessous pour tester l'accès aux différentes pages.
        </div>

        <h2>📍 Routes à tester :</h2>
        
        <a href="/PROJET/ReVente-Auto/" class="link">
            🏠 Page d'accueil
        </a>
        
        <a href="/PROJET/ReVente-Auto/connexion" class="link">
            🔐 Page de connexion
        </a>
        
        <a href="/PROJET/ReVente-Auto/database/creer_admin.php" class="link">
            ⚙️ Créer le compte admin
        </a>
        
        <a href="/PROJET/ReVente-Auto/admin/faq" class="link">
            🔧 Admin FAQ (nécessite d'être connecté en admin)
        </a>
        
        <a href="/PROJET/ReVente-Auto/api/faq" class="link">
            📡 API FAQ (JSON)
        </a>

        <div class="info" style="margin-top: 30px;">
            <strong>🎯 Si vous obtenez une erreur 404 :</strong><br>
            1. Vérifiez que vous utilisez bien <code>/PROJET/ReVente-Auto/</code> dans l'URL<br>
            2. Vérifiez que Apache est démarré<br>
            3. Vérifiez que le fichier <code>.htaccess</code> existe dans le dossier public
        </div>
    </div>

    <div class="card">
        <h2>📋 Informations système</h2>
        <p><strong>Chemin actuel :</strong> <code><?php echo $_SERVER['REQUEST_URI']; ?></code></p>
        <p><strong>Script :</strong> <code><?php echo $_SERVER['SCRIPT_NAME']; ?></code></p>
        <p><strong>Document Root :</strong> <code><?php echo $_SERVER['DOCUMENT_ROOT']; ?></code></p>
    </div>
</body>
</html>
