
<?php

try {
    $conn = new PDO(
        "sqlsrv:Server=localhost;Database=db_cdms;Encrypt=false;TrustServerCertificate=true",
        "sa",
        "Admin@1234"
    );

    echo "Connected";

} catch (PDOException $e) {
    die($e->getMessage());
}


phpinfo();
?>