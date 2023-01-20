<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

// Sanitize SERVER_NAME output
$serverName = htmlspecialchars($_SERVER["SERVER_NAME"]);
// Remove external ipv6 brackets if any
$serverName = preg_replace('/^\[(.*)\]$/', '${1}', $serverName);

// Set landing page location, found within /var/www/html/
$landPage = "../landing.php";

// Define array for hostnames to be accepted as self address for splash page
$authorizedHosts = [ "localhost" ];
if (!empty($_SERVER["FQDN"])) {
    // If setenv.add-environment = ("fqdn" => "true") is configured in lighttpd,
    // append $serverName to $authorizedHosts
    array_push($authorizedHosts, $serverName);
} else if (!empty($_SERVER["VIRTUAL_HOST"])) {
    // Append virtual hostname to $authorizedHosts
    array_push($authorizedHosts, $_SERVER["VIRTUAL_HOST"]);
}

// Determine block page type
if ($serverName === "pi.hole"
    || (!empty($_SERVER["VIRTUAL_HOST"]) && $serverName === $_SERVER["VIRTUAL_HOST"])) {
    // Redirect to Web Interface
    header("Location: /admin");
    exit();
} elseif (filter_var($serverName, FILTER_VALIDATE_IP) || in_array($serverName, $authorizedHosts)) {
    // When directly browsing via IP or authorized hostname
    // Render splash/landing page based off presence of $landPage file
    // Unset variables so as to not be included in $landPage or $splashPage
    unset($authorizedHosts);
    // If $landPage file is present
    if (is_file(getcwd()."/$landPage")) {
        unset($serverName, $viewPort); // unset extra variables not to be included in $landpage
        include $landPage;
        exit();
    }
    // If $landPage file was not present, Set Splash Page output
    $splashPage = <<<EOT
    <!doctype html>
    <html lang='en'>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
            <title>● $serverName</title>
            <link rel='shortcut icon' href='/admin/img/favicons/favicon.ico' type='image/x-icon'>
            <style>
                html, body { height: 100% }
                body { margin: 0; font: 13pt "Source Sans Pro", "Helvetica Neue", Helvetica, Arial, sans-serif; }
                body { background: #222; color: rgba(255, 255, 255, 0.7); text-align: center; }
                p { margin: 0; }
                a { color: #3c8dbc; text-decoration: none; }
                a:hover { color: #72afda; text-decoration: underline; }
                #splashpage { display: flex; align-items: center; justify-content: center; }
                #splashpage img { margin: 5px; width: 256px; }
                #splashpage b { color: inherit; }
            </style>
        </head>
        <body id='splashpage'>
            <div>
            <img src='/admin/img/logo.svg' alt='Pi-hole logo' width='256' height='377'>
            <br>
            <p>Pi-<strong>hole</strong>: Your black hole for Internet advertisements</p>
            <a href='/admin'>Did you mean to go to the admin panel?</a>
            </div>
        </body>
    </html>
EOT;
    exit($splashPage);
}

header("HTTP/1.1 404 Not Found");
exit();
?>
