<?php

require_once __DIR__ . '/bootstrap.php';

use App\Core\Config;
use App\Core\Session;
use App\Core\Model;
use App\Core\Mailer;
use App\Auth\AccessControl;
use App\Services\SystemService;
use App\Services\AuthService;
use App\Services\UserService;
use App\Services\ProductService;
use App\Services\ArticleService;
use App\Services\AdminService;
use App\Traits\HasTimestamps;
use App\Models\User;
use App\Models\Product;
use App\Models\Article;
use App\Models\Category;
use App\Models\ArticleSection;
use App\Models\ActivityLog;

$passed = 0;
$failed = 0;
$skipped = 0;
$tests = [];

function test(string $label, callable $fn): void {
    global $tests, $passed, $failed;
    try {
        $fn();
        $tests[] = ['label' => $label, 'status' => 'PASS'];
        $passed++;
    } catch (\Throwable $e) {
        $tests[] = ['label' => $label, 'status' => 'FAIL', 'error' => $e->getMessage()];
        $failed++;
    }
}

function skip(string $label): void {
    global $tests, $skipped;
    $tests[] = ['label' => $label, 'status' => 'SKIP'];
    $skipped++;
}

// ======================================================
// 1. CORE: Config
// ======================================================
test('Config::get() — existing key', function () {
    assert(Config::get('app.name') === 'Backend System');
});

test('Config::get() — nested key', function () {
    assert(Config::get('auth.registration_mode') === 'email');
});

test('Config::get() — default on missing key', function () {
    assert(Config::get('nonexistent.key', 'fallback') === 'fallback');
});

test('Config::set() — set and retrieve', function () {
    Config::set('test.key', 'value123');
    assert(Config::get('test.key') === 'value123');
});

test('Config::all() — returns array', function () {
    $all = Config::all();
    assert(is_array($all));
    assert(isset($all['app']));
    assert(isset($all['auth']));
    assert(isset($all['access_levels']));
});

// ======================================================
// 2. CORE: Session
// ======================================================
test('Session::set() and Session::get()', function () {
    Session::set('test_key', 'hello');
    assert(Session::get('test_key') === 'hello');
});

test('Session::get() — default value', function () {
    assert(Session::get('_nonexistent_') === null);
    assert(Session::get('_nonexistent_', 'default') === 'default');
});

test('Session::has()', function () {
    Session::set('_exists_', 'yes');
    assert(Session::has('_exists_') === true);
    assert(Session::has('_nope_') === false);
});

test('Session::remove()', function () {
    Session::set('_rem_', 'x');
    Session::remove('_rem_');
    assert(Session::has('_rem_') === false);
});

test('Session::flash()', function () {
    Session::flash('_flash_', 'flash_val');
    assert(Session::flash('_flash_') === 'flash_val');
    assert(Session::flash('_flash_') === null);
});

// ======================================================
// 3. CORE: Model (abstract — test existence)
// ======================================================
test('Model class is abstract', function () {
    $ref = new ReflectionClass(Model::class);
    assert($ref->isAbstract());
});

test('Model has required static methods', function () {
    $methods = ['find', 'all', 'create', 'updateRecord', 'deleteRecord', 'findBy', 'where', 'count', 'exists', 'paginate', 'search', 'pluck', 'latest', 'oldest'];
    foreach ($methods as $m) {
        assert(method_exists(Model::class, $m), "Method $m missing");
    }
});

test('Model extends Models\Model', function () {
    assert((new ReflectionClass(Model::class))->getParentClass()->getName() === 'Models\Model');
});

// ======================================================
// 4. CORE: Mailer — class exists, has correct methods
// ======================================================
test('Mailer class exists and has methods', function () {
    assert(class_exists(Mailer::class));
    assert(method_exists(Mailer::class, 'send'));
    assert(method_exists(Mailer::class, 'sendWithTemplate'));
});

// ======================================================
// 5. AUTH: AccessControl
// ======================================================
test('AccessControl::getLevels()', function () {
    $levels = AccessControl::getLevels();
    assert(is_array($levels));
    assert($levels[1] === 'user');
    assert($levels[2] === 'admin');
    assert($levels[3] === 'owner');
});

test('AccessControl::getLevelName()', function () {
    assert(AccessControl::getLevelName(1) === 'user');
    assert(AccessControl::getLevelName(2) === 'admin');
    assert(AccessControl::getLevelName(3) === 'owner');
    assert(AccessControl::getLevelName(99) === 'unknown');
});

test('AccessControl::getLevelValue()', function () {
    assert(AccessControl::getLevelValue('user') === 1);
    assert(AccessControl::getLevelValue('admin') === 2);
    assert(AccessControl::getLevelValue('owner') === 3);
    assert(AccessControl::getLevelValue('nonexistent') === null);
});

test('AccessControl::hasAccess()', function () {
    assert(AccessControl::hasAccess(1, 1) === true);
    assert(AccessControl::hasAccess(1, 2) === false);
    assert(AccessControl::hasAccess(3, 'user') === true);
    assert(AccessControl::hasAccess(1, 'owner') === false);
    assert(AccessControl::hasAccess(0, 'nonexistent') === false);
});

test('AccessControl::isAdmin()', function () {
    assert(AccessControl::isAdmin(2) === true);
    assert(AccessControl::isAdmin(3) === true);
    assert(AccessControl::isAdmin(1) === false);
});

test('AccessControl::isOwner()', function () {
    assert(AccessControl::isOwner(3) === true);
    assert(AccessControl::isOwner(2) === false);
    assert(AccessControl::isOwner(1) === false);
});

test('AccessControl::canManageAdmins()', function () {
    assert(AccessControl::canManageAdmins(3) === true);
    assert(AccessControl::canManageAdmins(2) === false);
});

test('AccessControl::requireLevel() — allowed', function () {
    AccessControl::requireLevel(3, 'admin');
    assert(true);
});

test('AccessControl::requireLevel() — denied', function () {
    $thrown = false;
    try { AccessControl::requireLevel(1, 'owner'); } catch (\RuntimeException $e) { $thrown = true; }
    assert($thrown, 'Should throw RuntimeException');
});

