# Çalışan Talep Yönetim Sistemi

Modern ve kullanıcı dostu bir web tabanlı çalışan talep yönetim sistemi. Şirket içi çalışanların izin, avans, fazla mesai, ekipman ve özel taleplerini dijital ortamda iletebilmesi ve yöneticilerin bu talepleri onaylayabilmesi için tasarlanmıştır.

## 🚀 Özellikler

### 👥 Kullanıcı Rolleri
- **Çalışan**: Talep oluşturma, takip etme
- **Yönetici**: Talep onaylama/reddetme, ekip yönetimi
- **Admin**: Sistem yönetimi, tüm talepleri görüntüleme

### 📋 Talep Türleri
- **İzin**: Yıllık izin, hastalık izni, mazeret izni
- **Avans**: Maaş avansı talepleri
- **Fazla Mesai**: Fazla mesai ücreti talepleri
- **Ekipman**: Ofis ekipmanı, bilgisayar, telefon talepleri
- **Özel Talep**: Diğer özel durumlar

### 🔧 Sistem Özellikleri
- **Responsive Design**: Mobil uyumlu arayüz
- **Güvenli Kimlik Doğrulama**: Session tabanlı güvenli giriş
- **Bildirim Sistemi**: Anlık bildirimler
- **Onay Süreci**: Hiyerarşik onay sistemi
- **Filtreleme**: Gelişmiş filtreleme seçenekleri
- **İstatistikler**: Detaylı raporlama

## 🛠️ Teknoloji Stack

- **Backend**: PHP 8.x
- **Veritabanı**: MySQL 8.0+
- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Icons**: Font Awesome 6.0
- **Charts**: Chart.js

## 📋 Sistem Gereksinimleri

- PHP 8.0 veya üzeri
- MySQL 8.0 veya üzeri
- Web sunucusu (Apache/Nginx)
- PDO PHP extension
- mbstring PHP extension

## 🔧 Kurulum

### 1. Projeyi İndirin
```bash
git clone https://github.com/username/employee-request-system.git
cd employee-request-system
```

### 2. Veritabanı Kurulumu
```bash
# MySQL'e giriş yapın
mysql -u root -p

# Veritabanını oluşturun
source sql/schema.sql
```

### 3. Yapılandırma
`config/database.php` dosyasını düzenleyin:
```php
private $host = 'localhost';
private $dbname = 'employee_requests_db';
private $username = 'your_db_username';
private $password = 'your_db_password';
```

### 4. Dosya İzinleri
```bash
chmod 755 uploads/
chmod 755 public/
```

### 5. Web Sunucusu Yapılandırması

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 🎯 Kullanım

### İlk Giriş
Sistem kurulduktan sonra aşağıdaki admin hesabı ile giriş yapabilirsiniz:

```
E-posta: admin@company.com
Şifre: admin123
```

### Temel İşlemler

1. **Talep Oluşturma**: Dashboard > Yeni Talep
2. **Talep Takibi**: Dashboard > Taleplerim
3. **Onay İşlemleri**: Dashboard > Onaylar (Yönetici)
4. **Raporlar**: Dashboard > Raporlar

## 📁 Proje Yapısı

```
employee-request-system/
├── config/
│   ├── app.php              # Uygulama yapılandırması
│   └── database.php         # Veritabanı bağlantısı
├── src/
│   └── models/
│       ├── User.php         # Kullanıcı modeli
│       ├── Request.php      # Talep modeli
│       └── Notification.php # Bildirim modeli
├── public/
│   ├── index.php           # Giriş sayfası
│   ├── dashboard.php       # Ana dashboard
│   ├── request-form.php    # Talep formu
│   ├── my-requests.php     # Kullanıcı talepleri
│   └── logout.php          # Çıkış
├── sql/
│   └── schema.sql          # Veritabanı şeması
├── uploads/                # Dosya yüklemeleri
└── README.md              # Proje dokümantasyonu
```

## 🔒 Güvenlik Özellikleri

- **CSRF Koruması**: Formlar CSRF token ile korunur
- **SQL Injection Koruması**: Hazırlanmış sorgu (prepared statements)
- **XSS Koruması**: Kullanıcı girişleri sanitize edilir
- **Şifre Güvenliği**: Şifreler bcrypt ile hashlenir
- **Session Güvenliği**: Güvenli session yönetimi

## 🎨 Özelleştirme

### Tema Renkleri
`tailwind.config.js` dosyasını düzenleyerek tema renklerini değiştirebilirsiniz.

### Talep Türleri
Yeni talep türleri eklemek için:
1. `request_types` tablosuna yeni tür ekleyin
2. `request-form.php` dosyasında gerekli alanları güncelleyin

### E-posta Bildirimları
`config/app.php` dosyasında SMTP ayarlarını yapılandırın.

## 🐛 Sorun Giderme

### Ortak Sorunlar

1. **Veritabanı Bağlantı Hatası**
   - Veritabanı bilgilerini kontrol edin
   - MySQL servisinin çalıştığından emin olun

2. **Dosya Yükleme Hatası**
   - `uploads/` klasörünün yazılabilir olduğunu kontrol edin
   - PHP `upload_max_filesize` ayarını kontrol edin

3. **Session Hatası**
   - Session klasörünün yazılabilir olduğunu kontrol edin
   - PHP session ayarlarını kontrol edin

## 📊 Performans Optimizasyonu

- Veritabanı indekslerini optimize edin
- Statik dosyalar için cache kullanın
- Büyük dosyalar için CDN kullanın

## 🔄 Güncelleme

```bash
git pull origin main
# Veritabanı değişikliklerini uygulayın
mysql -u root -p < sql/updates.sql
```

## 📝 Katkıda Bulunma

1. Fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit edin (`git commit -m 'Add amazing feature'`)
4. Push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için `LICENSE` dosyasına bakın.

## 📞 Destek

Herhangi bir sorun veya öneriniz için:
- GitHub Issues: [github.com/username/employee-request-system/issues](https://github.com/username/employee-request-system/issues)
- E-posta: support@company.com

## 📈 Gelecek Özellikler

- [ ] E-posta bildirimleri
- [ ] Dosya eki desteği
- [ ] Gelişmiş raporlama
- [ ] API entegrasyonu
- [ ] Mobil uygulama
- [ ] Multi-tenant support
- [ ] Slack/Teams entegrasyonu

## 🏆 Teşekkürler

Bu projeye katkıda bulunan herkese teşekkürler:
- UI/UX tasarımı için Tailwind CSS
- İkonlar için Font Awesome
- Grafikler için Chart.js

---

**Çalışan Talep Yönetim Sistemi** - Modern, güvenli ve kullanıcı dostu talep yönetimi çözümü.
