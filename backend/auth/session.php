<?php
// GET /backend/auth/session.php
require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['user'])) {
    jsonSuccess($_SESSION['user'], 'Session active.');
} else {
    jsonError('No active session.', 401);
}
