<?php
// create_admin_user.php
// Usage (visit in browser): http://localhost/latest/create_admin_user.php
// or run: php create_admin_user.php
//
// IMPORTANT:
// - This generates the encrypted `pass` using the same safeEncrypt() as the app.
// - Output is printed as SQL you can copy/paste into SQL Server.
//
// For security, you should protect this script (or delete it after use).

require __DIR__ . '/js/fphp.php';

define('APP_ADMIN_USERNAME', 'admin01');
define('APP_ADMIN_PASSWORD_PLAINTEXT', 'admin1234');

$username = APP_ADMIN_USERNAME;
$password = APP_ADMIN_PASSWORD_PLAINTEXT;

$encryptedPass = safeEncrypt($password);

header('Content-Type: text/plain; charset=utf-8');

echo "-- Generated for admin user (MCLIS)
";
echo "-- user: {$username}
";
echo "-- plaintext password: (not shown)
";
echo "-- encrypted pass: {$encryptedPass}

";

echo "-- OPTION A: INSERT if admin01 does NOT exist
";
echo "INSERT INTO tbl_users ([user], pass, acctlvl, ini, fname, lname, logdate, [restrict])\n";
echo "VALUES ('" . str_replace("'", "''", $username) . "', '" . str_replace("'", "''", $encryptedPass) . "', 'admin', 'A1', '', '', NULL, NULL);\n\n";

echo "-- OPTION B: UPDATE if admin01 ALREADY exists
";
echo "UPDATE tbl_users\n";
echo "SET acctlvl='admin', pass='" . str_replace("'", "''", $encryptedPass) . "'\n";
echo "WHERE [user]='" . str_replace("'", "''", $username) . "';\n\n";

echo "-- Ensure rights row exists for admin level
";
echo "-- (If you already have tbl_userrights.level='admin', switch INSERT to UPDATE.)\n";
echo "INSERT INTO tbl_userrights (\n";
echo "  level,\n";
echo "  search_customer, search_elements, is_crew, main_area, central_area, northern_area, eastern_area, southeastern_area, southern_area,\n";
echo "  edit_locations, edit_metering_data, edit_complaints_request,\n";
echo "  utilities_user, utilities_vehicle, utilities_personnel, utilities_complaints_request,\n";
echo "  download_master_data, download_complaints_request_data, download_cust_brgy, download_cust_town\n";
echo ")\n";
echo "VALUES (\n";
echo "  'admin',\n";
echo "  1,1,1,1,1,1,1,1,1,\n";
echo "  1,1,1,\n";
echo "  1,1,1,1,\n";
echo "  1,1,1,1\n";
echo ");\n";

