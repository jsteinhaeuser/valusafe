<?php
/**
 * image_optimizer.php
 * Optimiert Bilder auf 800x600 für Backups
 */

class ImageOptimizer {
    private $maxWidth = 800;
    private $maxHeight = 600;
    private $quality = 85;
    
    public function __construct($maxWidth = 800, $maxHeight = 600, $quality = 85) {
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
        $this->quality = $quality;
    }
    
    public function optimizeImage($sourcePath, $destPath) {
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Wenn bereits klein genug, kopieren
        if ($width <= $this->maxWidth && $height <= $this->maxHeight) {
            return copy($sourcePath, $destPath);
        }
        
        // Bild laden
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = @imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = @imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $source = @imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        // Neue Dimensionen
        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        $destination = imagecreatetruecolor($newWidth, $newHeight);
        
        // Transparenz beibehalten
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Speichern
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($destination, $destPath, $this->quality);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($destination, $destPath, 6);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($destination, $destPath);
                break;
            case IMAGETYPE_WEBP:
                $result = imagewebp($destination, $destPath, $this->quality);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($destination);
        
        return $result;
    }
    
    public static function formatSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' Bytes';
    }
}
?>
