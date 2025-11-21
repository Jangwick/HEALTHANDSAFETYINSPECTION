<?php

/**
 * Router for PHP Built-in Server
 * This file handles routing for the development server
 */

// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly
if (preg_match('/\.(?:png|jpg|jpeg|gif|ico|css|js|svg|woff|woff2|ttf)$/', $uri)) {
    return false; // Let the server handle static files
}

// Route all API requests and non-static requests to index.php
require_once __DIR__ . '/index.php';