// ======================================================
// 6. TRAIT: HasTimestamps
// ======================================================
test('HasTimestamps trait methods', function () {
    $mock = new class { use HasTimestamps; };
    assert(trait_exists(HasTimestamps::class));
    assert(method_exists($mock, 'addTimestamps'));
});

// ======================================================
// 7. SERVICES: SystemService (no DB needed)
// ======================================================
test('SystemService::generateSlug()', function () {
    assert(SystemService::generateSlug('Hello World') === 'hello-world');
    assert(SystemService::generateSlug('  A  B  C  ') === 'a-b-c');
    assert(SystemService::generateSlug('Test Slug!') === 'test-slug');
    assert(SystemService::generateSlug('Hello', '_') === 'hello');
});

test('SystemService::sanitizeInput()', function () {
    assert(SystemService::sanitizeInput('<script>alert("xss")</script>') === 'alert("xss")');
    assert(SystemService::sanitizeInput('  <b>hello</b>  ') === 'hello');
    assert(SystemService::sanitizeInput('<a href="">link</a>') === 'link');
});

test('SystemService::generateToken()', function () {
    $t = SystemService::generateToken();
    assert(strlen($t) === 64);
    assert(preg_match('/^[a-f0-9]+$/', $t) === 1);
});

test('SystemService::generateToken() — custom length', function () {
    $t = SystemService::generateToken(16);
    assert(strlen($t) === 32);
});

test('SystemService::dateFormat()', function () {
    assert(SystemService::dateFormat('2024-01-15 10:30:00', 'Y/m/d') === '2024/01/15');
});

test('SystemService::timeAgo()', function () {
    assert(SystemService::timeAgo(date('Y-m-d H:i:s')) === 'just now');
});

test('SystemService::registerMethod(), callMethod(), hasMethod()', function () {
    SystemService::registerMethod('double', fn($x) => $x * 2);
    assert(SystemService::callMethod('double', 5) === 10);
    assert(SystemService::callMethod('double', 0) === 0);
    assert(SystemService::hasMethod('double') === true);
    assert(SystemService::hasMethod('nonexistent') === false);
});

test('SystemService::callMethod() — non-existent throws', function () {
    $thrown = false;
    try { SystemService::callMethod('_nothing_'); } catch (\RuntimeException $e) { $thrown = true; }
    assert($thrown);
});

test('SystemService::getRegisteredMethods()', function () {
    SystemService::registerMethod('_test1_', fn() => null);
    $methods = SystemService::getRegisteredMethods();
    assert(in_array('_test1_', $methods));
});

test('SystemService::getConfig() and setConfig()', function () {
    $orig = SystemService::getConfig('app.debug');
    SystemService::setConfig('app.debug', !$orig);
    assert(SystemService::getConfig('app.debug') === !$orig);
    SystemService::setConfig('app.debug', $orig);
});

test('SystemService::deleteFile() — non-existent returns false', function () {
    assert(SystemService::deleteFile('/nonexistent/file.txt') === false);
});

// ======================================================
// 8. AUTH: AuthService (non-DB methods)
// ======================================================
test('AuthService::isLoggedIn() — initially false', function () {
    assert(AuthService::isLoggedIn() === false);
});

test('AuthService::getCurrentLevel() — initially 0', function () {
    assert(AuthService::getCurrentLevel() === 0);
});

test('AuthService::getCurrentUser() — initially null', function () {
    assert(AuthService::getCurrentUser() === null);
});

// ======================================================
// 9. SERVICES: UserService (non-DB method)
// ======================================================
test('UserService::getByUsername() — non-existent returns null', function () {
    assert(UserService::getByUsername('__nonexistent_user_xxx__') === null);
});

test('UserService::getByEmail() — non-existent returns null', function () {
    assert(UserService::getByEmail('__nonexistent@test.com__') === null);
});

test('UserService::getByPhone() — non-existent returns null', function () {
    assert(UserService::getByPhone('+00000000000') === null);
});

// ======================================================
// 10. SERVICES: ProductService (non-DB method)
// ======================================================
test('ProductService::getBySlug() — non-existent returns null', function () {
    assert(ProductService::getBySlug('__no-such-product__') === null);
});

// ======================================================
// 11. SERVICES: ArticleService (non-DB method)
// ======================================================
test('ArticleService::getBySlug() — non-existent returns null', function () {
    assert(ArticleService::getBySlug('__no-such-article__') === null);
});

// ======================================================
// 12. MODEL: User model — static logic methods
// ======================================================
test('User model constants and methods exist', function () {
    assert(method_exists(User::class, 'findByUsername'));
    assert(method_exists(User::class, 'findByEmail'));
    assert(method_exists(User::class, 'findByPhone'));
    assert(method_exists(User::class, 'findByRememberToken'));
    assert(method_exists(User::class, 'findByPasswordResetToken'));
    assert(method_exists(User::class, 'getByAccessLevel'));
    assert(method_exists(User::class, 'getAdmins'));
    assert(method_exists(User::class, 'getRegularUsers'));
    assert(method_exists(User::class, 'searchUsers'));
    assert(method_exists(User::class, 'updateLastLogin'));
    assert(method_exists(User::class, 'setRememberToken'));
    assert(method_exists(User::class, 'clearRememberToken'));
    assert(method_exists(User::class, 'setPasswordResetToken'));
    assert(method_exists(User::class, 'clearPasswordResetToken'));
    assert(method_exists(User::class, 'updatePassword'));
    assert(method_exists(User::class, 'verifyEmail'));
    assert(method_exists(User::class, 'verifyPhone'));
    assert(method_exists(User::class, 'isEmailVerified'));
    assert(method_exists(User::class, 'isPhoneVerified'));
    assert(method_exists(User::class, 'isActive'));
    assert(method_exists(User::class, 'countByAccessLevel'));
    assert(method_exists(User::class, 'getRecentUsers'));
});

