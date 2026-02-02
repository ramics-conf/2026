<?php
require __DIR__ . '/db.php';

session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = false;

// Check if admin is already set up and locked
$isLocked = DB::getConfig('admin_setup_locked') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Validate password
        if (empty($password)) {
            $error = 'Password is required.';
        } elseif (strlen($password) < 12) {
            $error = 'Password must be at least 12 characters long.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            // Set password and lock setup
            if (DB::setAdminPassword($password)) {
                DB::setConfig('admin_setup_locked', '1');
                $success = true;
                // Regenerate session ID for security
                session_regenerate_id(true);
            } else {
                $error = 'Failed to set password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
        <meta name="author" content="RAMICS 2026" />
        <link rel="stylesheet" type="text/css" href="../CSS/site.css" title="RAMICS stylesheet" />
        <title>RAMICS 2026 - Admin Setup</title>
    </head>

    <body>

        <!-- ###### Header ###### -->

        <div id="header">
            <span>
                <img src="../figs/impan.jpg" alt="Impan" style="width:8%; height:auto; margin-right:1em;vertical-align: top;"/>
                <img src="../figs/BanachCenter.png" alt="Banach Center" style="width:8%; height:auto;margin-right:5em;vertical-align: top;"/>
            </span>
            <span class="headerTitle">
                <p><a href="../index.html">Relational and Algebraic Methods in Computer Science (RAMICS 2026)</a></p>
                <p>Institute of Mathematics of the Polish Academy of Sciences,
                    Będlewo, Poland, April 07-10, 2026</p>
            </span>
        </div>

        <!-- ###### Side Boxes ###### -->

        <div class="sideBox LHS">
            <div>Navigation</div>
            <a href="../index.html">Home</a>
            <a href="register.php">Registration</a>
        </div>

        <!-- ###### Body Text ###### -->

        <div id="bodyText">

            <h1>Admin Password Setup</h1>

            <?php if ($isLocked): ?>
                <div style="padding: 1em; border: 2px solid DodgerBlue; background-color: #ECF6F7; margin-bottom: 1em;">
                    <p><strong>Setup Complete</strong></p>
                    <p>The admin password has already been set up and this page is now locked.</p>
                    <p><a href="view_registrations.php">Go to Admin Panel</a></p>
                </div>
            <?php elseif ($success): ?>
                <div style="padding: 1em; border: 2px solid green; background-color: #f0fff0; margin-bottom: 1em;">
                    <p><strong>Success!</strong></p>
                    <p>Admin password has been set successfully. This setup page is now locked.</p>
                    <p><a href="view_registrations.php">Go to Admin Panel</a></p>
                </div>
            <?php else: ?>
                <p>This is a one-time setup page. Please set a strong password for the admin panel.</p>

                <?php if (!empty($error)): ?>
                    <div style="padding: 1em; border: 2px solid red; background-color: #fff0f0; margin-bottom: 1em;">
                        <p style="color: red; margin: 0;"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <form method="post" action="setup_admin.php" style="max-width: 500px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

                    <p>
                        <label for="password"><strong>Password:</strong></label><br />
                        <input type="password" id="password" name="password" required="required"
                               style="width: 100%; padding: 0.5em; margin-top: 0.5em; font-size: 100%;" />
                        <br />
                        <small>Minimum 12 characters</small>
                    </p>

                    <p>
                        <label for="confirm_password"><strong>Confirm Password:</strong></label><br />
                        <input type="password" id="confirm_password" name="confirm_password" required="required"
                               style="width: 100%; padding: 0.5em; margin-top: 0.5em; font-size: 100%;" />
                    </p>

                    <p>
                        <input type="submit" value="Set Admin Password"
                               style="padding: 0.7em 1.5em; font-size: 100%; background-color: DodgerBlue;
                                      color: white; border: none; cursor: pointer;" />
                    </p>
                </form>

                <div style="padding: 1em; border: 1px solid #ccc; background-color: #f9f9f9; margin-top: 2em;">
                    <p><strong>Security Notes:</strong></p>
                    <ul>
                        <li>Use a strong password with at least 12 characters</li>
                        <li>Include uppercase, lowercase, numbers, and special characters</li>
                        <li>After setup, this page will be permanently locked</li>
                        <li>Store the password securely - password recovery is not available</li>
                    </ul>
                </div>
            <?php endif; ?>

        </div>

        <!-- ###### Footer ###### -->

        <div id="footer">
            <div>
                Website design based on <a href="http://www.oswd.org/design/preview/id/1152">Blue Haze</a> by <a href="http://www.oswd.org/user/profile/id/3013">haran</a> from <a href="http://www.oswd.org/">OSWD</a>.  <a href="mailto:ulrich.fahrenberg@polytechnique.edu">Contact</a>
            </div>
        </div>

    </body>

</html>
