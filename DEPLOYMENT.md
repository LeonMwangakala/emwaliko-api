# Deployment Guide - QR Code Generation System

This guide provides detailed instructions for deploying the Kadirafiki API with QR code generation capabilities.

## Critical Dependencies

The QR code generation system requires **ImageMagick** and the **PHP imagick extension** to function properly. Without these, QR codes will not be generated.

## Pre-Deployment Checklist

### System Requirements
- [ ] PHP 8.4+ installed
- [ ] ImageMagick library installed
- [ ] PHP imagick extension installed and enabled
- [ ] Web server (Apache/Nginx) configured
- [ ] Database server (MySQL/PostgreSQL) running
- [ ] Storage directory with write permissions

### QR Code Specific Requirements
- [ ] ImageMagick version 6.5.3+ or 7.0+
- [ ] PHP imagick extension version 3.0+
- [ ] Storage directory: `storage/app/public/qr_codes/` (will be created automatically)
- [ ] Public storage link created: `php artisan storage:link`

## Installation by Operating System

### Ubuntu 20.04/22.04

```bash
# Update package list
sudo apt-get update

# Install ImageMagick and development libraries
sudo apt-get install imagemagick libmagickwand-dev

# Install PHP imagick extension
sudo apt-get install php-imagick

# Verify installation
php -m | grep imagick
convert --version

# Restart web server
sudo systemctl restart apache2  # or nginx
sudo systemctl restart php8.4-fpm  # adjust version as needed
```

### CentOS 7/8/9

```bash
# Install ImageMagick
sudo yum install ImageMagick ImageMagick-devel

# Install PHP imagick extension
sudo yum install php-imagick

# Verify installation
php -m | grep imagick
convert --version

# Restart web server
sudo systemctl restart httpd  # or nginx
sudo systemctl restart php-fpm
```

### Amazon Linux 2

```bash
# Install ImageMagick
sudo yum install ImageMagick ImageMagick-devel

# Install PHP imagick extension
sudo yum install php-imagick

# Verify installation
php -m | grep imagick
convert --version

# Restart web server
sudo systemctl restart httpd
sudo systemctl restart php-fpm
```

### Docker Deployment

Add to your `Dockerfile`:

```dockerfile
# Install ImageMagick and PHP imagick extension
RUN apt-get update && apt-get install -y \
    imagemagick \
    libmagickwand-dev \
    php-imagick \
    && rm -rf /var/lib/apt/lists/*

# Verify installation
RUN php -m | grep imagick
RUN convert --version
```

### cPanel/Shared Hosting

1. **Contact your hosting provider** to ensure ImageMagick is installed
2. **Enable PHP imagick extension** through cPanel's PHP Selector
3. **Verify installation** by creating a PHP info file:
   ```php
   <?php phpinfo(); ?>
   ```
   Look for "imagick" in the loaded extensions.

## Verification Steps

### 1. Check ImageMagick Installation
```bash
convert --version
```
Expected output:
```
Version: ImageMagick 7.1.2-0 Q16 HDRI x86_64 2023-01-01 https://imagemagick.org
```

### 2. Check PHP imagick Extension
```bash
php -m | grep imagick
```
Expected output:
```
imagick
```

### 3. Test QR Code Generation
```bash
# Create a test guest and generate QR code
php artisan tinker --execute="
\$guest = App\Models\Guest::create([
    'event_id' => 1,
    'name' => 'Test Guest',
    'phone_number' => '+1234567890',
    'card_class_id' => 1
]);
echo 'QR Code generated: ' . \$guest->qr_code_path;
"
```

### 4. Check File Permissions
```bash
# Ensure storage directory has proper permissions
ls -la storage/app/public/
chmod -R 775 storage/
chown -R www-data:www-data storage/  # Adjust user as needed
```

## Common Issues and Solutions

### Issue: "You need to install the imagick extension"
**Solution**: Install the PHP imagick extension as shown above.

### Issue: "pkg-config not found"
**Solution**: Install pkg-config:
```bash
# Ubuntu/Debian
sudo apt-get install pkg-config

# CentOS/RHEL
sudo yum install pkgconfig
```

### Issue: QR codes not generating
**Solution**: Check the following:
1. ImageMagick is installed: `convert --version`
2. PHP imagick extension is loaded: `php -m | grep imagick`
3. Storage directory has write permissions
4. Public storage link exists: `php artisan storage:link`

### Issue: QR code files not accessible via URL
**Solution**: 
1. Ensure public storage link exists: `php artisan storage:link`
2. Check web server configuration allows access to `/storage/` directory
3. Verify file permissions on QR code files

### Issue: Permission denied errors
**Solution**: Set proper permissions:
```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/  # Adjust user as needed
```

## Production Configuration

### Environment Variables
Ensure these are set in your `.env` file:
```env
APP_URL=https://yourdomain.com
APP_ENV=production
FILESYSTEM_DISK=public
```

### Web Server Configuration

#### Apache
Ensure your `.htaccess` file allows access to storage:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

#### Nginx
Add to your nginx configuration:
```nginx
location /storage {
    alias /path/to/your/project/storage/app/public;
    try_files $uri $uri/ =404;
}
```

### Security Considerations
1. **File Access**: QR codes are publicly accessible by design
2. **Storage Cleanup**: Implement regular cleanup of unused QR codes
3. **Backup**: Include QR code files in your backup strategy

## Monitoring and Maintenance

### Health Check Script
Create a script to verify QR code generation is working:
```bash
#!/bin/bash
# health_check.sh

echo "Checking QR code generation system..."

# Check ImageMagick
if ! command -v convert &> /dev/null; then
    echo "ERROR: ImageMagick not found"
    exit 1
fi

# Check PHP imagick extension
if ! php -m | grep -q imagick; then
    echo "ERROR: PHP imagick extension not loaded"
    exit 1
fi

# Test QR code generation
php artisan tinker --execute="
try {
    \$guest = App\Models\Guest::first();
    if (\$guest) {
        \$guest->generateQrCode();
        echo 'SUCCESS: QR code generation working';
    } else {
        echo 'WARNING: No guests found for testing';
    }
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
    exit(1);
}
"

echo "Health check completed"
```

### Regular Maintenance
1. **Monitor storage usage**: QR codes accumulate over time
2. **Clean up orphaned files**: Remove QR codes for deleted guests
3. **Update ImageMagick**: Keep ImageMagick updated for security

## Troubleshooting Commands

```bash
# Check system status
php -v
php -m | grep imagick
convert --version

# Check storage
ls -la storage/app/public/qr_codes/
du -sh storage/app/public/qr_codes/

# Count guests without QR codes
php artisan tinker --execute="echo App\Models\Guest::whereNull('qr_code_path')->count();"

# Generate missing QR codes
php artisan tinker --execute="echo App\\Models\\Guest::generateMissingQrCodes();"
```

## Support

If you encounter issues with QR code generation:

1. Check this deployment guide
2. Verify all dependencies are installed
3. Check the Laravel logs: `tail -f storage/logs/laravel.log`
4. Test with the verification commands above
5. Contact your hosting provider if using shared hosting 