test('User::isActive() — logic', function () {
    assert(User::isActive(['is_active' => 1]) === true);
    assert(User::isActive(['is_active' => 0]) === false);
    assert(User::isActive([]) === true);
});

test('User::isEmailVerified() — logic', function () {
    assert(User::isEmailVerified(['email_verified_at' => '2024-01-01']) === true);
    assert(User::isEmailVerified(['email_verified_at' => null]) === false);
});

test('User::isPhoneVerified() — logic', function () {
    assert(User::isPhoneVerified(['phone_verified_at' => '2024-01-01']) === true);
    assert(User::isPhoneVerified(['phone_verified_at' => null]) === false);
});

// ======================================================
// 13. MODEL: Product model — static methods exist
// ======================================================
test('Product model methods exist', function () {
    assert(method_exists(Product::class, 'findBySlug'));
    assert(method_exists(Product::class, 'findBySku'));
    assert(method_exists(Product::class, 'getByCategory'));
    assert(method_exists(Product::class, 'getActive'));
    assert(method_exists(Product::class, 'getFeatured'));
    assert(method_exists(Product::class, 'getInStock'));
    assert(method_exists(Product::class, 'getOutOfStock'));
    assert(method_exists(Product::class, 'getOnSale'));
    assert(method_exists(Product::class, 'getByPriceRange'));
    assert(method_exists(Product::class, 'searchProducts'));
    assert(method_exists(Product::class, 'updateStock'));
    assert(method_exists(Product::class, 'decreaseStock'));
    assert(method_exists(Product::class, 'getLowStock'));
    assert(method_exists(Product::class, 'getCategoryWithProducts'));
    assert(method_exists(Product::class, 'getRelatedProducts'));
    assert(method_exists(Product::class, 'toggleActive'));
    assert(method_exists(Product::class, 'toggleFeatured'));
});

// ======================================================
// 14. MODEL: Article model — static methods exist
// ======================================================
test('Article model methods exist', function () {
    assert(method_exists(Article::class, 'findBySlug'));
    assert(method_exists(Article::class, 'getByCategory'));
    assert(method_exists(Article::class, 'getByAuthor'));
    assert(method_exists(Article::class, 'getPublished'));
    assert(method_exists(Article::class, 'getDrafts'));
    assert(method_exists(Article::class, 'searchArticles'));
    assert(method_exists(Article::class, 'publish'));
    assert(method_exists(Article::class, 'unpublish'));
    assert(method_exists(Article::class, 'getWithSections'));
    assert(method_exists(Article::class, 'getRecent'));
    assert(method_exists(Article::class, 'getPopular'));
    assert(method_exists(Article::class, 'getByCategoryWithPagination'));
    assert(method_exists(Article::class, 'getArchiveByMonth'));
    assert(method_exists(Article::class, 'getRelatedArticles'));
});

// ======================================================
// 15. MODEL: Category model — static methods exist
// ======================================================
test('Category model methods exist', function () {
    assert(method_exists(Category::class, 'findBySlug'));
    assert(method_exists(Category::class, 'getByType'));
    assert(method_exists(Category::class, 'getParentCategories'));
    assert(method_exists(Category::class, 'getChildren'));
    assert(method_exists(Category::class, 'getCategoryTree'));
    assert(method_exists(Category::class, 'getWithProductCount'));
    assert(method_exists(Category::class, 'getWithArticleCount'));
    assert(method_exists(Category::class, 'searchCategories'));
});

// ======================================================
// 16. MODEL: ArticleSection model — static methods exist
// ======================================================
test('ArticleSection model constants and methods', function () {
    assert(ArticleSection::TYPE_TEXT === 'text');
    assert(ArticleSection::TYPE_LIST === 'list');
    assert(ArticleSection::TYPE_TABLE === 'table');
    assert(ArticleSection::TYPE_IMAGE === 'image');
    assert(ArticleSection::TYPE_MIXED === 'mixed');
    assert(method_exists(ArticleSection::class, 'getByArticle'));
    assert(method_exists(ArticleSection::class, 'addSection'));
    assert(method_exists(ArticleSection::class, 'updateSection'));
    assert(method_exists(ArticleSection::class, 'reorder'));
    assert(method_exists(ArticleSection::class, 'deleteByArticle'));
    assert(method_exists(ArticleSection::class, 'duplicateSections'));
    assert(method_exists(ArticleSection::class, 'getTableOfContents'));
});

// ======================================================
// 17. MODEL: ActivityLog model — static methods exist
// ======================================================
test('ActivityLog model methods exist', function () {
    assert(method_exists(ActivityLog::class, 'log'));
    assert(method_exists(ActivityLog::class, 'getByUser'));
    assert(method_exists(ActivityLog::class, 'getByEntity'));
    assert(method_exists(ActivityLog::class, 'getRecent'));
    assert(method_exists(ActivityLog::class, 'getByAction'));
    assert(method_exists(ActivityLog::class, 'countToday'));
    assert(method_exists(ActivityLog::class, 'cleanOld'));
});

// ======================================================
// 18. ADMIN: AdminService — system info (no DB required for PHP version)
// ======================================================
test('AdminService::getSystemInfo() — has php_version', function () {
    $info = AdminService::getSystemInfo();
    assert(isset($info['php_version']));
    assert($info['php_version'] === PHP_VERSION);
    assert(isset($info['server_software']));
    assert(isset($info['database']));
    assert(isset($info['app_config']));
});

// ======================================================
// 19. SERVICE: ProductService — slug method (testable via createProduct with mock)
//    We test the slug private method indirectly via SystemService (same logic)
// ======================================================
// ======================================================
// 20. FULL CRUD — User (requires database)
// ======================================================
$testUserId = null;

test('User::create() — insert a new user', function () use (&$testUserId) {
    $id = User::create([
        'username' => 'smoke_test_' . uniqid(),
        'password' => password_hash('test1234', PASSWORD_DEFAULT),
        'display_name' => 'Smoke Test User',
        'email' => 'smoke_' . uniqid() . '@test.com',
        'access_level' => 1,
        'is_active' => 1,
    ]);
    assert(is_numeric($id) && $id > 0);
    $testUserId = (int)$id;
});

