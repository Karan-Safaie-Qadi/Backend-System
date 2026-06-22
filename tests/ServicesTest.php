<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\UserService;
use App\Services\ProductService;
use App\Services\ArticleService;
use App\Services\AdminService;

class ServicesTest extends TestCase
{
    public function testUserServiceHasMethods()
    {
        $methods = ['getUser', 'getAllUsers', 'searchUsers', 'createUser', 'getStats'];
        foreach ($methods as $m) {
            $this->assertTrue(method_exists(UserService::class, $m), "Missing: $m");
        }
    }

    public function testProductServiceHasMethods()
    {
        $methods = ['createProduct', 'updateProduct', 'deleteProduct', 'getProduct', 'getAllProducts', 'searchProducts', 'getFeatured', 'getStats'];
        foreach ($methods as $m) {
            $this->assertTrue(method_exists(ProductService::class, $m), "Missing: $m");
        }
    }

    public function testArticleServiceHasMethods()
    {
        $methods = ['createArticle', 'updateArticle', 'deleteArticle', 'getArticle', 'getAllArticles', 'searchArticles', 'publishArticle', 'getStats'];
        foreach ($methods as $m) {
            $this->assertTrue(method_exists(ArticleService::class, $m), "Missing: $m");
        }
    }

    public function testAdminServiceHasMethods()
    {
        $methods = ['getDashboardStats', 'getAllUsers', 'getAdmins', 'addAdmin', 'removeAdmin', 'transferOwnership', 'getSystemInfo'];
        foreach ($methods as $m) {
            $this->assertTrue(method_exists(AdminService::class, $m), "Missing: $m");
        }
    }

    public function testGetNonExistentUser()
    {
        $this->expectException(\RuntimeException::class);
        UserService::getUser(99999);
    }
}
