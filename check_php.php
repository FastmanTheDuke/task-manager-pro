<?php
/**
 * Test rapide pour identifier quelle installation PHP est active
 */

echo "🔍 Diagnostic PHP - Task Manager Pro\n";
echo "=====================================\n\n";

echo "📍 Installation PHP active:\n";
echo "   Version: " . PHP_VERSION . "\n";
echo "   Binaire: " . PHP_BINARY . "\n";
echo "   php.ini: " . php_ini_loaded_file() . "\n\n";

echo "📦 Extensions liées à MySQL:\n";
$mysqlExtensions = ['pdo', 'pdo_mysql', 'mysql', 'mysqli'];
foreach ($mysqlExtensions as $ext) {
    $status = extension_loaded($ext) ? "✅ ACTIVÉE" : "❌ MANQUANTE";
    echo "   - $ext: $status\n";
}

echo "\n🌍 Variables d'environnement:\n";
echo "   PATH: " . $_ENV['PATH'] . "\n\n";

echo "💡 Recommandations:\n";
if (!extension_loaded('pdo_mysql')) {
    echo "   ❌ pdo_mysql manquante - activez-la dans: " . php_ini_loaded_file() . "\n";
    echo "   🔧 Recherchez ';extension=pdo_mysql' et enlevez le ';'\n";
} else {
    echo "   ✅ pdo_mysql est activée - votre PHP est prêt!\n";
}

if (strpos(PHP_BINARY, 'xampp') !== false) {
    echo "   ✅ Vous utilisez le PHP de XAMPP - parfait!\n";
} else {
    echo "   ⚠️  Vous utilisez un PHP standalone\n";
    echo "   💡 Pour utiliser XAMPP: set PATH=C:\\xampp\\php;%PATH%\n";
}
