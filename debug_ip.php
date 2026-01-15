<?php
/**
 * Script de diagnostic des headers IP reçus par PHP
 * À placer à la racine et accéder via : https://votresite.com/debug_ip.php
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic IP - ReVente-Auto</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header-row {
            display: flex;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .header-row:nth-child(even) {
            background: #f9f9f9;
        }
        .header-name {
            font-weight: bold;
            width: 300px;
            color: #2c3e50;
        }
        .header-value {
            flex: 1;
            color: #34495e;
            font-family: 'Courier New', monospace;
        }
        .present {
            color: #27ae60;
        }
        .absent {
            color: #e74c3c;
            font-style: italic;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .current-detection {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic des Headers IP</h1>
    
    <?php
    // Headers à vérifier
    $headersIp = [
        'HTTP_CF_CONNECTING_IP' => 'Cloudflare - IP réelle du client',
        'HTTP_X_REAL_IP' => 'Nginx - IP réelle du client',
        'HTTP_X_FORWARDED_FOR' => 'Proxy standard - Liste des IPs (client, proxy1, proxy2...)',
        'HTTP_X_FORWARDED' => 'Variante X-Forwarded',
        'HTTP_FORWARDED_FOR' => 'Variante Forwarded-For',
        'HTTP_FORWARDED' => 'RFC 7239 - Header Forwarded standard',
        'HTTP_CLIENT_IP' => 'Header client IP (parfois utilisé)',
        'REMOTE_ADDR' => 'IP directement connectée au serveur (peut être le proxy)',
        'SERVER_ADDR' => 'IP du serveur web',
    ];
    
    // Fonction pour obtenir l'IP comme dans Utilitaires.php
    function obtenirIpClient() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    if ($ip === '::1') {
                        $ip = '127.0.0.1';
                    }
                    return ['ip' => $ip, 'source' => $header];
                }
            }
        }
        return ['ip' => 'unknown', 'source' => 'none'];
    }
    
    $detectionActuelle = obtenirIpClient();
    $ipDetectee = $detectionActuelle['ip'];
    $sourceDetectee = $detectionActuelle['source'];
    
    // Analyser le type d'IP
    $estPrivee = false;
    $estLocale = in_array($ipDetectee, ['127.0.0.1', '::1', 'localhost']);
    
    if (!$estLocale && filter_var($ipDetectee, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // Vérifier si c'est une IP privée (10.x.x.x, 172.16-31.x.x, 192.168.x.x)
        $octets = explode('.', $ipDetectee);
        $premier = (int)$octets[0];
        $deuxieme = (int)$octets[1];
        
        if ($premier === 10) {
            $estPrivee = true;
        } elseif ($premier === 172 && $deuxieme >= 16 && $deuxieme <= 31) {
            $estPrivee = true;
        } elseif ($premier === 192 && $deuxieme === 168) {
            $estPrivee = true;
        }
    }
    ?>
    
    <div class="section">
        <h2>📊 IP Détectée par le Système</h2>
        <div class="current-detection">
            IP : <?php echo htmlspecialchars($ipDetectee); ?><br>
            Source : <code><?php echo htmlspecialchars($sourceDetectee); ?></code>
        </div>
        
        <?php if ($estLocale): ?>
            <div class="warning">
                <strong>⚠️ IP Locale Détectée</strong><br>
                Vous testez depuis localhost (127.0.0.1). C'est normal en développement.
            </div>
        <?php elseif ($estPrivee): ?>
            <div class="error">
                <strong>🚨 PROBLÈME : IP Privée Détectée</strong><br>
                L'IP <strong><?php echo htmlspecialchars($ipDetectee); ?></strong> est une adresse privée (réseau local).<br>
                Cela signifie que PHP reçoit l'IP du proxy/load balancer, pas l'IP publique du client.<br><br>
                <strong>Solution :</strong> Votre hébergeur/reverse proxy doit transmettre l'IP réelle du client via un header comme <code>X-Forwarded-For</code> ou <code>X-Real-IP</code>.
            </div>
        <?php else: ?>
            <div class="success">
                <strong>✅ IP Publique Détectée</strong><br>
                Le système détecte correctement votre IP publique.
            </div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>📋 Tous les Headers $_SERVER Liés aux IP</h2>
        <?php foreach ($headersIp as $header => $description): ?>
            <div class="header-row">
                <div class="header-name">
                    <?php echo htmlspecialchars($header); ?><br>
                    <small style="color: #7f8c8d; font-weight: normal;"><?php echo htmlspecialchars($description); ?></small>
                </div>
                <div class="header-value">
                    <?php if (isset($_SERVER[$header]) && !empty($_SERVER[$header])): ?>
                        <span class="present"><?php echo htmlspecialchars($_SERVER[$header]); ?></span>
                    <?php else: ?>
                        <span class="absent">(non défini)</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="section">
        <h2>🔧 Diagnostic et Solutions</h2>
        
        <?php if ($estPrivee): ?>
            <h3>🚨 PROBLÈME IDENTIFIÉ : IP Proxy au lieu de l'IP Client</h3>
            <p>Votre serveur est derrière un reverse proxy/load balancer qui <strong>ne transmet pas</strong> l'IP réelle du client.</p>
            
            <h3>Solutions selon votre hébergement :</h3>
            
            <h4>1️⃣ Si vous utilisez Cloudflare :</h4>
            <p>Cloudflare devrait automatiquement ajouter le header <code>CF-Connecting-IP</code>.<br>
            Vérifiez que "Transform Rules" ne bloquent pas ce header.</p>
            
            <h4>2️⃣ Si vous utilisez Nginx en reverse proxy :</h4>
            <p>Ajoutez dans votre configuration Nginx :</p>
            <code style="display:block; padding:10px; margin:10px 0;">
                proxy_set_header X-Real-IP $remote_addr;<br>
                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            </code>
            
            <h4>3️⃣ Si vous utilisez Apache avec mod_proxy :</h4>
            <p>Ajoutez dans votre VirtualHost :</p>
            <code style="display:block; padding:10px; margin:10px 0;">
                ProxyPreserveHost On<br>
                ProxyAddHeaders On
            </code>
            
            <h4>4️⃣ Si vous utilisez un Load Balancer Cloud (AWS ELB, Azure LB, etc.) :</h4>
            <p>Activez l'option "Preserve client IP" ou "X-Forwarded-For" dans la configuration du load balancer.</p>
            
            <h4>5️⃣ Solution temporaire (mod_remoteip Apache) :</h4>
            <p>Si vous ne pouvez pas modifier la config du proxy, activez mod_remoteip dans Apache :</p>
            <code style="display:block; padding:10px; margin:10px 0;">
                # Dans httpd.conf ou dans .htaccess<br>
                RemoteIPHeader X-Forwarded-For<br>
                RemoteIPTrustedProxy 10.10.10.0/24
            </code>
            <p><small>⚠️ Remplacez <code>10.10.10.0/24</code> par le réseau de vos proxies internes</small></p>
            
        <?php elseif ($estLocale): ?>
            <p>✅ Configuration normale pour un environnement de développement.</p>
            <p>En production avec un vrai reverse proxy, les headers <code>X-Forwarded-For</code> ou <code>X-Real-IP</code> seront automatiquement remplis.</p>
        <?php else: ?>
            <p>✅ Tout fonctionne correctement ! L'IP publique est bien détectée.</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>📞 Contacter Votre Hébergeur</h2>
        <p>Si vous ne pouvez pas modifier la configuration du proxy vous-même, contactez votre hébergeur avec ce message :</p>
        <code style="display:block; padding:15px; margin:10px 0; background:#f8f9fa; border:1px solid #dee2e6;">
            Bonjour,<br><br>
            Mon application PHP a besoin de récupérer l'adresse IP réelle des visiteurs.<br>
            Actuellement, $_SERVER['REMOTE_ADDR'] retourne l'IP du proxy interne (<?php echo htmlspecialchars($ipDetectee); ?>).<br><br>
            Pourriez-vous configurer le reverse proxy/load balancer pour transmettre l'IP réelle du client via un des headers suivants ?<br>
            - X-Forwarded-For<br>
            - X-Real-IP<br>
            - CF-Connecting-IP (si Cloudflare)<br><br>
            Merci !
        </code>
    </div>
    
    <div class="section">
        <h2>📚 Tous les $_SERVER Disponibles</h2>
        <details>
            <summary style="cursor:pointer; color:#3498db; font-weight:bold;">Cliquez pour afficher tous les $_SERVER (debug complet)</summary>
            <div style="margin-top:15px;">
                <?php foreach ($_SERVER as $key => $value): ?>
                    <div class="header-row">
                        <div class="header-name"><?php echo htmlspecialchars($key); ?></div>
                        <div class="header-value"><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>
    </div>
    
    <div style="text-align:center; margin-top:40px; color:#7f8c8d;">
        <p>ReVente-Auto - Diagnostic IP v1.0</p>
        <p><small>⚠️ SUPPRIMEZ ce fichier après diagnostic pour des raisons de sécurité</small></p>
    </div>
</body>
</html>