test('User::find() — retrieve by ID', function () use (&$testUserId) {
    $u = User::find($testUserId);
    assert($u !== null);
    assert($u['id'] == $testUserId);
    assert($u['display_name'] === 'Smoke Test User');
});

test('User::findByUsername()', function () use (&$testUserId) {
    $u = User::find(User::findByUsername('smoke_test_' . substr(User::find($testUserId)['username'], 11))['id']);
    assert($u !== null);
});

test('User::findByEmail()', function () use (&$testUserId) {
    $u = User::findByEmail(User::find($testUserId)['email']);
    assert($u !== null && $u['id'] == $testUserId);
});

test('User::updateRecord()', function () use (&$testUserId) {
    User::updateRecord($testUserId, ['display_name' => 'Updated Name']);
    $u = User::find($testUserId);
    assert($u['display_name'] === 'Updated Name');
});

test('User::isActive()', function () use (&$testUserId) {
    $u = User::find($testUserId);
    assert(User::isActive($u) === true);
});

test('User::updateLastLogin()', function () use (&$testUserId) {
    User::updateLastLogin($testUserId);
    $u = User::find($testUserId);
    assert($u['last_login_at'] !== null);
});

test('User::updatePassword() and password verification', function () use (&$testUserId) {
    $newHash = password_hash('newpass123', PASSWORD_DEFAULT);
    User::updatePassword($testUserId, $newHash);
    $u = User::find($testUserId);
    assert(password_verify('newpass123', $u['password']));
});

test('User::verifyEmail() and isEmailVerified()', function () use (&$testUserId) {
    User::verifyEmail($testUserId);
    $u = User::find($testUserId);
    assert(User::isEmailVerified($u) === true);
});

test('User::verifyPhone() and isPhoneVerified()', function () use (&$testUserId) {
    User::updateRecord($testUserId, ['phone' => '+989121234567']);
    User::verifyPhone($testUserId);
    $u = User::find($testUserId);
    assert(User::isPhoneVerified($u) === true);
});

test('User::setRememberToken() and findByRememberToken()', function () use (&$testUserId) {
    $token = bin2hex(random_bytes(32));
    User::setRememberToken($testUserId, $token);
    $u = User::findByRememberToken($token);
    assert($u !== null && $u['id'] == $testUserId);
});

test('User::clearRememberToken()', function () use (&$testUserId) {
    User::clearRememberToken($testUserId);
    $u = User::find($testUserId);
    assert($u['remember_token'] === null);
});

test('User::setPasswordResetToken() and findByPasswordResetToken()', function () use (&$testUserId) {
    $u = User::find($testUserId);
    User::setPasswordResetToken($u['email'], 'test_reset_token_' . $testUserId);
    $found = User::findByPasswordResetToken('test_reset_token_' . $testUserId);
    assert($found !== null && $found['id'] == $testUserId);
});

test('User::clearPasswordResetToken()', function () use (&$testUserId) {
    User::clearPasswordResetToken($testUserId);
    $u = User::find($testUserId);
    assert($u['password_reset_token'] === null);
});

test('User::countByAccessLevel()', function () {
    $count = User::countByAccessLevel(1);
    assert(is_int($count) && $count >= 0);
});

test('User::getRecentUsers()', function () {
    $recent = User::getRecentUsers(5);
    assert(is_array($recent));
});

test('User::getByAccessLevel()', function () {
    $users = User::getByAccessLevel(1);
    assert(is_array($users));
});

test('User::searchUsers()', function () {
    $results = User::searchUsers('smoke_test');
    assert(is_array($results));
    assert(count($results) >= 1);
});

test('User::paginate()', function () {
    $result = User::paginate(1, 10);
    assert(isset($result['items']));
    assert(isset($result['total']));
    assert(isset($result['page']));
    assert(isset($result['total_pages']));
    assert($result['page'] === 1);
});

test('User::all()', function () {
    $all = User::all();
    assert(is_array($all));
});

test('User::count()', function () {
    $c = User::count();
    assert(is_int($c) && $c > 0);
});

test('User::exists()', function () use (&$testUserId) {
    assert(User::exists($testUserId) === true);
    assert(User::exists(9999999) === false);
});

test('User::getAdmins()', function () {
    $admins = User::getAdmins();
    assert(is_array($admins));
});

test('User::getRegularUsers()', function () {
    $regular = User::getRegularUsers();
    assert(is_array($regular));
});

test('User::deleteRecord()', function () use (&$testUserId) {
    User::deleteRecord($testUserId);
    assert(User::find($testUserId) === null);
});

// ======================================================
// 21. FULL CRUD — Category (requires database)
// ======================================================
$testCategoryId = null;

test('Category::create()', function () use (&$testCategoryId) {
    $id = Category::create([
        'name' => 'Test Category ' . uniqid(),
        'slug' => 'test-cat-' . uniqid(),
        'type' => 'product',
        'sort_order' => 0,
    ]);
    assert(is_numeric($id) && $id > 0);
    $testCategoryId = (int)$id;
});

test('Category::find()', function () use (&$testCategoryId) {
    $c = Category::find($testCategoryId);
    assert($c !== null);
    assert($c['id'] == $testCategoryId);
});

test('Category::findBySlug()', function () use (&$testCategoryId) {
    $c = Category::find($testCategoryId);
    $found = Category::findBySlug($c['slug']);
    assert($found !== null && $found['id'] == $testCategoryId);
});

test('Category::getByType()', function () {
    $cats = Category::getByType('product');
    assert(is_array($cats));
});

test('Category::getParentCategories()', function () {
    $parents = Category::getParentCategories();
    assert(is_array($parents));
});

test('Category::searchCategories()', function () {
    $results = Category::searchCategories('Test');
    assert(is_array($results));
});

test('Category::getWithProductCount()', function () {
    $results = Category::getWithProductCount();
    assert(is_array($results));
});

test('Category::getWithArticleCount()', function () {
    $results = Category::getWithArticleCount();
    assert(is_array($results));
});

