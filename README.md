# Case Mark System

Sistem manajemen scanning case mark untuk memindai dan mencocokkan box yang berisi parts dengan container yang menjadi tempat box tersebut dipindahkan.

## Features

-   ✅ Upload Excel content list
-   ✅ QR Code scanning untuk container dan box
-   ✅ Real-time tracking progress scanning
-   ✅ History scan dengan filter
-   ✅ Dashboard monitoring
-   ✅ Responsive design dengan Tailwind CSS
-   ✅ AJAX-based scanning untuk performance optimal

## Technology Stack

-   **Backend:** Laravel 9.x
-   **Frontend:** Blade Templates + Tailwind CSS
-   **Database:** MySQL 5.7+
-   **JavaScript:** jQuery + Vanilla JS
-   **Icons:** Font Awesome 6
-   **Excel Processing:** Laravel Excel (Maatwebsite)

## Installation Guide

### 1. Prerequisites

Pastikan sistem Anda memiliki:

-   PHP 8.0 atau lebih tinggi
-   Composer
-   MySQL 5.7+ atau MariaDB 10.3+
-   Node.js & NPM (opsional untuk asset compilation)

### 2. Clone Repository

```bash
git clone https://github.com/your-repo/case-mark-system.git
cd case-mark-system
```

### 3. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Laravel Excel package
composer require maatwebsite/excel

# Generate application key
php artisan key:generate
```

### 4. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE case_mark_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 5. Environment Configuration

Copy `.env.example` to `.env` dan sesuaikan konfigurasi:

```env
APP_NAME="Case Mark System"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=case_mark_system
DB_USERNAME=root
DB_PASSWORD=your_password

FILESYSTEM_DISK=local
```

### 6. Run Migrations & Seeders

```bash
# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed
```

### 7. Storage Setup

```bash
# Create storage directories
mkdir -p storage/app/public/uploads
mkdir -p public/uploads

# Create symbolic link for storage
php artisan storage:link

# Set permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 8. Start Development Server

```bash
php artisan serve
```

Buka browser dan akses: `http://localhost:8000/casemark`

## Default Login Credentials

### Admin Account

-   **Email:** admin@casemark.com
-   **Password:** password
-   **Role:** Admin (full access)

### Operator Account

-   **Email:** operator1@casemark.com
-   **Password:** password
-   **Role:** Operator (scanning only)

## File Structure

```
case-mark-system/
├── app/
│   ├── Http/Controllers/
│   │   ├── CaseMarkController.php
│   │   └── Api/CaseMarkApiController.php
│   ├── Models/
│   │   ├── CaseModel.php
│   │   ├── ContentList.php
│   │   ├── ScanHistory.php
│   │   └── User.php
│   └── Imports/
│       └── ContentListImport.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/views/
│   ├── layouts/app.blade.php
│   └── casemark/
├── routes/
│   ├── web.php
│   └── api.php
└── public/uploads/
```

## Usage Guide

### 1. Upload Content List

1. Akses menu **UPLOAD**
2. Pilih file Excel dengan format:
    ```
    box_no | part_no | part_name | quantity | remark
    BOX_01 | 32909-BZ100-00-87 | TUBE SUB-ASSY | 15 |
    BOX_02 | 32909-BZ100-00-87 | TUBE SUB-ASSY | 15 |
    ```
3. Isi informasi container (Case No, Destination, dll)
4. Klik **Upload & Process**

### 2. Scan Container

1. Akses **Scan Container**
2. Scan QR code container atau input manual
3. Konfirmasi container
4. Lanjut ke **Scan Box**

### 3. Scan Box

1. Pastikan container sudah dipilih
2. Scan QR code box dengan format: `BOX_01|32909-BZ100-00-87`
3. Sistem akan memvalidasi dengan content list
4. Box yang valid akan tercatat dalam history

### 4. Monitor Progress

-   **Content List:** Lihat daftar box dan progress scanning
-   **History:** Monitor semua aktivitas scan
-   **List Case Mark:** Overview semua container dan statusnya

## QR Code Format

### Container QR Code

```
I2E-SAN-00435
```

### Box QR Code

```
BOX_01|32909-BZ100-00-87
```

Format: `{BOX_NUMBER}|{PART_NUMBER}`

## API Endpoints

### Scan Operations

-   `POST /api/casemark/scan` - Process box scan
-   `POST /api/casemark/mark-packed` - Mark container as packed

### Information

-   `GET /api/casemark/container-info/{caseNo}` - Get container details
-   `GET /api/casemark/stats` - Get system statistics
-   `GET /api/casemark/search` - Search functionality

## Troubleshooting

### Common Issues

1. **File Upload Error**

    ```bash
    # Check PHP upload limits
    php -i | grep upload_max_filesize
    php -i | grep post_max_size

    # Increase limits in php.ini if needed
    upload_max_filesize = 10M
    post_max_size = 10M
    ```

2. **Database Connection Error**

    ```bash
    # Test database connection
    php artisan tinker
    >>> DB::connection()->getPdo();
    ```

3. **Storage Permission Error**

    ```bash
    # Fix storage permissions
    sudo chown -R www-data:www-data storage
    sudo chmod -R 775 storage
    ```

4. **Excel Import Error**
    ```bash
    # Clear config cache
    php artisan config:clear
    php artisan cache:clear
    ```

### Error Logs

Check logs untuk debugging:

```bash
tail -f storage/logs/laravel.log
```

## Production Deployment

### 1. Server Requirements

-   PHP 8.0+ dengan extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
-   Web server (Apache/Nginx)
-   MySQL 5.7+

### 2. Deployment Steps

```bash
# Set environment to production
APP_ENV=production
APP_DEBUG=false

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### 3. Security Considerations

-   Change default passwords
-   Setup SSL certificate
-   Configure firewall
-   Regular database backups
-   Update dependencies regularly

## Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/new-feature`)
3. Commit changes (`git commit -am 'Add new feature'`)
4. Push branch (`git push origin feature/new-feature`)
5. Create Pull Request

## License

This project is licensed under the MIT License.

## Support

Untuk support dan pertanyaan:

-   Email: support@casemark.com
-   Documentation: [Wiki](https://github.com/your-repo/case-mark-system/wiki)
-   Issues: [GitHub Issues](https://github.com/your-repo/case-mark-system/issues)
