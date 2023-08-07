<?php

session_start();

function validate_csrf_token() {


    // Check if the CSRF token is set in the request
    if (isset($_REQUEST['csrf_token']) && !empty($_REQUEST['csrf_token'])) {
        // Check if the CSRF token matches the one stored in the session
        if ($_REQUEST['csrf_token'] === $_REQUEST['csrf_token']) {
            return true; // CSRF token is valid
        }
    }
    return false; // CSRF token is invalid
}

?>