test('Category::deleteRecord()', function () use (&$testCategoryId) {
    Category::deleteRecord($testCategoryId);
    assert(Category::find($testCategoryId) === null);
});

// ======================================================
// 22. FULL CRUD — Product (requires database)
// ======================================================
$testProductId = null;

test('Product::create()', function () use (&$testProductId) {
    $id = Product::create([
        'name' => 'Test Product ' . uniqid(),
        'slug' => 'test-prod-' . uniqid(),
        'price' => 29.99,
        'stock_quantity' => 100,
        'is_active' => 1,
    ]);
    assert(is_numeric($id) && $id > 0);
    $testProductId = (int)$id;
});

test('Product::find()', function () use (&$testProductId) {
    $p = Product::find($testProductId);
    assert($p !== null);
    assert($p['price'] == 29.99);
});

test('Product::findBySlug()', function () use (&$testProductId) {
    $p = Product::find($testProductId);
    $found = Product::findBySlug($p['slug']);
    assert($found !== null && $found['id'] == $testProductId);
});

test('Product::findBySku()', function () use (&$testProductId) {
    Product::updateRecord($testProductId, ['sku' => 'SKU-' . $testProductId]);
    $found = Product::findBySku('SKU-' . $testProductId);
    assert($found !== null && $found['id'] == $testProductId);
});

test('Product::updateStock() and getLowStock()', function () use (&$testProductId) {
    Product::updateStock($testProductId, 3);
    $p = Product::find($testProductId);
    assert($p['stock_quantity'] == 3);
    $low = Product::getLowStock(10);
    $ids = array_column($low, 'id');
    assert(in_array($testProductId, $ids));
});

test('Product::decreaseStock()', function () use (&$testProductId) {
    Product::decreaseStock($testProductId, 1);
    $p = Product::find($testProductId);
    assert($p['stock_quantity'] == 2);
});

test('Product::getActive()', function () {
    $active = Product::getActive();
    assert(is_array($active));
});

test('Product::getFeatured()', function () use (&$testProductId) {
    Product::updateRecord($testProductId, ['is_featured' => 1]);
    $featured = Product::getFeatured();
    $ids = array_column($featured, 'id');
    assert(in_array($testProductId, $ids));
});

test('Product::toggleFeatured()', function () use (&$testProductId) {
    Product::toggleFeatured($testProductId);
    $p = Product::find($testProductId);
    assert($p['is_featured'] == 0);
});

test('Product::getOutOfStock()', function () use (&$testProductId) {
    Product::updateStock($testProductId, 0);
    $oos = Product::getOutOfStock();
    $ids = array_column($oos, 'id');
    assert(in_array($testProductId, $ids));
});

test('Product::getInStock()', function () use (&$testProductId) {
    Product::updateStock($testProductId, 10);
    $inStock = Product::getInStock();
    $ids = array_column($inStock, 'id');
    assert(in_array($testProductId, $ids));
});

test('Product::toggleActive()', function () use (&$testProductId) {
    Product::toggleActive($testProductId);
    $p = Product::find($testProductId);
    assert($p['is_active'] == 0);
    Product::toggleActive($testProductId);
});

test('Product::getOnSale()', function () use (&$testProductId) {
    Product::updateRecord($testProductId, ['sale_price' => 19.99, 'price' => 29.99]);
    $onSale = Product::getOnSale();
    $ids = array_column($onSale, 'id');
    assert(in_array($testProductId, $ids));
});

test('Product::getByPriceRange()', function () use (&$testProductId) {
    $results = Product::getByPriceRange(10, 50);
    $ids = array_column($results, 'id');
    assert(in_array($testProductId, $ids));
});

test('Product::searchProducts()', function () {
    $results = Product::searchProducts('Test Product');
    assert(is_array($results) && count($results) >= 1);
});

test('Product::paginate()', function () {
    $result = Product::paginate(1, 10);
    assert(isset($result['items']));
    assert($result['page'] === 1);
});

test('Product::deleteRecord()', function () use (&$testProductId) {
    Product::deleteRecord($testProductId);
    assert(Product::find($testProductId) === null);
});

// ======================================================
// 23. FULL CRUD — Article + ArticleSection (requires database)
// ======================================================
$testArticleId = null;
$testSectionId = null;

test('Article::create()', function () use (&$testArticleId) {
    $id = Article::create([
        'title' => 'Test Article ' . uniqid(),
        'slug' => 'test-art-' . uniqid(),
        'summary' => 'This is a test article summary.',
        'is_published' => 0,
    ]);
    assert(is_numeric($id) && $id > 0);
    $testArticleId = (int)$id;
});

test('Article::find()', function () use (&$testArticleId) {
    $a = Article::find($testArticleId);
    assert($a !== null);
    assert($a['title'] === Article::find($testArticleId)['title']);
});

test('Article::findBySlug()', function () use (&$testArticleId) {
    $a = Article::find($testArticleId);
    $found = Article::findBySlug($a['slug']);
    assert($found !== null && $found['id'] == $testArticleId);
});

test('Article::getDrafts()', function () use (&$testArticleId) {
    $drafts = Article::getDrafts();
    $ids = array_column($drafts, 'id');
    assert(in_array($testArticleId, $ids));
});

test('Article::publish()', function () use (&$testArticleId) {
    Article::publish($testArticleId);
    $a = Article::find($testArticleId);
    assert($a['is_published'] == 1);
    assert($a['published_at'] !== null);
});

test('Article::getPublished()', function () use (&$testArticleId) {
    $published = Article::getPublished();
    $ids = array_column($published, 'id');
    assert(in_array($testArticleId, $ids));
});

test('Article::unpublish()', function () use (&$testArticleId) {
    Article::unpublish($testArticleId);
    $a = Article::find($testArticleId);
    assert($a['is_published'] == 0);
});

test('Article::updateRecord()', function () use (&$testArticleId) {
    Article::updateRecord($testArticleId, ['summary' => 'Updated summary']);
    $a = Article::find($testArticleId);
    assert($a['summary'] === 'Updated summary');
});

