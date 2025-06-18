<?php
/**
 * Diagnostic PHP - Vérification des extensions et configuration
 */

echo "=== DIAGNOSTIC PHP ===\n\n";

// Version PHP
echo "1. Version PHP...\n";
echo "   Version: " . PHP_VERSION . "\n";
echo "   SAPI: " . php_sapi_name() . "\n\n";

// Extensions requises
echo "2. Extensions PHP requises...\n";
$requiredExtensions = [
    'pdo' => 'PDO (base de données)',
    'pdo_mysql' => 'PDO MySQL (driver MySQL)',
    'mysql' => 'MySQL (legacy)',
    'mysqli' => 'MySQLi (amélioré)',
    'json' => 'JSON',
    'mbstring' => 'Multibyte String',
    'curl' => 'cURL',
    'openssl' => 'OpenSSL'
];

$missing = [];
foreach ($requiredExtensions as $ext => $description) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext - $description\n";
    } else {
        echo "   ❌ $ext - $description (MANQUANT)\n";
        $missing[] = $ext;
    }
}
echo "\n";

// Configuration PHP
echo "3. Configuration PHP...\n";
$configs = [
    'extension_dir' => 'Répertoire des extensions',
    'include_path' => 'Chemin d\'inclusion'
];

foreach ($configs as $config => $description) {
    $value = ini_get($config);
    echo "   $description: $value\n";
}
echo "\n";

// Extensions disponibles
echo "4. Toutes les extensions chargées...\n";
$loadedExtensions = get_loaded_extensions();
sort($loadedExtensions);
$chunks = array_chunk($loadedExtensions, 5);
foreach ($chunks as $chunk) {
    echo "   " . implode(', ', $chunk) . "\n";
}
echo "\n";

// Test de connexion basique
echo "5. Test de pilotes de base de données disponibles...\n";
if (class_exists('PDO')) {
    echo "   ✅ Classe PDO disponible\n";
    $drivers = PDO::getAvailableDrivers();
    if (count($drivers) > 0) {
        echo "   Pilotes PDO disponibles: " . implode(', ', $drivers) . "\n";
        if (in_array('mysql', $drivers)) {
            echo "   ✅ Pilote MySQL disponible\n";
        } else {
            echo "   ❌ Pilote MySQL non disponible\n";
        }
    } else {
        echo "   ❌ Aucun pilote PDO disponible\n";
    }
} else {
    echo "   ❌ Classe PDO non disponible\n";
}
echo "\n";

// Solutions
echo "=== SOLUTIONS ===\n\n";

if (!empty($missing)) {
    echo "Extensions manquantes détectées: " . implode(', ', $missing) . "\n\n";
    
    echo "📋 SOLUTIONS POUR WINDOWS:\n\n";
    
    echo "Option 1: XAMPP/WAMP (Recommandé)\n";
    echo "- Téléchargez XAMPP: https://www.apachefriends.org/\n";
    echo "- Ou WAMP: https://www.wampserver.com/\n";
    echo "- Ces packages incluent PHP avec toutes les extensions\n\n";
    
    echo "Option 2: PHP standalone\n";
    echo "1. Téléchargez PHP depuis: https://windows.php.net/download/\n";
    echo "2. Éditez php.ini et décommentez ces lignes:\n";
    echo "   extension=pdo_mysql\n";
    echo "   extension=mysqli\n";
    echo "   extension=curl\n";
    echo "   extension=mbstring\n";
    echo "   extension=openssl\n\n";
    
    echo "Option 3: Vérification rapide\n";
    echo "1. Trouvez votre php.ini:\n";
    $phpIni = php_ini_loaded_file();
    echo "   Fichier php.ini: " . ($phpIni ?: "Non trouvé") . "\n";
    echo "2. Vérifiez que ces lignes ne sont PAS commentées (pas de ; au début):\n";
    foreach ($missing as $ext) {
        if (in_array($ext, ['pdo_mysql', 'mysqli', 'curl', 'mbstring', 'openssl'])) {
            echo "   extension=$ext\n";
        }
    }
    echo "3. Redémarrez votre serveur web\n\n";
    
    echo "📁 Localisation du php.ini:\n";
    echo "   Fichier chargé: " . (php_ini_loaded_file() ?: "Aucun") . "\n";
    echo "   Répertoire de scan: " . (php_ini_scanned_files() ?: "Aucun") . "\n\n";
}

echo "🔄 COMMANDES DE TEST APRÈS CORRECTION:\n";
echo "1. Vérifiez PHP: php -v\n";
echo "2. Vérifiez les extensions: php -m | grep -i mysql\n";
echo "3. Relancez le diagnostic: php debug_login_cli.php\n\n";

echo "💡 ALTERNATIVE TEMPORAIRE:\n";
echo "Si vous voulez tester rapidement sans MySQL:\n";
echo "1. Utilisez XAMPP Control Panel\n";
echo "2. Démarrez Apache et MySQL\n";
echo "3. Utilisez le PHP de XAMPP: C:\\xampp\\php\\php.exe debug_login_cli.php\n\n";

echo "=== FIN DU DIAGNOSTIC PHP ===\n";
