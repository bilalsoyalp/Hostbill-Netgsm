<?php

/**
 * NetGSM SMS Notification Module for HostBill
 *
 * Required methods:
 * - notifyClient
 * - notifyAdmin
 *
 * @package HostBill
 * @subpackage Notification
 * @version 1.2.0
 * @author Özkan Kutuk <ozkan@edsoft.net>
 * @copyright 2026 Özkan Kutuk
 * @license MIT
 */
class netgsm extends NotificationModule
{
    protected $version = '1.2.0';
    protected $modname = 'NetGSM SMS Notifications';
    protected $description = 'NetGSM SMS Gateway ile personel ve müşterilere SMS bildirimleri gönderin.
        <br>Personel telefon numarası profil sayfasından ayarlanabilir.';
    
    /**
     * API Endpoints
     */
    private $smsApiUrl = 'https://api.netgsm.com.tr/sms/rest/v2/send';
    private $otpApiUrl = 'https://api.netgsm.com.tr/sms/rest/v2/otp';
    
    /**
     * Log table name
     */
    private $logTable = 'hb_netgsm_sms_logs';
    
    /**
     * Module configuration, visible in Settings->Modules
     * @var array
     */
    protected $configuration = [
        'Username' => [
            'value' => '',
            'type' => 'input',
            'description' => 'NetGSM abone numaranız (850XXXXXXX)'
        ],
        'Password' => [
            'value' => '',
            'type' => 'password',
            'description' => 'NetGSM API şifreniz'
        ],
        'Originator' => [
            'value' => '',
            'type' => 'input',
            'description' => 'SMS gönderici başlığı (msgheader)'
        ],
        'Appname' => [
            'value' => '',
            'type' => 'input',
            'description' => 'OTP için uygulama adı (appname). Boş bırakılırsa Originator kullanılır.'
        ],
        'Client Field' => [
            'value' => 'mobilephone',
            'type' => 'input',
            'description' => 'Müşteri telefon numarası alanı (Clients->Registration fields). Boş bırakılırsa müşterilere SMS gönderilmez.'
        ],
        'Test Phone' => [
            'value' => '',
            'type' => 'input',
            'description' => 'Bağlantı testi için SMS gönderilecek numara (5XXXXXXXXX). Boş bırakılırsa sadece credential kontrolü yapılır.'
        ]
    ];

    /**
     * Install module.
     * Add custom admin field for mobile phone number
     * Add custom client field for mobile phone number
     * Create SMS logs table
     */
    public function install()
    {
        // Admin için telefon alanı ekle
        $admin_field = [
            'name' => 'Cep Telefonu',
            'code' => 'mobilephone',
            'type' => 'input'
        ];
        $fieldsmanager = HBLoader::LoadModel('EditAdmins/AdminFields');
        $fieldsmanager->addField($admin_field);

        // Client için telefon alanı ekle
        $client_field = [
            'name' => 'Cep Telefonu',
            'code' => 'mobilephone',
            'field_type' => 'input',
            'editable' => true,
            'type' => 'All',
            'description' => 'SMS bildirimleri almak için cep telefonu numaranızı ülke kodu ile birlikte girin. Örn: +905551234567'
        ];
        $clientfieldsmanager = HBLoader::LoadModel('Clients');
        $clientfieldsmanager->addCustomField($client_field);
        
        // SMS logs tablosu oluştur
        $this->_createLogTable();
    }
    
    /**
     * Get database connection
     * @return PDO|null
     */
    private function _getDb()
    {
        // Try parent class db property first
        if (isset($this->db) && $this->db instanceof PDO) {
            return $this->db;
        }
        
        // Try HBRegistry
        if (class_exists('HBRegistry')) {
            try {
                $registry = HBRegistry::singleton();
                if (method_exists($registry, 'getDb')) {
                    return $registry->getDb();
                }
            } catch (Exception $e) {}
        }
        
        // Try global $db
        global $db;
        if (isset($db) && $db instanceof PDO) {
            return $db;
        }
        
        return null;
    }
    
