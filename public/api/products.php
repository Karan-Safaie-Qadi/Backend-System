<?php

use App\Services\ProductService;
use App\Services\AuthService;

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = AuthService::getCurrentUser();
$input['_actor_id'] = $currentUser['id'] ?? null;

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $response = ['status' => 'ok', 'data' => ProductService::getProduct((int)$_GET['id'])];
        } elseif (isset($_GET['slug'])) {
            $response = ['status' => 'ok', 'data' => ProductService::getBySlug($_GET['slug'])];
        } elseif (isset($_GET['search'])) {
            $response = ['status' => 'ok', 'data' => ProductService::searchProducts($_GET['search'])];
        } elseif (isset($_GET['featured'])) {
            $response = ['status' => 'ok', 'data' => ProductService::getFeatured()];
        } elseif (isset($_GET['on_sale'])) {
            $response = ['status' => 'ok', 'data' => ProductService::getOnSale()];
        } elseif (isset($_GET['low_stock'])) {
            $response = ['status' => 'ok', 'data' => ProductService::getLowStock((int)($_GET['threshold'] ?? 10))];
        } elseif (isset($_GET['category_id'])) {
            $response = ['status' => 'ok', 'data' => ProductService::getByCategory((int)$_GET['category_id'], (int)($_GET['page'] ?? 1))];
        } elseif (isset($_GET['stats'])) {
            $response = ['status' => 'ok', 'data' => ProductService::getStats()];
        } else {
            $response = ['status' => 'ok', 'data' => ProductService::getAllProducts((int)($_GET['page'] ?? 1))];
        }
        break;
    case 'POST':
        $response = ['status' => 'ok', 'message' => 'Product created', 'data' => ProductService::createProduct($input)];
        break;
    case 'PUT':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new \InvalidArgumentException('Product ID required');
        $response = ['status' => 'ok', 'data' => ProductService::updateProduct($id, $input)];
        break;
    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new \InvalidArgumentException('Product ID required');
        ProductService::deleteProduct($id, $input['_actor_id'] ?? null);
        $response = ['status' => 'ok', 'message' => 'Product deleted'];
        break;
    default:
        http_response_code(405);
        $response = ['status' => 'error', 'message' => 'Method not allowed'];
}
