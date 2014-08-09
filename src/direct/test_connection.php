<?php
/**
 * This file is used to test the cURL connection from the server to the server.
 * It is required to jump from Mouf context to the application context
 */

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';

echo "ok";