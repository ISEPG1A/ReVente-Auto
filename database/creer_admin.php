<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * SCRIPT DE CRÉATION ADMINISTRATEUR
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce script crée un compte administrateur dans la base de données.
 * 
 * ⚠️ IMPORTANT : 
 * - Exécuter ce script UNE SEULE FOIS
 * - Changer le mot de passe après la première connexion
 * - Supprimer ce fichier après utilisation pour des raisons de sécurité
 * 
 * Usage : http://localhost/PROJET/ReVente-Auto/database/creer_admin.php
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Chargement de la configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/Commun/BaseDeDonnees.php';
require_once __DIR__ . '/../app/Commun/ServiceChiffrement.php';

// Configuration de l'admin
$emailAdmin = 'antoine.perez@eleve.isep.fr';
$motDePasseAdmin = 'Admin123!';
$prenomAdmin = 'Antoine';
$nomAdmin = 'Perez';

try {
    $connexion = BaseDeDonnees::obtenirConnexion();
    
    // Vérifier si l'utilisateur existe déjà
    $verif = $connexion->prepare("SELECT id, role FROM users WHERE email = :email");
    $verif->execute(['email' => $emailAdmin]);
    $utilisateurExistant = $verif->fetch(PDO::FETCH_ASSOC);
    
    if ($utilisateurExistant) {
        // L'utilisateur existe, le promouvoir en admin
        $update = $connexion->prepare("UPDATE users SET role = 'admin', email_verified = TRUE WHERE email = :email");
        $update->execute(['email' => $emailAdmin]);
        
        echo "✅ <strong>Utilisateur existant promu en administrateur !</strong><br><br>";
        echo "📧 Email : <code>{$emailAdmin}</code><br>";
        echo "🔑 Mot de passe : Votre mot de passe actuel<br>";
        echo "👤 ID : {$utilisateurExistant['id']}<br><br>";
        echo "⚠️ <span style='color: orange;'>Vous pouvez maintenant vous connecter avec vos identifiants habituels.</span>";
        
    } else {
        // L'utilisateur n'existe pas, le créer
        
        // 1. Vérifier que la colonne 'role' existe
        $colonnes = $connexion->query("SHOW COLUMNS FROM users LIKE 'role'");
        if ($colonnes->rowCount() === 0) {
            // Ajouter la colonne role si elle n'existe pas
            $connexion->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' NOT NULL AFTER email_verified");
            echo "✅ <strong>Colonne 'role' ajoutée à la table users</strong><br><br>";
        }
        
        // 2. Hacher le mot de passe
        $hashMotDePasse = ServiceChiffrement::hacherMotDePasse($motDePasseAdmin);
        
        // 3. Générer les clés RSA pour la messagerie
        $paireCles = ServiceChiffrement::genererPaireCles();
        
        // 4. Insérer l'utilisateur admin
        $sql = "INSERT INTO users (
                    first_name, 
                    last_name, 
                    email, 
                    password_hash, 
                    role, 
                    email_verified,
                    public_key,
                    private_key,
                    created_at
                ) VALUES (
                    :first_name, 
                    :last_name, 
                    :email, 
                    :password_hash, 
                    'admin', 
                    TRUE,
                    :public_key,
                    :private_key,
                    NOW()
                )";
        
        $stmt = $connexion->prepare($sql);
        $stmt->execute([
            'first_name' => $prenomAdmin,
            'last_name' => $nomAdmin,
            'email' => $emailAdmin,
            'password_hash' => $hashMotDePasse,
            'public_key' => $paireCles['public'],
            'private_key' => $paireCles['private']
        ]);
        
        $idAdmin = $connexion->lastInsertId();
        
        echo "✅ <strong>Compte administrateur créé avec succès !</strong><br><br>";
        echo "📧 Email : <code>{$emailAdmin}</code><br>";
        echo "🔑 Mot de passe : <code>{$motDePasseAdmin}</code><br>";
        echo "👤 ID : {$idAdmin}<br><br>";
        echo "⚠️ <span style='color: red;'><strong>IMPORTANT : Changez ce mot de passe après votre première connexion !</strong></span><br><br>";
        echo "🔒 <span style='color: orange;'>Supprimez ce fichier après utilisation pour des raisons de sécurité.</span>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Erreur lors de la création de l'administrateur :</strong><br>";
    echo "<code>" . htmlspecialchars($e->getMessage()) . "</code><br><br>";
    echo "📋 <strong>Trace :</strong><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création Administrateur - ReVente-Auto</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-top: 0;
            font-size: 28px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
        }
        code {
            background: #f5f5f5;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e91e63;
            font-weight: bold;
        }
        .success { color: #4caf50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
            text-align: center;
            flex: 1;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-danger {
            background: #f44336;
            color: white;
        }
        .btn-danger:hover {
            background: #da190b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Installation Administrateur</h1>
        
        <div class="btn-group">
            <a href="http://localhost/PROJET/ReVente-Auto/connexion" class="btn btn-primary">
                🔐 Se connecter
            </a>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?delete=1" class="btn btn-danger" 
               onclick="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer ce fichier ?');">
                🗑️ Supprimer ce fichier
            </a>
        </div>
    </div>
</body>
</html>

<?php
// Fonctionnalité de suppression automatique
if (isset($_GET['delete']) && $_GET['delete'] == '1') {
    if (unlink(__FILE__)) {
        echo "<script>alert('✅ Fichier supprimé avec succès !'); window.location.href = 'http://localhost/PROJET/ReVente-Auto/';</script>";
    } else {
        echo "<script>alert('❌ Erreur lors de la suppression. Supprimez le fichier manuellement.');</script>";
    }
}
?>
