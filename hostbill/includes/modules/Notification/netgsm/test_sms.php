<?php
/**
 * NetGSM SMS Test Script
 * 
 * Bu dosyayı HostBill ana dizinine yükleyin ve tarayıcıdan çalıştırın:
 * https://panel.setcloud.com.tr/test_sms.php
 * 
 * TEST SONRASI SİLİN!
 */

// Ayarlar - KENDİ BİLGİLERİNİZİ GİRİN
$username = '8503025301';
$password = 'K3-Q36y6';  // Daha önce paylaştığınız şifre
$msgheader = '8503025301';
$appname = '8503025301';
$testPhone = '05323614494';  // Test telefon numarası (5XXXXXXXXX formatında)
$testMessage = 'Bilal bey selamlar, HostBill NetGSM test mesajı - ' . date('H:i:s');

// API URL
$apiUrl = 'https://api.netgsm.com.tr/sms/rest/v2/otp';

// Request data
$data = [
    'msgheader' => $msgheader,
    'appname' => $appname,
    'msg' => $testMessage,
    'no' => $testPhone
];

echo "<h2>NetGSM SMS Test</h2>";
echo "<p><strong>Numara:</strong> {$testPhone}</p>";
echo "<p><strong>Mesaj:</strong> {$testMessage}</p>";
echo "<hr>";

// cURL request
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($username . ':' . $password)
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";

if ($curlError) {
    echo "<p style='color:red'><strong>cURL Error:</strong> {$curlError}</p>";
} else {
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $json = json_decode($response, true);
    if ($json) {
        echo "<p><strong>Parsed:</strong></p>";
        echo "<pre>" . print_r($json, true) . "</pre>";
        
        if (isset($json['code']) && in_array($json['code'], ['00', '01', '02'])) {
            echo "<p style='color:green; font-size:20px'><strong>✓ SMS BAŞARIYLA GÖNDERİLDİ!</strong></p>";
            echo "<p><strong>Job ID:</strong> " . ($json['jobid'] ?? 'N/A') . "</p>";
        } else {
            echo "<p style='color:red; font-size:20px'><strong>✗ SMS GÖNDERİLEMEDİ</strong></p>";
            echo "<p><strong>Hata:</strong> " . ($json['description'] ?? $json['code'] ?? 'Bilinmeyen') . "</p>";
        }
    }
}

echo "<hr>";
echo "<p style='color:orange'><strong>⚠️ GÜVENLİK: Test sonrası bu dosyayı silin!</strong></p>";

