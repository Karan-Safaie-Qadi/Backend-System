<div align="center">

# ⚡ Backend System

**A Complete Object-Oriented PHP Backend Management System**  
**یک سیستم مدیریت بک‌اند تماما شی‌گرا با PHP**

[![PHP](https://img.shields.io/badge/PHP-≥7.4-777BB4?style=flat-square&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue?style=flat-square)](LICENSE)
[![Packagist](https://img.shields.io/badge/packagist-v1.0.0-orange?style=flat-square)](https://packagist.org/packages/karan-safaie-qadi/backend-system)
[![Built on](https://img.shields.io/badge/built%20on-pdo--module-6c5ce7?style=flat-square)](https://packagist.org/packages/karan-safaie-qadi/pdo-module)

---

**English** · [Persian](#فارسی)

</div>

## English

A powerful, secure, and extensible OOP backend system built on the [PDO-Module](https://packagist.org/packages/karan-safaie-qadi/pdo-module) package. Designed for rapid backend development with built-in user management, product catalog, article publishing, role-based access control, and more.

### ✨ Features

| Feature | Description |
|---------|-------------|
| **🔐 User Management** | Registration (email/phone mode), login, logout, password reset, profile management |
| **📦 Products** | Full CRUD, stock management, pricing, categories, featured/on-sale filters |
| **📝 Articles** | Rich content with sections (text, list, table, image, mixed), TOC, publishing workflow |
| **👮 Access Control** | 3 default roles (user, admin, owner) + fully extensible via config |
| **📧 Email** | Integrated PHPMailer for password resets, welcome emails, and notifications |
| **📊 Activity Log** | Automatic tracking of all system actions with user and entity context |
| **🔧 System Service** | Custom method registry, file uploads, slug generation, input sanitization |
| **🗄️ Database** | Secure PDO with prepared statements, transactions, pagination |
| **🌐 Bilingual SPA** | Beautiful test panel in Persian & English |

### 📦 Installation

```bash
composer require karan-safaie-qadi/backend-system
```

### ⚙️ Configuration

1. Copy `.env.example` to `.env` and configure your database:
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=my_database
DB_USERNAME=root
DB_PASSWORD=
```

2. Adjust settings in `config/app.php`:
```php
'auth' => [
    'registration_mode' => 'email', // 'email' or 'phone'
    'password_min_length' => 8,
],
'access_levels' => [
    1 => 'user',
    2 => 'admin',
    3 => 'owner',
    // 4 => 'editor', // add custom levels
],
```

3. Import the database schema:
```bash
mysql -u root -p my_database < migrations/schema.sql
```

4. Initialize in your project:
```php
require_once 'vendor/autoload.php';

use Database\Database;
use App\Core\Config;

Database::setRootPath(__DIR__);
Config::load(__DIR__ . '/config');
```

### 🚀 Usage

#### User Registration & Authentication

```php
use App\Services\AuthService;

// Register (mode is 'email')
$user = AuthService::register([
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'password' => 'securePass123',
    'display_name' => 'John Doe',
]);

// Login
$user = AuthService::login('johndoe', 'securePass123');

// Get current user
$user = AuthService::getCurrentUser();

// Logout
AuthService::logout();
```

#### Managing Products

```php
use App\Services\ProductService;

// Create product
$product = ProductService::createProduct([
    'name' => 'Smartphone X',
    'price' => 699.99,
    'stock_quantity' => 50,
    'category_id' => 1,
]);

// Get all products (paginated)
$products = ProductService::getAllProducts(page: 1, perPage: 20);

// Search
$results = ProductService::searchProducts('smartphone');

// Update stock
ProductService::updateStock(1, 100);
```

#### Working with Articles

```php
use App\Services\ArticleService;

// Create article with sections
$article = ArticleService::createArticle([
    'title' => 'Getting Started with PHP',
    'summary' => 'A comprehensive guide',
    'category_id' => 2,
    'author_id' => 1,
], [
    ['title' => 'Introduction', 'section_type' => 'text', 'content' => 'PHP is a popular language...'],
    ['title' => 'Key Features', 'section_type' => 'list', 'list_items' => ['Easy to learn', 'Widely used', 'Great community']],
    ['title' => 'Comparison', 'section_type' => 'table', 'table_data' => [
        ['Feature', 'PHP', 'Python'], ['Speed', 'Fast', 'Moderate'], ['Learning', 'Easy', 'Easy']
    ]],
]);

// Get article with sections and TOC
$article = ArticleService::getArticle(1);
// $article['sections'] - all sections
// $article['toc'] - table of contents

// Publish
ArticleService::publishArticle(1);
```

#### Access Control

```php
use App\Auth\AccessControl;

// Check permissions
if (AccessControl::isAdmin($userLevel)) { /* user is admin+ */ }
if (AccessControl::isOwner($userLevel)) { /* user is owner */ }
if (AccessControl::canManageAdmins($userLevel)) { /* only owner */ }

// Enforce minimum level
AccessControl::requireLevel($userLevel, 'admin');

// Custom levels from config
$allLevels = AccessControl::getLevels();   // [1 => 'user', 2 => 'admin', ...]
$levelName = AccessControl::getLevelName(2); // 'admin'
```

#### Custom System Methods

```php
use App\Services\SystemService;

// Register custom methods
SystemService::registerMethod('hello', function($name) {
    return "Hello, $name!";
});

// Call them
echo SystemService::callMethod('hello', 'World'); // Hello, World!

// Utility methods
$slug = SystemService::generateSlug('Hello World'); // 'hello-world'
$clean = SystemService::sanitizeInput('<script>alert("xss")</script>');
```

### 🧪 Running the Test SPA

Start a PHP development server:

```bash
php -S localhost:8000 -t public/
```

Open `http://localhost:8000` in your browser. The test panel features:
- 📊 Dashboard with real-time stats
- 👥 User CRUD with search and pagination
- 📦 Product management with stock tracking
- 📝 Article editor with section builder
- ⚙️ System info panel
- 🌐 Bilingual (English/Persian) interface
- 🔄 SPA routing with hash-based navigation

### 🏗️ Architecture

```
src/
├── Core/              # Base classes
│   ├── Config.php     # Configuration manager (dot-notation access)
│   ├── Model.php      # Base model (extends PDO-Module's Model)
│   ├── Mailer.php     # PHPMailer wrapper
│   └── Session.php    # Session management
├── Auth/
│   └── AccessControl.php  # Role-based access control
├── Models/            # Data models (one per database table)
│   ├── User.php       # Users CRUD + auth helpers
│   ├── Product.php    # Products CRUD + stock/pricing
│   ├── Article.php    # Articles CRUD + publishing
│   ├── ArticleSection.php  # Article sections (text/list/table/image)
│   ├── Category.php   # Hierarchical categories
│   └── ActivityLog.php     # Activity tracking
├── Services/          # Business logic layer
│   ├── AuthService.php     # Authentication & registration
│   ├── UserService.php     # User management
│   ├── ProductService.php  # Product management
│   ├── ArticleService.php  # Article management
│   ├── AdminService.php    # Admin dashboard & operations
│   └── SystemService.php   # Custom methods & utilities
└── Traits/
    └── HasTimestamps.php    # Timestamps trait
```

### 📄 Database Schema

The full schema is in `migrations/schema.sql` with 7 tables:

| Table | Purpose |
|-------|---------|
| `users` | User accounts with roles and auth tokens |
| `categories` | Hierarchical categories for products & articles |
| `products` | Product catalog with pricing, stock, images |
| `articles` | Content articles with metadata |
| `article_sections` | Rich content sections (text, list, table, image) |
| `activity_logs` | System-wide activity tracking |
| `settings` | Key-value application settings |

### 🛠️ Development

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit

# Check syntax
find src -name "*.php" -exec php -l {} \;
```

### 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

### 👤 Author

**Karan Safaie Qadi**
- GitHub: [@Karan-Safaie-Qadi](https://github.com/Karan-Safaie-Qadi)
- Packagist: [karan-safaie-qadi](https://packagist.org/packages/karan-safaie-qadi/)

---

<br>

<div align="center" dir="rtl">

## فارسی

یک سیستم مدیریت بک‌اند قدرتمند، امن و قابل گسترش مبتنی بر [PDO-Module](https://packagist.org/packages/karan-safaie-qadi/pdo-module). طراحی شده برای توسعه سریع بک‌اند با قابلیت‌های مدیریت کاربران، محصولات، مقالات، کنترل دسترسی سطح‌بندی شده و موارد دیگر.

### ✨ ویژگی‌ها

| ویژگی | توضیحات |
|-------|---------|
| **🔐 مدیریت کاربران** | ثبت‌نام با دو حالت (ایمیل/شماره تلفن اجباری)، ورود و خروج، بازیابی رمز عبور |
| **📦 محصولات** | مدیریت کامل، موجودی، قیمت‌گذاری، دسته‌بندی، فیلتر ویژه/تخفیف |
| **📝 مقالات** | محتوای غنی با بخش‌های متنوع (متن، لیست، جدول، تصویر)، فهرست مطالب |
| **👮 کنترل دسترسی** | ۳ نقش پیش‌فرض (کاربر، مدیر، مالک) + قابلیت افزودن نقش جدید از طریق کانفیگ |
| **📧 ایمیل** | PHPMailer یکپارچه برای بازیابی رمز و اطلاع‌رسانی |
| **📊 لاگ فعالیت** | ثبت خودکار تمام عملیات با جزئیات کاربر و موجودیت |
| **🔧 سیستم سفارشی** | ثبت متدهای دلخواه، آپلود فایل، تولید slug، پالایش ورودی |
| **🗄️ دیتابیس** | PDO امن با Prepared Statements، تراکنش، صفحه‌بندی |
| **🌐 پنل دو زبانه** | SPA زیبا برای تست با پشتیبانی فارسی و انگلیسی |

### 📦 نصب

```bash
composer require karan-safaie-qadi/backend-system
```

### ⚙️ تنظیمات

۱. فایل `.env.example` را به `.env` کپی کنید و اطلاعات دیتابیس را تنظیم کنید:
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=my_database
DB_USERNAME=root
DB_PASSWORD=
```

۲. تنظیمات را در `config/app.php` شخصی‌سازی کنید:
```php
'auth' => [
    'registration_mode' => 'email', // 'email' یا 'phone'
    'password_min_length' => 8,
],
'access_levels' => [
    1 => 'user',
    2 => 'admin',
    3 => 'owner',
    // 4 => 'editor', // سطوح دلخواه اضافه کنید
],
```

۳. اسکیمای دیتابیس را اجرا کنید:
```bash
mysql -u root -p my_database < migrations/schema.sql
```

۴. در پروژه خود مقداردهی کنید:
```php
require_once 'vendor/autoload.php';

use Database\Database;
use App\Core\Config;

Database::setRootPath(__DIR__);
Config::load(__DIR__ . '/config');
```

### 🚀 نحوه استفاده

#### ثبت‌نام و احراز هویت

```php
use App\Services\AuthService;

// ثبت‌نام کاربر
$user = AuthService::register([
    'username' => 'ali',
    'email' => 'ali@example.com',
    'password' => '12345678',
]);

// ورود
$user = AuthService::login('ali', '12345678');

// خروج
AuthService::logout();
```

#### مدیریت محصولات

```php
use App\Services\ProductService;

// ایجاد محصول
$product = ProductService::createProduct([
    'name' => 'گوشی هوشمند X',
    'price' => 25000000,
    'stock_quantity' => 50,
]);

// جستجو
$results = ProductService::searchProducts('گوشی');
```

#### مقالات

```php
use App\Services\ArticleService;

$article = ArticleService::createArticle([
    'title' => 'آموزش PHP',
    'summary' => 'یک راهنمای جامع',
], [
    ['title' => 'مقدمه', 'section_type' => 'text', 'content' => 'PHP یک زبان محبوب است...'],
    ['title' => 'ویژگی‌ها', 'section_type' => 'list', 'list_items' => ['آسان', 'قدرتمند', 'رایگان']],
]);
```

### 🧪 اجرای پنل تست

```bash
php -S localhost:8000 -t public/
```

سپس در مرورگر به آدرس `http://localhost:8000` مراجعه کنید.

### 👤 نویسنده

**کاران صفایی قادی**
- گیت‌هاب: [@Karan-Safaie-Qadi](https://github.com/Karan-Safaie-Qadi)

</div>