// ArticleSection tests
test('ArticleSection::addSection()', function () use (&$testArticleId, &$testSectionId) {
    $sid = ArticleSection::addSection($testArticleId, 'Section 1', 'text', 'Hello content');
    assert(is_numeric($sid) && $sid > 0);
    $testSectionId = (int)$sid;
});

test('ArticleSection::getByArticle()', function () use (&$testArticleId, &$testSectionId) {
    $sections = ArticleSection::getByArticle($testArticleId);
    assert(count($sections) >= 1);
    assert($sections[0]['id'] == $testSectionId);
});

test('ArticleSection::updateSection()', function () use (&$testSectionId) {
    ArticleSection::updateSection($testSectionId, ['title' => 'Updated Section Title']);
    $s = ArticleSection::find($testSectionId);
    assert($s['title'] === 'Updated Section Title');
});

test('ArticleSection::getTableOfContents()', function () use (&$testArticleId, &$testSectionId) {
    $toc = ArticleSection::getTableOfContents($testArticleId);
    assert(count($toc) >= 1);
    assert($toc[0]['id'] == $testSectionId);
});

test('ArticleSection::addSection() — list type', function () use (&$testArticleId) {
    $sid = ArticleSection::addSection($testArticleId, 'List Section', 'list', null, ['item1', 'item2', 'item3']);
    assert(is_numeric($sid));
});

test('ArticleSection::addSection() — table type', function () use (&$testArticleId) {
    $sid = ArticleSection::addSection($testArticleId, 'Table Section', 'table', null, null, [['Name', 'Age'], ['Alice', '30']]);
    assert(is_numeric($sid));
});

test('ArticleSection::getTableOfContents() — multiple sections', function () use (&$testArticleId) {
    $toc = ArticleSection::getTableOfContents($testArticleId);
    assert(count($toc) >= 3);
    assert($toc[0]['sort_order'] < $toc[1]['sort_order']);
});

test('Article::getWithSections()', function () use (&$testArticleId) {
    $a = Article::getWithSections($testArticleId);
    assert($a !== null);
    assert(isset($a['sections']));
    assert(count($a['sections']) >= 3);
});

test('Article::getRecent()', function () {
    $recent = Article::getRecent();
    assert(is_array($recent));
});

test('Article::searchArticles()', function () {
    $results = Article::searchArticles('Test Article');
    assert(is_array($results) && count($results) >= 1);
});

test('Article::paginate()', function () {
    $result = Article::paginate(1, 10);
    assert(isset($result['items']));
});

test('Article::getArchiveByMonth()', function () {
    $archive = Article::getArchiveByMonth();
    assert(is_array($archive));
});

test('ArticleSection::deleteByArticle()', function () use (&$testArticleId) {
    ArticleSection::deleteByArticle($testArticleId);
    $sections = ArticleSection::getByArticle($testArticleId);
    assert(count($sections) === 0);
});

test('Article::deleteRecord()', function () use (&$testArticleId) {
    Article::deleteRecord($testArticleId);
    assert(Article::find($testArticleId) === null);
});

// ======================================================
// 24. ActivityLog (requires database)
// ======================================================
$testLogId = null;

test('ActivityLog::log()', function () use (&$testLogId) {
    $id = ActivityLog::log(null, 'test_action', 'test', 0, 'Test log entry');
    assert(is_numeric($id) && $id > 0);
    $testLogId = (int)$id;
});

test('ActivityLog::find()', function () use (&$testLogId) {
    $log = ActivityLog::find($testLogId);
    assert($log !== null);
    assert($log['action'] === 'test_action');
});

test('ActivityLog::getByAction()', function () use (&$testLogId) {
    $logs = ActivityLog::getByAction('test_action');
    $ids = array_column($logs, 'id');
    assert(in_array($testLogId, $ids));
});

test('ActivityLog::countToday()', function () {
    $count = ActivityLog::countToday();
    assert(is_int($count) && $count >= 0);
});

test('ActivityLog::getRecent()', function () use (&$testLogId) {
    $recent = ActivityLog::getRecent(10);
    $ids = array_column($recent, 'id');
    assert(in_array($testLogId, $ids));
});

test('ActivityLog::cleanOld()', function () {
    $deleted = ActivityLog::cleanOld(1);
    assert(is_int($deleted) && $deleted >= 0);
});

// ======================================================
// 25. UserService — full CRUD (requires database)
// ======================================================
$svcUserId = null;
$svcUser = null;

test('UserService::createUser()', function () use (&$svcUserId, &$svcUser) {
    $user = UserService::createUser([
        'username' => 'svc_test_' . uniqid(),
        'password' => 'test1234',
        'email' => 'svc_' . uniqid() . '@test.com',
        'display_name' => 'Service Test',
        'access_level' => 1,
    ], 3);
    assert(isset($user['id']));
    $svcUserId = $user['id'];
    $svcUser = $user;
});

test('UserService::getUser()', function () use (&$svcUserId) {
    $u = UserService::getUser($svcUserId);
    assert($u['id'] == $svcUserId);
});

test('UserService::getAllUsers()', function () {
    $result = UserService::getAllUsers(1, 10);
    assert(isset($result['items']));
    assert(count($result['items']) > 0);
});

test('UserService::searchUsers()', function () {
    $results = UserService::searchUsers('svc_test');
    assert(is_array($results) && count($results) >= 1);
});

test('UserService::updateUser()', function () use (&$svcUserId) {
    $updated = UserService::updateUser($svcUserId, ['display_name' => 'Updated Service User'], 3);
    assert($updated['display_name'] === 'Updated Service User');
});

test('UserService::getAdmins()', function () {
    $admins = UserService::getAdmins();
    assert(is_array($admins));
});

test('UserService::getStats()', function () {
    $stats = UserService::getStats();
    assert(isset($stats['total']));
    assert(isset($stats['users']));
    assert(isset($stats['admins']));
    assert(isset($stats['owners']));
    assert(isset($stats['active_today']));
});

test('UserService::deleteUser()', function () use (&$svcUserId) {
    UserService::deleteUser($svcUserId, 3);
    $thrown = false;
    try { UserService::getUser($svcUserId); } catch (\RuntimeException $e) { $thrown = true; }
    assert($thrown);
});

