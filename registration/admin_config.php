<?php
require __DIR__ . '/db.php';

session_start();

// Session timeout - 30 minutes
$timeout = 1800;
if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    session_start();
    header('Location: view_registrations.php');
    exit;
}
$_SESSION['admin_last_activity'] = time();

// Check authentication
if (!isset($_SESSION['admin_authenticated'])) {
    header('Location: view_registrations.php');
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// Handle configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token.';
    } else {
        $deadline = trim($_POST['registration_deadline'] ?? '');
        $capacity = trim($_POST['capacity_limit'] ?? '');
        $enabled = isset($_POST['registration_enabled']) ? '1' : '0';

        // Validate inputs
        if (empty($deadline)) {
            $error = 'Registration deadline is required.';
        } elseif (!strtotime($deadline)) {
            $error = 'Invalid deadline format.';
        } elseif (empty($capacity) || !is_numeric($capacity) || $capacity < 1) {
            $error = 'Capacity must be a positive number.';
        } else {
            // Update configuration
            DB::setConfig('registration_deadline', $deadline);
            DB::setConfig('capacity_limit', $capacity);
            DB::setConfig('registration_enabled', $enabled);
            $success = 'Configuration updated successfully.';
        }
    }
}

// Get current configuration
$deadline = DB::getConfig('registration_deadline');
$capacity = DB::getConfig('capacity_limit');
$enabled = DB::getConfig('registration_enabled') === '1';

// Get statistics
$stats = DB::getStatistics();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
        <meta name="author" content="RAMICS 2026" />
        <link rel="stylesheet" type="text/css" href="../CSS/site.css" title="RAMICS stylesheet" />
        <title>RAMICS 2026 - Admin Configuration</title>
        <style type="text/css">
            .config-form {
                background-color: #ECF6F7;
                padding: 1.5em;
                margin: 1em 0;
                border: 2px solid DodgerBlue;
                max-width: 600px;
            }
            .config-form input[type="text"],
            .config-form input[type="datetime-local"] {
                width: 100%;
                padding: 0.5em;
                margin-top: 0.5em;
                font-size: 100%;
            }
            .button {
                padding: 0.7em 1.5em;
                background-color: DodgerBlue;
                color: white;
                border: none;
                cursor: pointer;
                font-size: 100%;
            }
            .button:hover {
                background-color: #1C86EE;
            }
            .stats-box {
                background-color: #f9f9f9;
                padding: 1em;
                margin: 1em 0;
                border: 1px solid #ccc;
                max-width: 600px;
            }
        </style>
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
            <a href="register.php">Registration Form</a>
            <a href="view_registrations.php">View Registrations</a>
            <a href="?logout=1">Logout</a>
        </div>

        <div class="sideBox LHS">
            <div>Quick Stats</div>
            <span>Total: <?php echo $stats['total']; ?></span>
            <span>Capacity: <?php echo $capacity; ?></span>
            <span>Remaining: <?php echo max(0, $capacity - $stats['total']); ?></span>
        </div>

        <!-- ###### Body Text ###### -->

        <div id="bodyText">

            <h1>Admin Configuration</h1>

            <?php if (!empty($error)): ?>
                <div style="padding: 1em; border: 2px solid red; background-color: #fff0f0; margin-bottom: 1em;">
                    <p style="color: red; margin: 0;"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div style="padding: 1em; border: 2px solid green; background-color: #f0fff0; margin-bottom: 1em;">
                    <p style="color: green; margin: 0;"><strong>Success:</strong> <?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <div class="stats-box">
                <h3 style="margin-top: 0;">Current Status</h3>
                <p><strong>Total Registrations:</strong> <?php echo $stats['total']; ?> / <?php echo $capacity; ?></p>
                <p><strong>Registration Status:</strong>
                    <?php if ($enabled): ?>
                        <span style="color: green;">Open</span>
                    <?php else: ?>
                        <span style="color: red;">Closed</span>
                    <?php endif; ?>
                </p>
                <p><strong>Deadline:</strong> <?php echo htmlspecialchars(date('F j, Y g:i A', strtotime($deadline))); ?></p>
                <p><strong>Time Remaining:</strong>
                    <?php
                    $remaining = strtotime($deadline) - time();
                    if ($remaining > 0) {
                        $days = floor($remaining / 86400);
                        echo "$days day" . ($days !== 1 ? 's' : '');
                    } else {
                        echo '<span style="color: red;">Deadline passed</span>';
                    }
                    ?>
                </p>
            </div>

            <div class="config-form">
                <h3 style="margin-top: 0;">Update Configuration</h3>

                <form method="post" action="admin_config.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

                    <p>
                        <label for="registration_deadline"><strong>Registration Deadline:</strong></label><br />
                        <input type="datetime-local" id="registration_deadline" name="registration_deadline"
                               value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($deadline))); ?>"
                               required="required" />
                    </p>

                    <p>
                        <label for="capacity_limit"><strong>Capacity Limit:</strong></label><br />
                        <input type="text" id="capacity_limit" name="capacity_limit"
                               value="<?php echo htmlspecialchars($capacity); ?>"
                               required="required" />
                        <br />
                        <small>Maximum number of registrations allowed</small>
                    </p>

                    <p>
                        <input type="checkbox" id="registration_enabled" name="registration_enabled"
                               <?php echo $enabled ? 'checked="checked"' : ''; ?> />
                        <label for="registration_enabled"><strong>Registration Open</strong></label>
                        <br />
                        <small>Uncheck to temporarily close registration</small>
                    </p>

                    <p>
                        <input type="submit" value="Update Configuration" class="button" />
                    </p>
                </form>
            </div>

            <div style="padding: 1em; border: 1px solid #ccc; background-color: #f9f9f9; margin-top: 2em; max-width: 600px;">
                <p><strong>Configuration Notes:</strong></p>
                <ul>
                    <li>Changes take effect immediately</li>
                    <li>Closing registration will prevent new submissions</li>
                    <li>Deadline is enforced even if registration is open</li>
                    <li>Capacity can be adjusted even after reaching the limit</li>
                    <li>Use <a href="view_registrations.php">View Registrations</a> to export data</li>
                </ul>
            </div>

        </div>

        <!-- ###### Footer ###### -->

        <div id="footer">
            <div>
                Website design based on <a href="http://www.oswd.org/design/preview/id/1152">Blue Haze</a> by <a href="http://www.oswd.org/user/profile/id/3013">haran</a> from <a href="http://www.oswd.org/">OSWD</a>.  <a href="mailto:ulrich.fahrenberg@polytechnique.edu">Contact</a>
            </div>
        </div>

    </body>

</html>
