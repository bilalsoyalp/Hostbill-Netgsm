# NetGSM HostBill SMS Entegrasyonu

HostBill için NetGSM SMS Notification modülü ve SMS Log görüntüleme plugini.

## 🚀 Özellikler

- ✅ **SMS Notification Modülü**: HostBill Notification modül yapısına tam uyumlu
- ✅ **OTP SMS Desteği**: NetGSM REST v2 OTP endpoint entegrasyonu
- ✅ **Telefon Normalizasyonu**: Tüm Türk telefon formatlarını destekler
- ✅ **Türkçe Karakter Desteği**: Unicode SMS veya karakter dönüşümü
- ✅ **Basic Authentication**: REST v2 API ile güvenli iletişim
- ✅ **SMS Loglama**: Tüm SMS gönderimlerinin loglanması
- ✅ **Admin Panel**: "Gönderilen SMS'ler" sayfası ile log görüntüleme
- ✅ **İstatistikler**: Toplam, başarılı, başarısız SMS sayıları
- ✅ **Filtreleme**: Tip, durum, tarih ve telefon numarasına göre filtreleme
- ✅ **Pagination**: Sayfalama desteği
- ✅ **Log Temizleme**: Eski logları silme özelliği

## 📋 Gereksinimler

- **PHP**: 7.4 veya üzeri
- **HostBill**: 2025+ (Notification modül yapısı)
- **cURL**: PHP cURL extension
- **JSON**: PHP JSON extension
- **NetGSM Hesabı**: Aktif NetGSM SMS hesabı gerekli

## 📦 Kurulum

### 1. Dosyaları Kopyalama

**FileZilla veya SSH ile:**

Tüm `includes/` klasörünü HostBill dizinine kopyalayın:

```bash
# HostBill ana dizinine gidin
cd /path/to/hostbill

# Tüm modülleri kopyalayın
cp -r includes/* /path/to/hostbill/includes/

# Veya tek tek:
# NetGSM Notification modülü
cp -r includes/modules/Notification/netgsm includes/modules/Notification/

# SMS Logs plugini
cp -r includes/modules/Other/netgsm_smslogs includes/modules/Other/

# Logs menüsü hook'u
cp includes/extend/hooks/before_displayadminheader_99.php includes/extend/hooks/

# Dosya izinlerini ayarlayın
chmod -R 755 includes/modules/Notification/netgsm
chmod -R 755 includes/modules/Other/netgsm_smslogs
chmod 644 includes/extend/hooks/before_displayadminheader_99.php
```

### 2. Notification Modülü Aktivasyonu

1. HostBill Admin Panel → **Settings** → **Notifications** → **SMS Gateways**
2. **NetGSM** seçin ve **Enable** tıklayın
3. Konfigürasyon bilgilerini girin:
   - **Username**: NetGSM abone numaranız (örn: `8503025301`)
   - **Password**: NetGSM API şifreniz
   - **Originator**: Gönderici başlığınız (max 11 karakter)
   - **Appname**: OTP için uygulama adı (opsiyonel)
   - **Client Field**: Müşteri telefon alanı (varsayılan: `mobilephone`)
   - **Test Phone**: Test SMS göndermek için numara (opsiyonel)
4. **Save** tıklayın

### 3. SMS Logs Pluginini Aktivasyonu

1. HostBill Admin Panel → **Settings** → **Modules** → **Other**
2. **Gönderilen SMS'ler** pluginini bulun ve **Activate** tıklayın
3. Plugin artık **Extras** menüsünde ve **Logs** menüsünün altında görünecektir

### 4. Hook Dosyasını Yükleme (Logs Menüsü İçin)

Hook dosyası, "Gönderilen SMS'ler" linkini Logs menüsüne ekler:

```bash
cp -r includes/extend/hooks/* /path/to/hostbill/includes/extend/hooks/
```

### 4. Bağlantı Testi

1. Modül ayarları sayfasında **Test Connection** butonuna tıklayın
2. "Bağlantı başarılı" mesajını görmelisiniz

## 📊 SMS Logları Görüntüleme

Admin panelde **Extras** → **Gönderilen SMS'ler** menüsüne gidin.

### Özellikler:

- **İstatistikler**: Toplam, başarılı, başarısız ve bugünkü SMS sayıları
- **Filtreleme**: 
  - Tip: Admin, Müşteri, Test
  - Durum: Başarılı, Başarısız
  - Tarih aralığı
  - Telefon numarası
- **Log Detayları**:
  - Tarih/saat
  - Alıcı tipi ve ID
  - Telefon numarası (maskelenmiş)
  - Mesaj içeriği
  - API yanıt kodu ve mesajı
- **Eski Log Temizleme**: 30, 60, 90, 180 gün veya 1 yıldan eski logları silme

## 🧪 Test

### API Test (Komut Satırı)