// ======================================================
// 26. ProductService — full CRUD (requires database)
// ======================================================
$svcProductId = null;

test('ProductService::createProduct()', function () use (&$svcProductId) {
    $p = ProductService::createProduct([
        'name' => 'Service Product ' . uniqid(),
        'price' => 49.99,
        'stock_quantity' => 50,
        '_actor_id' => null,
    ]);
    assert(isset($p['id']));
    $svcProductId = $p['id'];
});

test('ProductService::getProduct()', function () use (&$svcProductId) {
    $p = ProductService::getProduct($svcProductId);
    assert($p['id'] == $svcProductId);
    assert($p['price'] == 49.99);
});

test('ProductService::getAllProducts()', function () {
    $result = ProductService::getAllProducts(1, 10);
    assert(isset($result['items']));
});

test('ProductService::searchProducts()', function () {
    $results = ProductService::searchProducts('Service Product');
    assert(is_array($results));
});

test('ProductService::updateProduct()', function () use (&$svcProductId) {
    $updated = ProductService::updateProduct($svcProductId, ['price' => 39.99, '_actor_id' => null]);
    assert($updated['price'] == 39.99);
});

test('ProductService::updateStock()', function () use (&$svcProductId) {
    ProductService::updateStock($svcProductId, 25);
    $p = ProductService::getProduct($svcProductId);
    assert($p['stock_quantity'] == 25);
});

test('ProductService::getFeatured()', function () {
    $featured = ProductService::getFeatured();
    assert(is_array($featured));
});

test('ProductService::getOnSale()', function () {
    $onSale = ProductService::getOnSale();
    assert(is_array($onSale));
});

test('ProductService::getLowStock()', function () use (&$svcProductId) {
    ProductService::updateStock($svcProductId, 2);
    $low = ProductService::getLowStock(5);
    $ids = array_column($low, 'id');
    assert(in_array($svcProductId, $ids));
});

test('ProductService::getStats()', function () {
    $stats = ProductService::getStats();
    assert(isset($stats['total']));
    assert(isset($stats['active']));
    assert(isset($stats['out_of_stock']));
    assert(isset($stats['on_sale']));
    assert(isset($stats['categories']));
});

test('ProductService::deleteProduct()', function () use (&$svcProductId) {
    ProductService::deleteProduct($svcProductId);
    $thrown = false;
    try { ProductService::getProduct($svcProductId); } catch (\RuntimeException $e) { $thrown = true; }
    assert($thrown);
});

// ======================================================
// 27. ArticleService — full CRUD (requires database)
// ======================================================
$svcArticleId = null;

test('ArticleService::createArticle()', function () use (&$svcArticleId) {
    $a = ArticleService::createArticle([
        'title' => 'Service Article ' . uniqid(),
        'summary' => 'Created via ArticleService',
        '_actor_id' => null,
    ], [
        ['title' => 'Intro', 'section_type' => 'text', 'content' => 'This is the intro section.'],
        ['title' => 'Details', 'section_type' => 'list', 'content' => null, 'list_items' => ['Point 1', 'Point 2']],
    ]);
    assert(isset($a['id']));
    assert(isset($a['sections']));
    assert(count($a['sections']) === 2);
    $svcArticleId = $a['id'];
});

test('ArticleService::getArticle()', function () use (&$svcArticleId) {
    $a = ArticleService::getArticle($svcArticleId);
    assert($a['id'] == $svcArticleId);
    assert(isset($a['toc']));
});

test('ArticleService::getAllArticles()', function () {
    $result = ArticleService::getAllArticles(1, 10);
    assert(isset($result['items']));
});

test('ArticleService::searchArticles()', function () {
    $results = ArticleService::searchArticles('Service Article');
    assert(is_array($results));
});

test('ArticleService::addSection()', function () use (&$svcArticleId) {
    $s = ArticleService::addSection($svcArticleId, ['title' => 'Extra Section', 'section_type' => 'text', 'content' => 'Extra content']);
    assert(isset($s['id']));
});

test('ArticleService::getToc()', function () use (&$svcArticleId) {
    $toc = ArticleService::getToc($svcArticleId);
    assert(count($toc) >= 3);
});

test('ArticleService::publishArticle()', function () use (&$svcArticleId) {
    ArticleService::publishArticle($svcArticleId);
    $a = ArticleService::getArticle($svcArticleId);
    assert(Article::find($svcArticleId)['is_published'] == 1);
});

test('ArticleService::unpublishArticle()', function () use (&$svcArticleId) {
    ArticleService::unpublishArticle($svcArticleId);
    assert(Article::find($svcArticleId)['is_published'] == 0);
});

test('ArticleService::updateArticle()', function () use (&$svcArticleId) {
    $updated = ArticleService::updateArticle($svcArticleId, ['summary' => 'Updated via service'], null);
    assert($updated['summary'] === 'Updated via service');
});

test('ArticleService::getStats()', function () {
    $stats = ArticleService::getStats();
    assert(isset($stats['total']));
    assert(isset($stats['published']));
    assert(isset($stats['drafts']));
    assert(isset($stats['categories']));
    assert(isset($stats['archive']));
});

test('ArticleService::deleteArticle()', function () use (&$svcArticleId) {
    ArticleService::deleteArticle($svcArticleId);
    $thrown = false;
    try { ArticleService::getArticle($svcArticleId); } catch (\RuntimeException $e) { $thrown = true; }
    assert($thrown);
});

// ======================================================
// 28. AuthService — full auth flow (requires database)
// ======================================================
$authUsername = 'auth_test_' . uniqid();
$authPassword = 'securePass123';
$authEmail = 'auth_' . uniqid() . '@test.com';
$authUserId = null;

test('AuthService::register()', function () use (&$authUserId, $authUsername, $authPassword, $authEmail) {
    $user = AuthService::register([
        'username' => $authUsername,
        'password' => $authPassword,
        'email' => $authEmail,
        'display_name' => 'Auth Test User',
    ]);
    assert(isset($user['id']));
    $authUserId = $user['id'];
    assert($user['email'] === $authEmail);
});