    /**
     * Create SMS logs table if not exists
     */
    private function _createLogTable()
    {
        try {
            $db = $this->_getDb();
            if (!$db) {
                return false;
            }
            
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->logTable}` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `date` DATETIME NOT NULL,
                `type` ENUM('admin','client','test') NOT NULL DEFAULT 'client',
                `recipient_id` INT(11) DEFAULT NULL,
                `phone` VARCHAR(20) NOT NULL,
                `message` TEXT NOT NULL,
                `status` ENUM('success','failed') NOT NULL,
                `api_code` VARCHAR(10) DEFAULT NULL,
                `api_response` VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `date` (`date`),
                KEY `status` (`status`),
                KEY `type` (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $db->query($sql);
            return true;
        } catch (Exception $e) {
            // Tablo zaten var veya başka bir hata
            return false;
        }
    }

    /**
     * Send notification to admin.
     * HostBill will automatically execute this function if admin needs
     * to be notified and is allowed to be notified about something
     *
     * @param integer $admin_id Administrator ID to notify (see hb_admin_* tables)
     * @param string $subject Subject (for sms it may be omitted)
     * @param string $message Message to send
     * @return bool
     */
    public function notifyAdmin($admin_id, $subject, $message)
    {
        // Admin bilgilerini al
        $editadmins = HBLoader::LoadModel('EditAdmins');
        $admin = $editadmins->getAdminDetails($admin_id);

        if (!$admin) {
            $this->addError('Admin bulunamadı');
            $this->_logSms('admin', $admin_id, 'N/A', $message, false, 'ERR', 'Admin bulunamadı');
            return false;
        }
        
        if (empty($admin['mobilephone'])) {
            $this->addError('Admin telefon numarası tanımlı değil');
            $this->_logSms('admin', $admin_id, 'N/A', $message, false, 'ERR', 'Telefon numarası yok');
            return false;
        }

        return $this->_send($admin['mobilephone'], $message, 'admin', $admin_id);
    }

    /**
     * Send notification to client.
     * HostBill will automatically execute this function if client needs
     * to be notified and is allowed to be notified about something
     *
     * @param integer $client_id Client ID to notify (see hb_client_* tables)
     * @param string $subject Subject (for sms it may be omitted)
     * @param string $message Message to send
     * @return bool
     */
    public function notifyClient($client_id, $subject, $message)
    {
        $mobile_phone_field = isset($this->configuration['Client Field']['value']) ? $this->configuration['Client Field']['value'] : '';

        if (empty($mobile_phone_field)) {
            // Client field yapılandırılmamış, müşterilere SMS gönderme
            $this->_logSms('client', $client_id, 'N/A', $message, false, 'ERR', 'Client Field yapılandırılmamış');
            return false;
        }

        // Client bilgilerini al
        $clients = HBLoader::LoadModel('Clients');
        $client_details = $clients->getClient($client_id);

        if (!$client_details) {
            $this->addError('Müşteri bulunamadı');
            $this->_logSms('client', $client_id, 'N/A', $message, false, 'ERR', 'Müşteri bulunamadı');
            return false;
        }
        
        if (empty($client_details[$mobile_phone_field])) {
            $this->addError('Müşteri telefon numarası tanımlı değil');
            $this->_logSms('client', $client_id, 'N/A', $message, false, 'ERR', 'Telefon numarası yok');
            return false;
        }

        return $this->_send($client_details[$mobile_phone_field], $message, 'client', $client_id);
    }

    /**
     * Test connection - validates API credentials
     * @return bool
     */
    public function testConnection()
    {
        $username = $this->configuration['Username']['value'];
        $password = $this->configuration['Password']['value'];
        $msgheader = $this->configuration['Originator']['value'];
        $appname = isset($this->configuration['Appname']['value']) && $this->configuration['Appname']['value'] !== '' 
            ? $this->configuration['Appname']['value'] 
            : $msgheader;
        $testPhone = isset($this->configuration['Test Phone']['value']) ? $this->configuration['Test Phone']['value'] : '';
        
        if (empty($username) || empty($password)) {
            $this->addError('Kullanıcı adı ve şifre gerekli');
            return false;
        }
        
        if (empty($msgheader)) {
            $this->addError('SMS başlığı (Originator) gerekli');
            return false;
        }
        
        // Test telefon numarası girilmişse gerçek SMS gönder
        if (!empty($testPhone)) {
            $testPhone = $this->_normalizePhone($testPhone);
            if (!$testPhone) {
                $this->addError('Geçersiz test telefon numarası');
                return false;
            }
            
            $data = [
                'msgheader' => $msgheader,
                'appname' => $appname,
                'msg' => 'HostBill NetGSM baglanti testi basarili!',
                'no' => $testPhone
            ];
            
            $result = $this->_callApi($this->otpApiUrl, $data, $username, $password);
            
            // Test SMS'i logla
            $this->_logSms('test', null, $testPhone, 'HostBill NetGSM baglanti testi basarili!', $result['success'], $result['code'], $result['message']);
            
            if ($result['success']) {
                return true;
            }
            
            $this->addError($result['message']);
            return false;
        }
        
        // Test numarası yoksa sahte numara ile credential kontrolü yap
        $data = [
            'msgheader' => $msgheader,
            'appname' => $appname,
            'msg' => 'test',
            'no' => '5000000000'
        ];
        
        $result = $this->_callApi($this->otpApiUrl, $data, $username, $password);
        
        if ($result['success']) {
            return true;
        }
        
        // Bu hatalar credentials'ın doğru olduğunu gösterir (numara/mesaj hatası)
        // 50: Operator kodu belirlenemedi (sahte numara için normal)
        // 70: Hatalı numara
        // 80: Limit aşıldı
        // 85: Mesaj boş
        if (in_array($result['code'], ['50', '70', '80', '85'])) {
            return true;
        }
        
        if (strpos($result['message'], 'Operator') !== false) {
            return true;
        }
        
        if (strpos($result['message'], 'belirlenemedi') !== false) {
            return true;
        }
        
        $this->addError($result['message']);
        return false;
    }

    /**
     * Helper function to send actual SMS message via NetGSM API
     * 
     * @param string $number Phone number
     * @param string $message SMS message to send
     * @param string $type Recipient type: admin, client, test
     * @param int|null $recipientId Admin or Client ID
     * @return bool
     */
    private function _send($number, $message, $type = 'client', $recipientId = null)
    {
        $username = $this->configuration['Username']['value'];
        $password = $this->configuration['Password']['value'];
        $msgheader = $this->configuration['Originator']['value'];
        $appname = isset($this->configuration['Appname']['value']) && $this->configuration['Appname']['value'] !== '' 
            ? $this->configuration['Appname']['value'] 
            : $msgheader;
        
        if (empty($username) || empty($password) || empty($msgheader)) {
            $this->addError('NetGSM yapılandırması eksik');
            $this->_logSms($type, $recipientId, $number, $message, false, 'ERR', 'Yapılandırma eksik');
            return false;
        }
        
        $originalNumber = $number;
        $number = $this->_normalizePhone($number);
        if (!$number) {
            $this->addError('Geçersiz telefon numarası');
            $this->_logSms($type, $recipientId, $originalNumber, $message, false, 'ERR', 'Geçersiz telefon formatı');
            return false;
        }
        
        // OTP API'de Türkçe karakter desteklenmez
        $message = $this->_removeTurkishChars($message);
        
        $data = [
            'msgheader' => $msgheader,
            'appname' => $appname,
            'msg' => $message,
            'no' => $number
        ];
        
        $result = $this->_callApi($this->otpApiUrl, $data, $username, $password);
        
        // SMS'i logla
        $this->_logSms($type, $recipientId, $number, $message, $result['success'], $result['code'], $result['message']);
        
        if ($result['success']) {
            return true;
        }
        
        $this->addError($result['message']);
        return false;
    }

    /**
     * Make API call to NetGSM
     * 
     * @param string $url API endpoint
     * @param array $data Request data
     * @param string $username NetGSM username
     * @param string $password NetGSM password
     * @return array
     */
    private function _callApi($url, $data, $username, $password)
    {
        $ch = curl_init($url);
        
        $chOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($username . ':' . $password)
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ];
        
        curl_setopt_array($ch, $chOptions);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            return ['success' => false, 'message' => 'cURL hatası: ' . $curlError, 'code' => 'CURL'];
        }
        
        $json = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'JSON parse hatası', 'code' => 'JSON'];
        }
        
        $code = isset($json['code']) ? $json['code'] : 'UNKNOWN';
        $description = isset($json['description']) ? $json['description'] : '';
        
        // Başarılı kodlar: 00, 01, 02
        if (in_array($code, ['00', '01', '02'])) {
            return ['success' => true, 'code' => $code, 'message' => 'Başarılı'];
        }
        
        return ['success' => false, 'code' => $code, 'message' => $this->_getErrorMessage($code, $description)];
    }

    /**
     * Normalize phone number to NetGSM format
     * 
     * @param string $phone Phone number
     * @return string|false
     */
    private function _normalizePhone($phone)
    {
        if (empty($phone)) {
            return false;
        }
        
        // Sadece rakamları ve + işaretini tut
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // +90 ile başlıyorsa
        if (preg_match('/^\+?90(5\d{9})$/', $phone, $m)) {
            return $m[1];
        }
        // 0 ile başlıyorsa
        if (preg_match('/^0(5\d{9})$/', $phone, $m)) {
            return $m[1];
        }
        // 5 ile başlıyorsa (10 haneli)
        if (preg_match('/^(5\d{9})$/', $phone, $m)) {
            return $m[1];
        }
        
        return false;
    }

    /**
     * Remove Turkish characters (OTP API doesn't support them)
     * 
     * @param string $text Text to convert
     * @return string
     */
    private function _removeTurkishChars($text)
    {
        $turkish = ['ğ', 'ü', 'ş', 'ı', 'ö', 'ç', 'Ğ', 'Ü', 'Ş', 'İ', 'Ö', 'Ç'];
        $ascii = ['g', 'u', 's', 'i', 'o', 'c', 'G', 'U', 'S', 'I', 'O', 'C'];
        
        return str_replace($turkish, $ascii, $text);
    }

    /**
     * Get human readable error message for NetGSM error codes
     * 
     * @param string $code Error code
     * @param string $description API description
     * @return string
     */
    private function _getErrorMessage($code, $description = '')
    {
        $errors = [
            '20' => 'JSON formatı hatalı',
            '30' => 'Geçersiz kullanıcı adı veya şifre',
            '40' => 'Mesaj başlığı sistemde tanımlı değil',
            '50' => 'Yetersiz bakiye',
            '51' => 'Hesap aktif değil',
            '70' => 'Hatalı telefon numarası',
            '80' => 'Gönderim limiti aşıldı',
            '85' => 'Mesaj içeriği boş'
        ];
        
        if (!empty($description)) {
            return $description;
        }
        
        return isset($errors[$code]) ? $errors[$code] : 'Bilinmeyen hata: ' . $code;
    }
    
    /**
     * Log SMS activity to database
     * 
     * @param string $type Type: admin, client, test
     * @param int|null $recipientId Admin or Client ID
     * @param string $phone Phone number
     * @param string $message SMS message
     * @param bool $success Whether SMS was sent successfully
     * @param string $apiCode API response code
     * @param string $apiResponse API response message
     */
    private function _logSms($type, $recipientId, $phone, $message, $success, $apiCode = '', $apiResponse = '')
    {
        $maskedPhone = $this->_maskPhone($phone);
        $status = $success ? 'SUCCESS' : 'FAILED';
        $truncatedMsg = mb_strlen($message) > 50 ? mb_substr($message, 0, 50) . '...' : $message;
        
        // 1. HostBill System Log'a yaz (Main Log file'da görünür)
        $logMessage = sprintf(
            '[NetGSM SMS] [%s] Type: %s | Phone: %s | Code: %s | Message: %s',
            $status,
            $type,
            $maskedPhone,
            $apiCode,
            $truncatedMsg
        );
        
        // HostBill'in GeneralLog sınıfını kullan
        if (class_exists('GeneralLog')) {
            try {
                GeneralLog::add($logMessage, 'sms');
            } catch (Exception $e) {}
        }
        
        // 2. Kendi tablomuza da yaz (detaylı kayıt için)
        try {
            $db = $this->_getDb();
            if (!$db) {
                return;
            }
            
            // Önce tablo var mı kontrol et, yoksa oluştur
            $this->_createLogTable();
            
            $stmt = $db->prepare("INSERT INTO `{$this->logTable}` 
                (`date`, `type`, `recipient_id`, `phone`, `message`, `status`, `api_code`, `api_response`) 
                VALUES (NOW(), :type, :recipient_id, :phone, :message, :status, :api_code, :api_response)");
            
            $stmt->execute([
                'type' => $type,
                'recipient_id' => $recipientId,
                'phone' => $maskedPhone,
                'message' => mb_substr($message, 0, 500),
                'status' => $success ? 'success' : 'failed',
                'api_code' => $apiCode,
                'api_response' => mb_substr($apiResponse, 0, 255)
            ]);
        } catch (Exception $e) {
            // Log hatası sessizce geç
        }
    }
    
    /**
     * Mask phone number for privacy
     * Example: 5551234567 -> 555***4567
     * 
     * @param string $phone Phone number
     * @return string Masked phone number
     */
    private function _maskPhone($phone)
    {
        if (strlen($phone) < 7) {
            return $phone;
        }
        return substr($phone, 0, 3) . '***' . substr($phone, -4);
    }
    
    /**
     * Get SMS logs for admin panel
     * 
     * @param int $limit Number of logs to return
     * @param int $offset Offset for pagination
     * @return array
     */
    public function getSmsLogs($limit = 50, $offset = 0)
    {
        try {
            $db = $this->_getDb();
            if (!$db) {
                return [];
            }
            
            $stmt = $db->prepare("SELECT * FROM `{$this->logTable}` ORDER BY `date` DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get SMS logs count
     * 
     * @return int
     */
    public function getSmsLogsCount()
    {
        try {
            $db = $this->_getDb();
            if (!$db) {
                return 0;
            }
            
            $stmt = $db->query("SELECT COUNT(*) FROM `{$this->logTable}`");
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
}
