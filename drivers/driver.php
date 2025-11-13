<?php
/**
 * Deprecated compatibility shim for driver registration.
 *
 * Notes:
 * - The registration logic has been moved to `/driver_api/register.php`.
 * - This file is kept as a fallback to give a machine-readable deprecation
 *   response for any clients that still POST here.
 * - We intentionally do NOT duplicate the full registration logic here to
 *   avoid divergence and maintenance burden. Keep business logic centralized
 *   in the API folder.
 *
 * Behavior:
 * - For POST requests: return a JSON object indicating the new API location
 *   and an HTTP 410-like informational message. Clients should follow the
 *   new endpoint or use the test form at `../driver_api/register_form.html`.
 * - For non-POST requests: return a small JSON message indicating deprecation.
 *
 * You may delete this file once all clients have migrated. For now we keep
 * it commented and minimal so it's easy to audit.
 */

// Minimal safeguard: do NOT execute registration here. Just respond with JSON.
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Return 200 with a machine-friendly message. Clients should be updated to
    // use /driver_api/register.php. We avoid redirecting because many API
    // clients do not follow HTTP redirects for POST automatically.
    echo json_encode([
        'ok' => false,
        'message' => 'Driver registration has moved to /driver_api/register.php. POST there (multipart/form-data) or use ../driver_api/register_form.html for a test form.'
    ]);
    exit;
}

// Non-POST (GET/other) requests: information only.
echo json_encode([
    'ok' => false,
    'message' => 'This endpoint is deprecated. Use /driver_api/register.php'
]);
exit;
