<?php
namespace TaskManager\Services;

use TaskManager\Config\App;
use TaskManager\Utils\Response;

class FileUploadService {
    private static $allowedTypes = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz']
    ];
    
    private static $maxSizes = [
        'image' => 5242880, // 5MB
        'document' => 10485760, // 10MB
        'archive' => 52428800 // 50MB
    ];
    
    public static function upload($file, $destination = 'uploads/', $type = 'image') {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Aucun fichier uploadé'];
        }
        
        // Vérifier la taille
        if ($file['size'] > self::$maxSizes[$type]) {
            $maxSizeMB = self::$maxSizes[$type] / 1048576;
            return ['success' => false, 'message' => "Fichier trop volumineux. Taille maximale: {$maxSizeMB}MB"];
        }
        
        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::$allowedTypes[$type])) {
            $allowedExtensions = implode(', ', self::$allowedTypes[$type]);
            return ['success' => false, 'message' => "Extension non autorisée. Extensions autorisées: $allowedExtensions"];
        }
        
        // Générer un nom unique
        $fileName = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $destination . $fileName;
        
        // Créer le dossier si nécessaire
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => true,
                'filename' => $fileName,
                'path' => $filePath,
                'size' => $file['size'],
                'type' => $type,
                'extension' => $extension
            ];
        }
        
        return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
    }
    
    public static function delete($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    public static function getFileInfo($filePath) {
        if (!file_exists($filePath)) {
            return null;
        }
        
        return [
            'name' => basename($filePath),
            'size' => filesize($filePath),
            'type' => mime_content_type($filePath),
            'modified' => filemtime($filePath)
        ];
    }
    
    public static function validateImage($filePath) {
        $imageInfo = getimagesize($filePath);
        return $imageInfo !== false;
    }
}