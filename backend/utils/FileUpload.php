<?php
namespace TaskManager\Utils;

use TaskManager\Config\App;

class FileUpload {
    private $file;
    private $errors = [];
    
    public function __construct($file) {
        $this->file = $file;
    }
    
    public function validate() {
        // Vérifier les erreurs PHP
        if ($this->file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($this->file['error']);
            return false;
        }
        
        // Vérifier la taille
        $maxSize = App::get('max_upload_size');
        if ($this->file['size'] > $maxSize) {
            $this->errors[] = 'Le fichier est trop volumineux (max: ' . $this->formatBytes($maxSize) . ')';
            return false;
        }
        
        // Vérifier l'extension
        $extension = $this->getExtension();
        $allowedExtensions = App::get('allowed_extensions');
        
        if (!in_array($extension, $allowedExtensions)) {
            $this->errors[] = 'Type de fichier non autorisé';
            return false;
        }
        
        // Vérifier le type MIME
        if (!$this->isValidMimeType()) {
            $this->errors[] = 'Type MIME invalide';
            return false;
        }
        
        return true;
    }
    
    public function upload($directory = null) {
        if (!$this->validate()) {
            return false;
        }
        
        if ($directory === null) {
            $directory = App::get('upload_dir');
        }
        
        // Créer le répertoire s'il n'existe pas
        $uploadPath = $directory . '/' . date('Y/m');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Générer un nom unique
        $filename = $this->generateFilename();
        $filepath = $uploadPath . '/' . $filename;
        
        // Déplacer le fichier
        if (move_uploaded_file($this->file['tmp_name'], $filepath)) {
            return [
                'filename' => $filename,
                'original_name' => $this->file['name'],
                'mime_type' => $this->file['type'],
                'size' => $this->file['size'],
                'path' => $filepath
            ];
        }
        
        $this->errors[] = 'Erreur lors du déplacement du fichier';
        return false;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    private function getExtension() {
        return strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION));
    }
    
    private function generateFilename() {
        $extension = $this->getExtension();
        return uniqid() . '_' . time() . '.' . $extension;
    }
    
    private function isValidMimeType() {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $this->file['tmp_name']);
        finfo_close($finfo);
        
        // Vérifier que le type MIME correspond à l'extension
        $extension = $this->getExtension();
        $expectedTypes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'zip' => ['application/zip', 'application/x-zip-compressed']
        ];
        
        if (isset($expectedTypes[$extension])) {
            return in_array($mimeType, $expectedTypes[$extension]);
        }
        
        return false;
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    private function getUploadErrorMessage($error) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'envoi du fichier'
        ];
        
        return $messages[$error] ?? 'Erreur inconnue lors du téléchargement';
    }
}