# Intervention Image Package Setup Guide

## Overview

Intervention Image is a PHP image handling and manipulation library that provides an easier and more expressive way to create, edit, and compose images. This guide covers installation and configuration for Laravel 12.

## Installation

### 1. Install via Composer

```bash
composer require intervention/image
```

### 2. Verify Installation

Check if the package is installed correctly:

```bash
composer show intervention/image
```

You should see output similar to:
```
name     : intervention/image
descrip. : Image handling and manipulation library with support for Laravel integration
keywords : image, manipulation, resize, crop, filter, laravel
versions : * 3.11.4
```

## Configuration

### Laravel 12 Setup

Laravel 12 uses a different approach for Intervention Image compared to older versions. The package is auto-discovered, so no additional configuration is required.

### Service Provider (Auto-Discovery)

Laravel 12 automatically discovers the Intervention Image service provider. No manual registration is needed.

### Configuration File (Optional)

If you need custom configuration, publish the config file:

```bash
php artisan vendor:publish --provider="Intervention\Image\ImageServiceProviderLaravelRecent"
```

**Note**: In Laravel 12, this command may not publish any files as the package is designed to work out-of-the-box.

## Usage Examples

### Basic Usage

```php
<?php

namespace App\Http\Controllers;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageController extends Controller
{
    public function processImage()
    {
        // Create image manager
        $manager = new ImageManager(new Driver());
        
        // Read image from file
        $image = $manager->read('path/to/image.jpg');
        
        // Resize image
        $image->resize(800, 600);
        
        // Save image
        $image->save('path/to/output.jpg');
    }
}
```

### Available Drivers

Intervention Image supports multiple drivers:

#### GD Driver (Recommended for most cases)
```php
use Intervention\Image\Drivers\Gd\Driver;

$manager = new ImageManager(new Driver());
```

#### Imagick Driver (Requires ImageMagick extension)
```php
use Intervention\Image\Drivers\Imagick\Driver;

$manager = new ImageManager(new Driver());
```

### Common Operations

#### Resize Image
```php
$image->resize(800, 600);
```

#### Resize with Constraints
```php
$image->resize(800, 600, function ($constraint) {
    $constraint->aspectRatio();
    $constraint->upsize();
});
```

#### Crop Image
```php
$image->crop(400, 300, 100, 100); // width, height, x, y
```

#### Rotate Image
```php
$image->rotate(45);
```

#### Add Text
```php
$image->text('Hello World', 100, 100, function ($font) {
    $font->size(24);
    $font->color('#ffffff');
    $font->align('center');
    $font->valign('middle');
});
```

#### Place Image
```php
$image->place($otherImage, 100, 100);
```

#### Save with Quality
```php
$image->save('output.jpg', 80); // 80% quality
```

## System Requirements

### PHP Extensions

#### Required
- **GD Extension** (recommended)
  ```bash
  # Ubuntu/Debian
  sudo apt-get install php-gd
  
  # CentOS/RHEL
  sudo yum install php-gd
  
  # macOS (with Homebrew)
  brew install php
  # GD is included by default
  ```

#### Optional
- **ImageMagick Extension** (for advanced features)
  ```bash
  # Ubuntu/Debian
  sudo apt-get install php-imagick
  
  # CentOS/RHEL
  sudo yum install php-imagick
  
  # macOS (with Homebrew)
  brew install imagemagick
  brew install php-imagick
  ```

### Verify Extensions

Check if extensions are installed:

```bash
php -m | grep -i gd
php -m | grep -i imagick
```
