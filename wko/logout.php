<?php
/**
 * LSB Work Order System - Logout
 */

require_once __DIR__ . '/includes/wo_auth.php';

wo_logout();

header('Location: login.php');
exit;
