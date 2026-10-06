<?php
declare(strict_types=1);

/**
 * EXAMPLE configuration — copy this file to config.php and edit locally.
 *
 *   copy config.example.php config.php
 *
 * Do NOT commit config.php (it may contain secrets / LAN IP / production URLs).
 */
return [
    'app' => [
        'name' => 'FORMA',
        'tagline' => 'See it before you buy',
        'timezone' => 'Asia/Kuala_Lumpur',

        // Production (cPanel): set your public HTTPS root, no trailing slash.
        // Example: 'https://yourdomain.com/Web-based_AR_furniture'
        'base_url' => '',

        // Optional QR-only public URL (defaults to base_url).
        'public_url' => '',

        // Optional LAN IP for local phone testing on XAMPP (same Wi‑Fi).
        // Example: '192.168.0.15' — leave empty to auto-detect.
        'lan_ip' => '',
    ],
    'db' => [
        'host' => 'localhost',
        'name' => 'ar_furniture',
        'user' => 'root',
        'pass' => '',          // set your MySQL password locally / on cPanel
        'charset' => 'utf8mb4',
    ],
    'upload' => [
        'max_image_bytes' => 5 * 1024 * 1024,
        'max_model_bytes' => 50 * 1024 * 1024,
        'allowed_images' => ['jpg', 'jpeg', 'png', 'webp'],
        'allowed_models' => ['glb', 'gltf', 'usdz'],
    ],
    'currency_default' => 'RM',
];
