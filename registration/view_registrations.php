<?php
require __DIR__ . '/db.php';

session_start();

// Session timeout - 30 minutes
$timeout = 1800;
if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['admin_message'] = 'Session expired. Please login again.';
}
$_SESSION['admin_last_activity'] = time();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$message = '';

// Check for session message
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: view_registrations.php');
    exit;
}

// Handle CSV export
if (isset($_GET['export']) && isset($_SESSION['admin_authenticated'])) {
    $filters = $_SESSION['admin_filters'] ?? [];
    $csv = DB::exportToCSV($filters);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ramics2026_registrations_' . date('Y-m-d') . '.csv"');
    echo $csv;
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token.';
    } else {
        $password = $_POST['password'] ?? '';
        if (DB::verifyAdminPassword($password)) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_last_activity'] = time();
            session_regenerate_id(true);
            header('Location: view_registrations.php');
            exit;
        } else {
            $error = 'Invalid password.';
        }
    }
}

// Handle search/filter
$filters = [];
if (isset($_SESSION['admin_authenticated']) && isset($_POST['search'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token.';
    } else {
        $filters = [
            'name' => trim($_POST['filter_name'] ?? ''),
            'email' => trim($_POST['filter_email'] ?? ''),
            'affiliation' => trim($_POST['filter_affiliation'] ?? ''),
            'needs_transport' => $_POST['filter_transport'] ?? '',
            'needs_invoice' => $_POST['filter_invoice'] ?? '',
            'date_from' => trim($_POST['filter_date_from'] ?? ''),
            'date_to' => trim($_POST['filter_date_to'] ?? '')
        ];
        $_SESSION['admin_filters'] = $filters;
    }
} elseif (isset($_SESSION['admin_authenticated']) && isset($_POST['clear_filters'])) {
    $_SESSION['admin_filters'] = [];
    $filters = [];
} elseif (isset($_SESSION['admin_authenticated']) && isset($_SESSION['admin_filters'])) {
    $filters = $_SESSION['admin_filters'];
}

// Get registrations
$registrations = [];
$stats = [];
if (isset($_SESSION['admin_authenticated'])) {
    $registrations = empty($filters) ? DB::getAllRegistrations() : DB::searchRegistrations($filters);
    $stats = DB::getStatistics();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
        <meta name="author" content="RAMICS 2026" />
        <link rel="stylesheet" type="text/css" href="../CSS/site.css" title="RAMICS stylesheet" />
        <title>RAMICS 2026 - Admin Panel</title>
        <style type="text/css">
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 1em 0;
                font-size: 90%;
            }
            th, td {
                border: 1px solid #ccc;
                padding: 0.5em;
                text-align: left;
            }
            th {
                background-color: #ECF6F7;
                color: DodgerBlue;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .stats-box {
                background-color: #ECF6F7;
                padding: 1em;
                margin: 1em 0;
                border: 2px solid DodgerBlue;
            }
            .filter-form {
                background-color: #f9f9f9;
                padding: 1em;
                margin: 1em 0;
                border: 1px solid #ccc;
            }
            .filter-form input, .filter-form select {
                padding: 0.3em;
                margin: 0.2em 0;
            }
            .button {
                padding: 0.5em 1em;
                background-color: DodgerBlue;
                color: white;
                border: none;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                margin: 0.2em;
            }
            .button:hover {
                background-color: #1C86EE;
            }
            .button-secondary {
                background-color: #666;
            }
            .button-secondary:hover {
                background-color: #555;
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
            <?php if (isset($_SESSION['admin_authenticated'])): ?>
                <a href="admin_config.php">Configuration</a>
                <a href="?logout=1">Logout</a>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['admin_authenticated'])): ?>
        <div class="sideBox LHS">
            <div>Quick Stats</div>
            <span>Total: <?php echo $stats['total']; ?></span>
            <span>Transport: <?php echo $stats['transport']; ?></span>
            <span>Invoice: <?php echo $stats['invoice']; ?></span>
        </div>
        <?php endif; ?>

        <!-- ###### Body Text ###### -->

        <div id="bodyText">

            <h1>Registration Management</h1>

            <?php if (!empty($error)): ?>
                <div style="padding: 1em; border: 2px solid red; background-color: #fff0f0; margin-bottom: 1em;">
                    <p style="color: red; margin: 0;"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div style="padding: 1em; border: 2px solid DodgerBlue; background-color: #ECF6F7; margin-bottom: 1em;">
                    <p style="margin: 0;"><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['admin_authenticated'])): ?>
                <!-- Login Form -->
                <div style="max-width: 400px;">
                    <p>Please enter the admin password to view registrations.</p>

                    <form method="post" action="view_registrations.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

                        <p>
                            <label for="password"><strong>Password:</strong></label><br />
                            <input type="password" id="password" name="password" required="required"
                                   style="width: 100%; padding: 0.5em; margin-top: 0.5em; font-size: 100%;" />
                        </p>

                        <p>
                            <input type="submit" name="login" value="Login" class="button" />
                        </p>
                    </form>
                </div>

            <?php else: ?>
                <!-- Admin Dashboard -->

                <div class="stats-box">
                    <h3 style="margin-top: 0;">Registration Statistics</h3>
                    <p><strong>Total Registrations:</strong> <?php echo $stats['total']; ?></p>
                    <p><strong>Transport Assistance Needed:</strong> <?php echo $stats['transport']; ?></p>
                    <p><strong>Invoices Requested:</strong> <?php echo $stats['invoice']; ?></p>

                    <?php if (!empty($stats['dietary'])): ?>
                        <p><strong>Dietary Requirements:</strong></p>
                        <ul>
                        <?php foreach ($stats['dietary'] as $diet): ?>
                            <li><?php echo htmlspecialchars($diet['dietary_requirements']); ?>: <?php echo $diet['count']; ?></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Filter Form -->
                <div class="filter-form">
                    <h3 style="margin-top: 0;">Search / Filter</h3>
                    <form method="post" action="view_registrations.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

                        <table style="border: none;">
                            <tr style="background-color: transparent;">
                                <td style="border: none;">
                                    <label for="filter_name">Name:</label><br />
                                    <input type="text" id="filter_name" name="filter_name"
                                           value="<?php echo htmlspecialchars($filters['name'] ?? ''); ?>" />
                                </td>
                                <td style="border: none;">
                                    <label for="filter_email">Email:</label><br />
                                    <input type="text" id="filter_email" name="filter_email"
                                           value="<?php echo htmlspecialchars($filters['email'] ?? ''); ?>" />
                                </td>
                                <td style="border: none;">
                                    <label for="filter_affiliation">Affiliation:</label><br />
                                    <input type="text" id="filter_affiliation" name="filter_affiliation"
                                           value="<?php echo htmlspecialchars($filters['affiliation'] ?? ''); ?>" />
                                </td>
                            </tr>
                            <tr style="background-color: transparent;">
                                <td style="border: none;">
                                    <label for="filter_transport">Transport:</label><br />
                                    <select id="filter_transport" name="filter_transport">
                                        <option value="">All</option>
                                        <option value="1" <?php echo ($filters['needs_transport'] ?? '') === '1' ? 'selected="selected"' : ''; ?>>Yes</option>
                                        <option value="0" <?php echo ($filters['needs_transport'] ?? '') === '0' ? 'selected="selected"' : ''; ?>>No</option>
                                    </select>
                                </td>
                                <td style="border: none;">
                                    <label for="filter_invoice">Invoice:</label><br />
                                    <select id="filter_invoice" name="filter_invoice">
                                        <option value="">All</option>
                                        <option value="1" <?php echo ($filters['needs_invoice'] ?? '') === '1' ? 'selected="selected"' : ''; ?>>Yes</option>
                                        <option value="0" <?php echo ($filters['needs_invoice'] ?? '') === '0' ? 'selected="selected"' : ''; ?>>No</option>
                                    </select>
                                </td>
                                <td style="border: none;">
                                    <label for="filter_date_from">Date From:</label><br />
                                    <input type="date" id="filter_date_from" name="filter_date_from"
                                           value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>" />
                                </td>
                            </tr>
                            <tr style="background-color: transparent;">
                                <td style="border: none;">
                                    <label for="filter_date_to">Date To:</label><br />
                                    <input type="date" id="filter_date_to" name="filter_date_to"
                                           value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>" />
                                </td>
                                <td colspan="2" style="border: none; vertical-align: bottom;">
                                    <input type="submit" name="search" value="Apply Filters" class="button" />
                                    <input type="submit" name="clear_filters" value="Clear Filters" class="button button-secondary" />
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>

                <!-- Export Button -->
                <p>
                    <a href="?export=1" class="button">Export to CSV</a>
                    <span style="margin-left: 1em; font-size: 90%;">
                        (<?php echo count($registrations); ?> record<?php echo count($registrations) !== 1 ? 's' : ''; ?>)
                    </span>
                </p>

                <!-- Registrations Table -->
                <?php if (empty($registrations)): ?>
                    <p><em>No registrations found.</em></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Affiliation</th>
                                <th>Dietary</th>
                                <th>Transport</th>
                                <th>Arrival</th>
                                <th>Invoice</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reg['id']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['affiliation']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['dietary_requirements']); ?></td>
                                    <td><?php echo $reg['needs_transport'] ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <?php
                                        if (!empty($reg['arrival_date'])) {
                                            echo htmlspecialchars($reg['arrival_date']);
                                            if (!empty($reg['arrival_time'])) {
                                                echo ' ' . htmlspecialchars($reg['arrival_time']);
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $reg['needs_invoice'] ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($reg['registration_date']))); ?></td>
                                </tr>
                                <?php if (!empty($reg['invoice_details']) || !empty($reg['additional_notes'])): ?>
                                    <tr style="background-color: #fffacd;">
                                        <td colspan="9" style="font-size: 85%;">
                                            <?php if (!empty($reg['invoice_details'])): ?>
                                                <strong>Invoice Details:</strong> <?php echo nl2br(htmlspecialchars($reg['invoice_details'])); ?><br />
                                            <?php endif; ?>
                                            <?php if (!empty($reg['additional_notes'])): ?>
                                                <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($reg['additional_notes'])); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

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
