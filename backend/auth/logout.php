<?php
// GET /backend/auth/logout.php
require_once __DIR__ . '/../config.php';

$_SESSION = [];
session_destroy();
jsonSuccess([], 'You have been logged out successfully.');
