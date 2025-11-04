#!/bin/bash

# Script pour importer automatiquement le schéma SQL sur Railway
# Usage: ./import-db-railway.sh

echo "🚂 Import de la base de données sur Railway"
echo "============================================"
echo ""

# Vérifier si Railway CLI est installé
if ! command -v railway &> /dev/null; then
    echo "❌ Railway CLI n'est pas installé."
    echo ""
    echo "Pour installer Railway CLI:"
    echo "  npm install -g @railway/cli"
    echo ""
    echo "Puis exécutez: railway login"
    exit 1
fi

# Vérifier si le projet est lié
if [ ! -f ".railway" ]; then
    echo "⚠️  Projet Railway non lié."
    echo ""
    echo "Exécutez d'abord: railway link"
    echo ""
    exit 1
fi

echo "📋 Import du schéma SQL..."
echo ""

# Import du schéma via Railway CLI
railway run -s MySQL mysql -u \$MYSQLUSER -p\$MYSQLPASSWORD -h \$MYSQLHOST -P \$MYSQLPORT \$MYSQLDATABASE < database/schema.sql

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Schéma importé avec succès!"
    echo ""
    echo "Vous pouvez maintenant:"
    echo "  1. Vérifier les tables: railway run -s MySQL mysql -e 'SHOW TABLES;'"
    echo "  2. Déployer votre application"
else
    echo ""
    echo "❌ Erreur lors de l'import du schéma."
    echo ""
    echo "Vérifiez que:"
    echo "  - Le service MySQL est bien démarré sur Railway"
    echo "  - Les credentials sont corrects"
fi
