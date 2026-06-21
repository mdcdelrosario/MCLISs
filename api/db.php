<?php
/**
 * API database connection — reuses mclis/conn.php (db_cdms).
 * Skips the web-app login redirect by faking a login URI before include.
 */
if (!isset($conn)) {
    $_SERVER['REQUEST_URI'] = '/login';
    require_once dirname(__DIR__) . '/conn.php';
}
