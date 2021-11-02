<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

    require_once('func.php');

    // Start a new PHP session (or continue an existing one)
    // Prevents javascript XSS attacks aimed to steal the session ID
    ini_set('session.cookie_httponly', 1);
    // Prevent Session ID from being passed through URLs
    ini_set('session.use_only_cookies', 1);
    session_start();

    // Read setupVars.conf file
    $setupVars = parse_ini_file("/etc/pihole/setupVars.conf");
    // Try to read password hash from setupVars.conf
    if(isset($setupVars['WEBPASSWORD']))
    {
        $pwhash = $setupVars['WEBPASSWORD'];
    }
    else
    {
        $pwhash = "";
    }

    // If the user wants to log out, we free all session variables currently registered
    // and delete any persistent cookie.
    if(isset($_GET["logout"]))
    {
        session_unset();
        setcookie('persistentlogin', '', 1);
        header('Location: index.php');
        exit();
    }

    $wrongpassword = false;
    $auth = false;

    // Test if password is set
    if(strlen($pwhash) > 0)
    {
        // Check for and authorize from persistent cookie
        if (isset($_COOKIE["persistentlogin"]))
        {
            if (hash_equals($pwhash, $_COOKIE["persistentlogin"]))
            {
                $auth = true;
                // Refresh cookie with new expiry
                // setcookie( $name, $value, $expire, $path, $domain, $secure, $httponly )
                setcookie('persistentlogin', $pwhash, time()+60*60*24*7, null, null, null, true );
            }
            else
            {
                // Invalid cookie
                $auth = false;
                setcookie('persistentlogin', '', 1);
            }
        }
        // Compare doubly hashes password input with saved hash
        else if(isset($_POST["pw"]))
        {
            $postinput = hash('sha256',hash('sha256',$_POST["pw"]));
            if(hash_equals($pwhash, $postinput))
            {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id();

                // Clear the old session
                $_SESSION = array();

                // Set hash in new session
                $_SESSION["hash"] = $pwhash;

                // Set persistent cookie if selected
                if (isset($_POST['persistentlogin']))
                {
                    // setcookie( $name, $value, $expire, $path, $domain, $secure, $httponly )
                    setcookie('persistentlogin', $pwhash, time()+60*60*24*7, null, null, null, true );
                }

                // Login successful, redirect the user to the homepage to discard the POST request
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['QUERY_STRING'] === 'login') {
                    header('Location: index.php');
                    exit();
                }

                $auth = true;
            }
            else
            {
                $wrongpassword = true;
            }
        }
        // Compare auth hash with saved hash
        else if (isset($_SESSION["hash"]))
        {
            if(hash_equals($pwhash, $_SESSION["hash"]))
                $auth = true;
        }
        // API can use the hash to get data without logging in via plain-text password
        else if (isset($api) && isset($_GET["auth"]))
        {
            if(hash_equals($pwhash, $_GET["auth"]))
                $auth = true;
        }
        else
        {
            // Password or hash wrong
            $auth = false;
        }
    }
    else
    {
        // No password set
        $auth = true;
    }
?>
