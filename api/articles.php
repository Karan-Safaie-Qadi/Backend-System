<?php

use App\Services\ArticleService;
use App\Services\AuthService;

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$currentUser = AuthService::getCurrentUser();
$input['_actor_id'] = $currentUser['id'] ?? null;

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        if ($id) {
            $response = ['status' => 'ok', 'data' => ArticleService::getArticle((int)$id)];
        } elseif (isset($_GET['slug'])) {
            $response = ['status' => 'ok', 'data' => ArticleService::getBySlug($_GET['slug'])];
        } elseif (isset($_GET['search'])) {
            $response = ['status' => 'ok', 'data' => ArticleService::searchArticles($_GET['search'])];
        } elseif (isset($_GET['category_id'])) {
            $page = (int)($_GET['page'] ?? 1);
            $response = ['status' => 'ok', 'data' => ArticleService::getByCategory((int)$_GET['category_id'], $page)];
        } elseif (isset($_GET['toc'])) {
            $response = ['status' => 'ok', 'data' => ArticleService::getToc((int)$_GET['toc'])];
        } elseif (isset($_GET['stats'])) {
            $response = ['status' => 'ok', 'data' => ArticleService::getStats()];
        } else {
            $page = (int)($_GET['page'] ?? 1);
            $response = ['status' => 'ok', 'data' => ArticleService::getAllArticles($page)];
        }
        break;

    case 'POST':
        $action = $_GET['action'] ?? 'create';
        switch ($action) {
            case 'create':
                $sections = $input['sections'] ?? [];
                unset($input['sections']);
                $response = ['status' => 'ok', 'data' => ArticleService::createArticle($input, $sections)];
                break;
            case 'publish':
                ArticleService::publishArticle((int)($input['id'] ?? 0));
                $response = ['status' => 'ok', 'message' => 'Article published'];
                break;
            case 'unpublish':
                ArticleService::unpublishArticle((int)($input['id'] ?? 0));
                $response = ['status' => 'ok', 'message' => 'Article unpublished'];
                break;
            case 'add_section':
                $response = ['status' => 'ok', 'data' => ArticleService::addSection((int)($input['article_id'] ?? 0), $input)];
                break;
            default:
                http_response_code(400);
                $response = ['status' => 'error', 'message' => "Unknown action: $action"];
        }
        break;

    case 'PUT':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new \InvalidArgumentException('Article ID is required');
        $sections = $input['sections'] ?? null;
        unset($input['sections']);
        if (isset($input['_sections'])) {
            $sections = $input['_sections'];
            unset($input['_sections']);
        }
        $response = ['status' => 'ok', 'data' => ArticleService::updateArticle($id, $input, $sections)];
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new \InvalidArgumentException('Article ID is required');
        $action = $_GET['action'] ?? '';
        if ($action === 'section') {
            ArticleService::deleteSection($id);
            $response = ['status' => 'ok', 'message' => 'Section deleted'];
        } else {
            ArticleService::deleteArticle($id, $input['_actor_id'] ?? null);
            $response = ['status' => 'ok', 'message' => 'Article deleted'];
        }
        break;

    default:
        http_response_code(405);
        $response = ['status' => 'error', 'message' => 'Method not allowed'];
}
