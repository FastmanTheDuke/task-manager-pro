<?php
/**
 * Script de vérification des corrections
 * 
 * Execute ce script pour vérifier que les corrections sont bien appliquées
 * Usage: php check_fix.php
 */

echo "🔧 VÉRIFICATION DES CORRECTIONS - Task Manager Pro\n";
echo "================================================\n\n";

// Vérification 1: Fichier ResponseService.php
echo "1. Vérification de ResponseService.php...\n";
$responseServicePath = __DIR__ . '/backend/Services/ResponseService.php';
if (file_exists($responseServicePath)) {
    $content = file_get_contents($responseServicePath);
    if (strpos($content, 'sanitizeMessage') !== false) {
        echo "   ✅ ResponseService.php contient la méthode sanitizeMessage\n";
    } else {
        echo "   ❌ ResponseService.php ne contient pas la méthode sanitizeMessage\n";
    }
    
    if (strpos($content, 'private static function sanitizeMessage') !== false) {
        echo "   ✅ La méthode sanitizeMessage est correctement définie\n";
    } else {
        echo "   ❌ La méthode sanitizeMessage n'est pas correctement définie\n";
    }
} else {
    echo "   ❌ Fichier ResponseService.php non trouvé\n";
}

echo "\n";

// Vérification 2: Fichier Utils/Response.php
echo "2. Vérification de Utils/Response.php...\n";
$utilsResponsePath = __DIR__ . '/backend/Utils/Response.php';
if (file_exists($utilsResponsePath)) {
    echo "   ✅ Le fichier alias Utils/Response.php existe\n";
    
    $content = file_get_contents($utilsResponsePath);
    if (strpos($content, 'use TaskManager\Services\ResponseService') !== false) {
        echo "   ✅ L'alias pointe vers ResponseService\n";
    } else {
        echo "   ❌ L'alias ne pointe pas vers ResponseService\n";
    }
} else {
    echo "   ❌ Fichier Utils/Response.php non trouvé\n";
    echo "   📝 Ce fichier est nécessaire pour maintenir la compatibilité\n";
}

echo "\n";

// Vérification 3: Dossier Utils
echo "3. Vérification du dossier Utils...\n";
$utilsDir = __DIR__ . '/backend/Utils';
if (!is_dir($utilsDir)) {
    echo "   📁 Création du dossier Utils...\n";
    if (mkdir($utilsDir, 0755, true)) {
        echo "   ✅ Dossier Utils créé avec succès\n";
    } else {
        echo "   ❌ Impossible de créer le dossier Utils\n";
    }
} else {
    echo "   ✅ Le dossier Utils existe\n";
}

echo "\n";

// Vérification 4: Fichiers d'authentification
echo "4. Vérification des fichiers d'authentification...\n";
$authFiles = [
    'login.php' => __DIR__ . '/backend/api/auth/login.php',
    'register.php' => __DIR__ . '/backend/api/auth/register.php'
];

foreach ($authFiles as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, 'use TaskManager\Utils\Response') !== false) {
            echo "   ✅ $name utilise l'import correct\n";
        } else {
            echo "   ❌ $name n'utilise pas l'import correct\n";
        }
        
        if (strpos($content, 'error_log') !== false && strpos($content, 'getTraceAsString') !== false) {
            echo "   ✅ $name a un logging amélioré\n";
        } else {
            echo "   ⚠️  $name pourrait bénéficier d'un logging amélioré\n";
        }
    } else {
        echo "   ❌ Fichier $name non trouvé\n";
    }
}

echo "\n";

// Test de la méthode sanitizeMessage
echo "5. Test de la méthode sanitizeMessage...\n";
if (file_exists($responseServicePath)) {
    try {
        // Inclure le fichier
        require_once __DIR__ . '/backend/Bootstrap.php';
        
        // Tester avec différents types de données
        $reflection = new ReflectionClass('TaskManager\Services\ResponseService');
        $method = $reflection->getMethod('sanitizeMessage');
        $method->setAccessible(true);
        
        $testCases = [
            'string simple' => 'Erreur normale',
            'array' => ['erreur1', 'erreur2'],
            'object stdClass' => (object)['message' => 'erreur objet'],
            'boolean true' => true,
            'boolean false' => false,
            'number' => 42,
            'null' => null
        ];
        
        $allPassed = true;
        foreach ($testCases as $type => $value) {
            try {
                $result = $method->invoke(null, $value);
                if (is_string($result)) {
                    echo "   ✅ Test '$type': " . substr($result, 0, 50) . "...\n";
                } else {
                    echo "   ❌ Test '$type': résultat n'est pas une string\n";
                    $allPassed = false;
                }
            } catch (Exception $e) {
                echo "   ❌ Test '$type': exception " . $e->getMessage() . "\n";
                $allPassed = false;
            }
        }
        
        if ($allPassed) {
            echo "   ✅ Tous les tests de sanitizeMessage sont passés\n";
        }
        
    } catch (Exception $e) {
        echo "   ⚠️  Impossible de tester sanitizeMessage: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Impossible de tester - fichier ResponseService manquant\n";
}

echo "\n";

// Résumé
echo "=================\n";
echo "📋 RÉSUMÉ:\n";
echo "=================\n";

// Compter les vérifications réussies
$allFiles = [
    $responseServicePath,
    $utilsResponsePath,
    $authFiles['login.php'],
    $authFiles['register.php']
];

$existingFiles = array_filter($allFiles, 'file_exists');
$totalFiles = count($allFiles);
$existingCount = count($existingFiles);

if ($existingCount === $totalFiles) {
    echo "✅ Tous les fichiers requis sont présents ($existingCount/$totalFiles)\n";
    echo "🚀 Les corrections semblent correctement appliquées!\n";
    echo "\n";
    echo "📝 PROCHAINES ÉTAPES:\n";
    echo "1. Redémarrez votre serveur web\n";
    echo "2. Testez la connexion sur http://localhost:3000/login\n";
    echo "3. Testez l'inscription sur http://localhost:3000/register\n";
    echo "\n";
    echo "🎉 L'erreur 'Array to string conversion' devrait être résolue!\n";
} else {
    echo "⚠️  Certains fichiers sont manquants ($existingCount/$totalFiles)\n";
    echo "📝 Assurez-vous d'avoir fait un 'git pull' pour récupérer les dernières modifications\n";
}

echo "\n";
echo "Fin de la vérification.\n";
?>