test('AuthService::register() — duplicate username throws', function () use ($authUsername, $authPassword, $authEmail) {
    $thrown = false;
    try {
        AuthService::register([
            'username' => $authUsername,
            'password' => $authPassword,
            'email' => 'another_' . uniqid() . '@test.com',
        ]);
    } catch (\InvalidArgumentException $e) { $thrown = true; }
    assert($thrown);
});

test('AuthService::login() — success', function () use ($authUsername, $authPassword, &$authUserId) {
    $user = AuthService::login($authUsername, $authPassword);
    assert($user['username'] === $authUsername);
    assert(AuthService::isLoggedIn() === true);
    assert(AuthService::getCurrentLevel() > 0);
    $current = AuthService::getCurrentUser();
    assert($current !== null && $current['id'] == $user['id']);
});

test('AuthService::login() — invalid credentials throws', function () {
    AuthService::logout();
    $thrown = false;
    try { AuthService::login('nonexistent_user_xx', 'wrongpass'); } catch (\RuntimeException $e) { $thrown = true; }
    assert($thrown);
});

test('AuthService::loginWithCookie() — via session after login', function () use ($authUsername, $authPassword) {
    AuthService::login($authUsername, $authPassword);
    $user = AuthService::loginWithCookie();
    assert($user !== null);
    assert($user['username'] === $authUsername);
});

test('AuthService::changePassword()', function () use (&$authUserId, $authPassword) {
    AuthService::changePassword($authUserId, $authPassword, 'newSecurePass456');
    // Verify by logging in with new password
    AuthService::logout();
    $user = AuthService::login($authUserId < 0 ? 'ignored' : User::find($authUserId)['username'], 'newSecurePass456');
    assert($user !== null);
    assert($user['id'] == $authUserId);
});

test('AuthService::updateProfile()', function () use (&$authUserId) {
    $updated = AuthService::updateProfile($authUserId, ['display_name' => 'Updated Auth User']);
    assert($updated['display_name'] === 'Updated Auth User');
});

test('AuthService::forgotPassword() — valid email', function () use ($authEmail) {
    AuthService::forgotPassword($authEmail);
    $u = User::find(User::findByEmail($authEmail)['id']);
    assert($u['password_reset_token'] !== null);
});

test('AuthService::resetPassword()', function () use (&$authUserId, $authEmail) {
    $u = User::findByEmail($authEmail);
    $token = $u['password_reset_token'];
    AuthService::resetPassword($token, 'resetPass789');
    AuthService::logout();
    $user = AuthService::login($u['username'], 'resetPass789');
    assert($user !== null && $user['id'] == $authUserId);
});

test('AuthService::logout()', function () {
    AuthService::logout();
    assert(AuthService::isLoggedIn() === false);
    assert(AuthService::getCurrentUser() === null);
});

// Cleanup auth test user
test('Cleanup: delete auth test user', function () use (&$authUserId) {
    if ($authUserId) {
        User::deleteRecord($authUserId);
        assert(User::find($authUserId) === null);
    }
});

// ======================================================
// 29. AdminService (requires database)
// ======================================================
$adminServiceTestUserId = null;

test('AdminService::getSystemInfo()', function () {
    $info = AdminService::getSystemInfo();
    assert(isset($info['php_version']));
    assert(isset($info['server_software']));
    assert(isset($info['database']['connected']));
    assert($info['database']['connected'] === true);
});

test('AdminService::getDashboardStats()', function () {
    $stats = AdminService::getDashboardStats();
    assert(isset($stats['users']));
    assert(isset($stats['products']));
    assert(isset($stats['articles']));
    assert(isset($stats['recent_activities']));
});

test('AdminService::getAllUsers()', function () {
    $result = AdminService::getAllUsers(1, 10);
    assert(isset($result['items']));
});

test('AdminService::getAdmins()', function () {
    $admins = AdminService::getAdmins();
    assert(is_array($admins));
});

test('AdminService::getActivityLogs()', function () {
    $logs = AdminService::getActivityLogs(1, 10);
    assert(isset($logs['items']));
    assert(isset($logs['total']));
});

// ======================================================
// 30. SystemService — DB methods
// ======================================================
test('SystemService::customQuery()', function () {
    $result = SystemService::customQuery("SELECT 1 as val");
    assert(is_array($result));
    assert($result[0]['val'] == 1);
});

test('SystemService::customExecute()', function () {
    $affected = SystemService::customExecute("SELECT 1");
    assert(is_int($affected));
});

test('SystemService::paginateRaw()', function () {
    $result = SystemService::paginateRaw('users', 1, 5);
    assert(isset($result['items']));
    assert(isset($result['total']));
    assert($result['page'] === 1);
    assert($result['per_page'] === 5);
});

// ======================================================
// RESULTS
// ======================================================
echo "\n";
echo str_repeat('=', 60) . "\n";
echo "  SMOKE TEST — Complete Library Verification\n";
echo str_repeat('=', 60) . "\n\n";

$total = $passed + $failed + $skipped;
foreach ($tests as $t) {
    switch ($t['status']) {
        case 'PASS':
            echo "  ✅ {$t['label']}\n";
            break;
        case 'FAIL':
            echo "  ❌ {$t['label']}\n";
            echo "     Error: {$t['error']}\n";
            break;
        case 'SKIP':
            echo "  ⏭️  {$t['label']}\n";
            break;
    }
}

echo "\n";
echo str_repeat('-', 60) . "\n";
printf("  Total: %d  |  ✅ Passed: %d  |  ❌ Failed: %d  |  ⏭️  Skipped: %d\n", $total, $passed, $failed, $skipped);
echo str_repeat('-', 60) . "\n";

if ($failed === 0) {
    echo "\n  ✅ ALL TESTS PASSED — Library is working correctly.\n\n";
} else {
    echo "\n  ❌ Some tests FAILED. Review errors above.\n\n";
}

exit($failed > 0 ? 1 : 0);
