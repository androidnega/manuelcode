<?php
/**
 * Cloudinary Helper Class
 * Handles all image uploads to Cloudinary using cURL (no composer required)
 */

class CloudinaryHelper {
    private $cloud_name;
    private $api_key;
    private $api_secret;
    private $upload_preset;
    private $enabled;
    
    public function __construct($pdo) {
        // Get Cloudinary settings from database
        $stmt = $pdo->prepare("SELECT setting_key, value FROM settings WHERE setting_key LIKE 'cloudinary_%'");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['value'];
        }
        
        $this->enabled = isset($settings['cloudinary_enabled']) && $settings['cloudinary_enabled'] === '1';
        $this->cloud_name = $settings['cloudinary_cloud_name'] ?? '';
        $this->api_key = $settings['cloudinary_api_key'] ?? '';
        $this->api_secret = $settings['cloudinary_api_secret'] ?? '';
        $this->upload_preset = $settings['cloudinary_upload_preset'] ?? '';
    }
    
    /**
     * Generate Cloudinary signature for authenticated uploads
     */
    private function generateSignature($params) {
        $params['timestamp'] = time();
        ksort($params);
        $signature_string = '';
        foreach ($params as $key => $value) {
            $signature_string .= $key . '=' . $value . '&';
        }
        $signature_string = rtrim($signature_string, '&');
        $signature_string .= $this->api_secret;
        return sha1($signature_string);
    }
    
    /**
     * Upload image to Cloudinary
     * @param string $filePath Temporary file path
     * @param string $folder Folder name in Cloudinary (optional)
     * @param array $options Additional upload options
     * @return array|false Returns array with 'url' and 'public_id' on success, false on failure
     */
    public function uploadImage($filePath, $folder = 'products', $options = []) {
        if (!$this->enabled || empty($this->cloud_name)) {
            return false;
        }
        
        if (!file_exists($filePath)) {
            error_log("Cloudinary upload error: File not found - " . $filePath);
            return false;
        }
        
        try {
            $uploadUrl = "https://api.cloudinary.com/v1_1/{$this->cloud_name}/image/upload";
            
            // Prepare parameters
            $params = [];
            if (!empty($folder)) {
                $params['folder'] = $folder;
            }
            if (!empty($this->upload_preset)) {
                $params['upload_preset'] = $this->upload_preset;
            }
            
            // Add custom options
            foreach ($options as $key => $value) {
                $params[$key] = $value;
            }
            
            // If no upload preset, use signed upload
            if (empty($this->upload_preset) && !empty($this->api_key) && !empty($this->api_secret)) {
                $params['api_key'] = $this->api_key;
                $signature = $this->generateSignature($params);
                $params['signature'] = $signature;
            }
            
            // Prepare file for upload
            $file = new CURLFile($filePath, mime_content_type($filePath), basename($filePath));
            $params['file'] = $file;
            
            // Upload via cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $uploadUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("Cloudinary cURL error: " . $error);
                return false;
            }
            
            if ($httpCode !== 200) {
                error_log("Cloudinary upload error: HTTP $httpCode - " . $response);
                return false;
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['secure_url'])) {
                return [
                    'url' => $result['secure_url'],
                    'public_id' => $result['public_id'],
                    'format' => $result['format'] ?? '',
                    'width' => $result['width'] ?? 0,
                    'height' => $result['height'] ?? 0,
                    'bytes' => $result['bytes'] ?? 0
                ];
            }
            
            error_log("Cloudinary upload error: Invalid response - " . $response);
            return false;
            
        } catch (Exception $e) {
            error_log("Cloudinary upload error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload multiple images
     * @param array $filePaths Array of temporary file paths
     * @param string $folder Folder name in Cloudinary
     * @return array Array of upload results
     */
    public function uploadMultipleImages($filePaths, $folder = 'products') {
        $results = [];
        foreach ($filePaths as $filePath) {
            $result = $this->uploadImage($filePath, $folder);
            if ($result) {
                $results[] = $result;
            }
        }
        return $results;
    }
    
    /**
     * Delete image from Cloudinary
     * @param string $publicId Public ID of the image
     * @return bool Success status
     */
    public function deleteImage($publicId) {
        if (!$this->enabled || empty($this->cloud_name) || empty($this->api_key) || empty($this->api_secret)) {
            return false;
        }
        
        try {
            $params = [
                'public_id' => $publicId,
                'timestamp' => time()
            ];
            
            $signature = $this->generateSignature($params);
            $params['api_key'] = $this->api_key;
            $params['signature'] = $signature;
            
            $queryString = http_build_query($params);
            $deleteUrl = "https://api.cloudinary.com/v1_1/{$this->cloud_name}/image/destroy?{$queryString}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $deleteUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                return isset($result['result']) && $result['result'] === 'ok';
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Cloudinary delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if Cloudinary is enabled and configured
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled && !empty($this->cloud_name);
    }
    
    /**
     * Get optimized image URL
     * @param string $publicId Public ID or full URL
     * @param array $transformations Transformations array
     * @return string Optimized URL
     */
    public function getOptimizedUrl($publicId, $transformations = []) {
        if (!$this->enabled || empty($this->cloud_name)) {
            return $publicId; // Return original if Cloudinary not enabled
        }
        
        // If it's already a full URL, return as is
        if (strpos($publicId, 'http') === 0) {
            return $publicId;
        }
        
        try {
            $baseUrl = "https://res.cloudinary.com/{$this->cloud_name}/image/upload";
            
            // Build transformation string
            $transformString = '';
            if (!empty($transformations)) {
                $parts = [];
                foreach ($transformations as $key => $value) {
                    $parts[] = "{$key}_{$value}";
                }
                $transformString = implode(',', $parts) . '/';
            }
            
            return $baseUrl . '/' . $transformString . $publicId;
        } catch (Exception $e) {
            error_log("Cloudinary URL generation error: " . $e->getMessage());
            return $publicId;
        }
    }
}
?>

