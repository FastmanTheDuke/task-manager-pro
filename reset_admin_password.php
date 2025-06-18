<?php
/**
 * Test rapide avec réinitialisation de mot de passe
 * Version sans PDO complète, utilise les résultats du diagnostic précédent
 */

echo "=== RÉINITIALISATION RAPIDE DU MOT DE PASSE ADMIN ===\n\n";

// Instructions basées sur le diagnostic précédent
echo "Basé sur votre diagnostic précédent :\n";
echo "✅ Base de données: Connectée\n";
echo "✅ Utilisateur admin: admin (admin@taskmanager.local)\n";
echo "❌ Problème: Mot de passe ou validation API\n\n";

echo "🔧 SOLUTION RAPIDE VIA MYSQL:\n\n";

echo "1. Connectez-vous à MySQL:\n";
echo "   mysql -u root -p task_manager_pro\n\n";

echo "2. Réinitialisez le mot de passe admin:\n";
$newPasswordHash = password_hash('Admin123!', PASSWORD_DEFAULT);
echo "   UPDATE users SET password = '$newPasswordHash' WHERE username = 'admin';\n\n";

echo "3. Vérifiez la mise à jour:\n";
echo "   SELECT username, email, role FROM users WHERE username = 'admin';\n\n";

echo "🧪 TESTS DIRECTS DE L'API:\n\n";

echo "Après avoir réinitialisé le mot de passe, testez :\n\n";

echo "Test 1 - Email:\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"login\":\"admin@taskmanager.local\",\"password\":\"Admin123!\"}'\n\n";

echo "Test 2 - Username:\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"login\":\"admin\",\"password\":\"Admin123!\"}'\n\n";

echo "🔍 DIAGNOSTIC DE L'ERREUR HTTP 422:\n\n";

echo "L'erreur HTTP 422 'Erreur de validation' peut venir de :\n";
echo "1. Champ 'login' non reconnu par ValidationService\n";
echo "2. Règles de validation trop strictes\n";
echo "3. Problème dans ValidationMiddleware\n\n";

echo "📋 COMMANDES DE DEBUG SUPPLÉMENTAIRES:\n\n";

echo "1. Vérifiez les logs du serveur backend:\n";
echo "   tail -f backend/logs/errors_" . date('Y-m-d') . ".log\n\n";

echo "2. Test avec des données incorrectes (pour voir le message d'erreur):\n";
echo "   curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "     -H \"Content-Type: application/json\" \\\n";
echo "     -d '{\"email\":\"admin@taskmanager.local\",\"password\":\"Admin123!\"}'\n\n";

echo "3. Test avec champ manquant:\n";
echo "   curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "     -H \"Content-Type: application/json\" \\\n";
echo "     -d '{\"login\":\"admin\"}'\n\n";

echo "🎯 SOLUTION ALTERNATIVE TEMPORAIRE:\n\n";

echo "Si l'API ne fonctionne toujours pas, testez l'ancien endpoint:\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"email\":\"admin@taskmanager.local\",\"password\":\"Admin123!\"}'\n\n";

echo "💡 VÉRIFICATION DU SERVEUR:\n\n";
echo "Assurez-vous que le serveur backend tourne:\n";
echo "cd backend\n";
echo "php -S localhost:8000 router.php\n\n";

echo "Et dans un autre terminal, testez:\n";
echo "curl http://localhost:8000/api/health\n\n";

echo "=== ÉTAPES RECOMMANDÉES ===\n\n";

echo "1. ✅ Réinitialisez le mot de passe via MySQL\n";
echo "2. ✅ Testez l'API avec curl\n";
echo "3. ✅ Si erreur 422 persiste, vérifiez les logs\n";
echo "4. ✅ Testez avec l'ancien format {\"email\":...}\n\n";

echo "=== HASH DU MOT DE PASSE ===\n";
echo "Si vous voulez faire la commande SQL manuellement :\n";
echo "Mot de passe: Admin123!\n";
echo "Hash généré: $newPasswordHash\n\n";

echo "Commande SQL complète :\n";
echo "UPDATE users SET password = '$newPasswordHash' WHERE username = 'admin';\n\n";

echo "=== FIN DU GUIDE ===\n";
