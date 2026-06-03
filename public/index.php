<?php

// Ejecutar con: php -S localhost:8000 -t public public/index.php


// Cabeceras CORS (útiles para desarrollo local con el index.html)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$uri = rtrim($uri, '/');

// ----------------------------------------------------------------
// POST /api/tiquetes
// ----------------------------------------------------------------
if ($uri === '/api/tiquetes' && $method === 'POST') {
    require __DIR__ . '/api/tiquetes.php';
    return;
}

// ----------------------------------------------------------------
// GET /api/usuarios/{id}/tiquetes
// ----------------------------------------------------------------
if (preg_match('#^/api/usuarios/(\d+)/tiquetes$#', $uri, $matches) && $method === 'GET') {
    $_GET['id'] = $matches[1];
    require __DIR__ . '/api/tiquetes.php';
    return;
}

// ----------------------------------------------------------------
// GET / → servir index.html
// ----------------------------------------------------------------
if ($uri === '' || $uri === '/') {
    readfile(__DIR__ . '/index.html');
    return;
}
