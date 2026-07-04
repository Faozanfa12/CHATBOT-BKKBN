<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// =============================================================
// Chatbot Routes (Paste kodenya di sini)
// =============================================================
$routes->get('/', 'Chatbot::index');
$routes->get('/faq_tree', 'Chatbot::faq_tree');
$routes->post('/get', 'Chatbot::getResponse');
$routes->get('/job/(:segment)', 'Chatbot::jobStatus/$1');
$routes->post('/tts', 'Chatbot::tts');
// =============================================================

// =============================================================
// ROUTE ADMIN PANEL
// =============================================================
$routes->group('admin', function ($routes) {
    $routes->get('/', 'Admin::index');          // Halaman List
    $routes->get('create', 'Admin::create');    // Halaman Tambah
    $routes->post('store', 'Admin::store');     // Proses Simpan
    $routes->get('edit/(:num)', 'Admin::edit/$1');    // Halaman Edit
    $routes->post('update/(:num)', 'Admin::update/$1'); // Proses Update
    $routes->get('delete/(:num)', 'Admin::delete/$1');  // Proses Hapus
});
// =============================================================

// Route Login & Logout
$routes->get('/login', 'Login::index');
$routes->post('/login/auth', 'Login::auth');
$routes->get('/logout', 'Login::logout');

// =============================================================
// ADMIN PANEL (DILINDUNGI PASSWORD)
// =============================================================
// Perhatikan ada ['filter' => 'auth'] <- Ini gemboknya!
$routes->group('admin', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Admin::index');
    $routes->get('create', 'Admin::create');
    $routes->post('store', 'Admin::store');
    $routes->get('edit/(:num)', 'Admin::edit/$1');
    $routes->post('update/(:num)', 'Admin::update/$1');
    $routes->get('delete/(:num)', 'Admin::delete/$1');
});

// Tambahkan ini agar fungsi cek_model bisa dibuka
$routes->get('cek-model', 'Chatbot::cekModel');