```bash
cd tests/

# Environment variables ile test
NETGSM_USERNAME="ABONE_NO" \
NETGSM_PASSWORD="SIFRE" \
NETGSM_HEADER="BASLIK" \
NETGSM_TEST_PHONE="905XXXXXXXXX" \
php test_netgsm_client.php
```

Beklenen çıktı:
```
Test 1: Basit SMS Gönderimi
--------------------------------------------
Sonuç: ✓ BAŞARILI
Job ID: 176243759XXXXX9453710538549
Kod: 00
Mesaj: success
```

### HostBill'den Test

1. **Admin Panel** → **Settings** → **Notifications** → **SMS Gateways** → **NetGSM**
2. **Test Connection** butonuna tıklayın
3. "Bağlantı başarılı" mesajını görmelisiniz

## 📖 Kullanım

### HostBill Notification Sistemi

NetGSM modülü HostBill'in standart Notification sistemine entegre olur. 
SMS gönderimleri HostBill'in bildirim sistemi üzerinden otomatik olarak yapılır.

### Manuel SMS Gönderimi (PHP Kodu)

```php
// HostBill içinden
$sms = new netgsm();
$result = $sms->sendSMS('905XXXXXXXXX', 'Test mesajı');

if ($result['success']) {
    echo "SMS gönderildi! Job ID: " . $result['jobid'];
} else {
    echo "Hata: " . $result['message'];
}
```

### OTP SMS Gönderimi

```php
$sms = new netgsm();

// Serbest format mesaj
$result = $sms->sendOtpSMS('905XXXXXXXXX', 'Siparisınız onaylandı. #12345');

// Veya sadece OTP kodu
$result = $sms->sendOtpCode('905XXXXXXXXX', '1234');
```

## 🔧 Konfigürasyon

### Notification Modül Ayarları

| Alan | Açıklama | Zorunlu |
|------|----------|---------|
| **Username** | NetGSM abone numarası | ✅ |
| **Password** | NetGSM API şifresi | ✅ |
| **Originator** | Gönderici başlığı (max 11 karakter) | ✅ |
| **Appname** | OTP uygulama adı | ❌ |
| **Client Field** | Müşteri telefon alanı | ❌ |
| **Test Phone** | Test SMS numarası | ❌ |

## 📁 Dosya Yapısı

```
hostbill_netgsm/
├── includes/
│   ├── extend/
│   │   └── hooks/
│   │       └── before_displayadminheader_99.php  # Logs menüsüne link ekler
│   └── modules/
│       ├── Notification/
│       │   └── netgsm/
│       │       └── class.netgsm.php              # Ana notification modülü
│       └── Other/
│           └── netgsm_smslogs/                   # SMS Logs plugini
│               ├── class.netgsm_smslogs.php
│               └── admin/
│                   ├── class.netgsm_smslogs_controller.php
│                   └── default.tpl
├── README.md
└── test_sms.php
```

## 🔒 Güvenlik

- ✅ **SSL/TLS**: Tüm API istekleri HTTPS üzerinden yapılır
- ✅ **Basic Authentication**: Credentials güvenli şekilde iletilir
- ✅ **Input Validation**: Telefon numarası ve mesaj içeriği doğrulanır
- ✅ **Telefon Maskeleme**: Loglarda telefon numaraları maskelenir (555***4567)

## 🐛 Sorun Giderme

### Hata Kodları

| Kod | Açıklama | Çözüm |
|-----|----------|-------|
| 20 | JSON/XML hatalı | İstek formatını kontrol edin |
| 30 | Geçersiz kullanıcı/şifre | Credentials'ı kontrol edin |
| 40 | Başlık tanımsız | NetGSM panelinden başlık tanımlayın |
| 50 | Bakiye yok / Operator belirlenemedi | Hesabınıza kredi yükleyin veya numarayı kontrol edin |
| 51 | Hesap aktif değil | NetGSM ile iletişime geçin |
| 70 | Hatalı numara formatı | Telefon numarasını kontrol edin |
| 80 | Gönderim limitine ulaşıldı | Bekleyin veya limit artırın |
| 85 | Mesaj metni boş | Mesaj içeriği girin |

### Log Tablosu Görünmüyor

1. Notification modülünün aktif olduğundan emin olun
2. En az bir SMS gönderimi yapın (test dahil)
3. `hb_netgsm_sms_logs` tablosunun veritabanında oluştuğunu kontrol edin

### SMS Logs Plugini Görünmüyor

1. **Settings** → **Modules** → **Other** bölümünde pluginin aktif olduğunu kontrol edin
2. Dosyaların doğru dizine kopyalandığından emin olun
3. Dosya izinlerini kontrol edin (755)

## 📄 Lisans

Bu proje özel bir proje olup, ticari kullanım için izin gereklidir.

## 📞 Destek

- **NetGSM Destek**: https://www.netgsm.com.tr/destek
- **HostBill Destek**: https://hostbillapp.com/support

---

**Version**: 1.2.0  
**Last Updated**: 2026-01-09  
**Author**: HostBill NetGSM Integration Team
