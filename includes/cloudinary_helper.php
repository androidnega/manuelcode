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
     * Note: 'file' parameter should NOT be included in signature calculation
     */
    private function generateSignature($params) {
        // Remove 'file' from params if present (shouldn't be, but just in case)
        $signatureParams = $params;
        unset($signatureParams['file']);
        
        // Ensure timestamp is set
        if (!isset($signatureParams['timestamp'])) {
            $signatureParams['timestamp'] = time();
        }
        
        // Sort parameters alphabetically
        ksort($signatureParams);
        
        // Build signature string
        $signature_string = '';
        foreach ($signatureParams as $key => $value) {
            // Handle arrays (like transformation parameters)
            if (is_array($value)) {
                $value = json_encode($value);
            }
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
            error_log("Cloudinary upload error: Cloudinary is not enabled or cloud_name is empty");
            return ['error' => 'Cloudinary is not enabled or cloud_name is empty'];
        }
        
        if (!file_exists($filePath)) {
            error_log("Cloudinary upload error: File not found - " . $filePath);
            return ['error' => 'File not found'];
        }
        
        // Check if upload method is configured
        $hasPreset = !empty($this->upload_preset);
        $hasCredentials = !empty($this->api_key) && !empty($this->api_secret);
        
        if (!$hasPreset && !$hasCredentials) {
            $error_msg = 'No upload method configured. Please configure either an Upload Preset OR API Key + API Secret in System Settings.';
            error_log("Cloudinary upload error: " . $error_msg);
            return ['error' => $error_msg];
        }
        
        try {
            $uploadUrl = "https://api.cloudinary.com/v1_1/{$this->cloud_name}/image/upload";
            
            // Prepare parameters (excluding file for signature calculation)
            $params = [];
            if (!empty($folder)) {
                $params['folder'] = $folder;
            }
            
            // Add custom options (but exclude 'file' and 'transformation' which are not part of signature)
            // When using unsigned upload preset, exclude 'overwrite' as it's not allowed
            foreach ($options as $key => $value) {
                // Skip transformation and file - these are handled separately
                if ($key !== 'file' && $key !== 'transformation') {
                    // When using upload preset (unsigned), don't include 'overwrite' parameter
                    if ($hasPreset && $key === 'overwrite') {
                        error_log("Cloudinary: Skipping 'overwrite' parameter for unsigned upload preset");
                        continue;
                    }
                    $params[$key] = $value;
                }
            }
            
            // If using upload preset (unsigned upload)
            if ($hasPreset) {
                $params['upload_preset'] = trim($this->upload_preset); // Trim whitespace
                error_log("Cloudinary: Using upload preset: " . $params['upload_preset']);
            } 
            // If no upload preset, use signed upload (requires API key and secret)
            elseif ($hasCredentials) {
                $params['api_key'] = $this->api_key;
                $params['timestamp'] = time();
                
                // Generate signature BEFORE adding file
                // Signature should include all params except 'file'
                $signature = $this->generateSignature($params);
                $params['signature'] = $signature;
            }
            
            // Prepare file for upload (add AFTER signature generation)
            $file = new CURLFile($filePath, mime_content_type($filePath), basename($filePath));
            $params['file'] = $file;
            
            // Upload via cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $uploadUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 second timeout
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            curl_close($ch);
            
            // Log request details for debugging
            error_log("Cloudinary upload request - URL: $uploadUrl, Method: " . ($hasPreset ? 'Upload Preset' : 'Signed') . ", Preset: " . ($this->upload_preset ?? 'none') . ", Cloud Name: " . $this->cloud_name);
            
            if ($error) {
                $error_msg = "Cloudinary cURL error: " . $error;
                error_log($error_msg);
                return ['error' => $error_msg];
            }
            
            if ($httpCode !== 200) {
                $errorDetails = json_decode($response, true);
                $errorMessage = 'Unknown error';
                
                if (isset($errorDetails['error']['message'])) {
                    $errorMessage = $errorDetails['error']['message'];
                } elseif (isset($errorDetails['error'])) {
                    $errorMessage = is_string($errorDetails['error']) ? $errorDetails['error'] : json_encode($errorDetails['error']);
                } elseif (!empty($response)) {
                    $errorMessage = substr($response, 0, 200);
                }
                
                $fullError = "Cloudinary upload error: HTTP $httpCode - $errorMessage";
                error_log($fullError . " | Full response: " . substr($response, 0, 1000));
                
                // Provide helpful error messages based on common issues
                if ($httpCode === 401) {
                    return ['error' => 'Authentication failed. Please check your Upload Preset name or API credentials.'];
                } elseif ($httpCode === 400) {
                    return ['error' => "Invalid request: $errorMessage. Please check your Upload Preset configuration in Cloudinary dashboard."];
                } else {
                    return ['error' => "Upload failed (HTTP $httpCode): $errorMessage"];
                }
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['secure_url'])) {
                return [
                    'url' => trim($result['secure_url']), // Trim to remove any whitespace
                    'public_id' => $result['public_id'],
                    'format' => $result['format'] ?? '',
                    'width' => $result['width'] ?? 0,
                    'height' => $result['height'] ?? 0,
                    'bytes' => $result['bytes'] ?? 0
                ];
            }
            
            $error_msg = "Cloudinary upload error: Invalid response - " . substr($response, 0, 500);
            error_log($error_msg);
            return ['error' => 'Invalid response from Cloudinary. Check error logs for details.'];
            
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
            
            $url = $baseUrl . '/' . $transformString . $publicId;
            return trim($url); // Trim to remove any trailing whitespace
        } catch (Exception $e) {
            error_log("Cloudinary URL generation error: " . $e->getMessage());
            return $publicId;
        }
    }
}
?>

