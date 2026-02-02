<?php
require __DIR__ . '/db.php';

session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        // Store form data for repopulation on error
        $formData = $_POST;

        // Check if registration is enabled
        if (DB::getConfig('registration_enabled') !== '1') {
            $error = 'Registration is currently closed.';
        }
        // Check deadline
        elseif (strtotime(DB::getConfig('registration_deadline')) < time()) {
            $error = 'Registration deadline has passed.';
        }
        // Check capacity
        elseif (DB::getRegistrationCount() >= (int)DB::getConfig('capacity_limit')) {
            $error = 'Registration capacity has been reached. Please contact the organizers.';
        }
        // Check rate limit
        elseif (!DB::checkRateLimit($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 3, 3600)) {
            $error = 'Too many registration attempts. Please try again later.';
        }
        // Validate required fields
        elseif (empty($_POST['full_name']) || empty($_POST['email']) || empty($_POST['affiliation'])) {
            $error = 'Please fill in all required fields (Name, Email, Affiliation).';
        }
        // Validate email format
        elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        }
        // Check for duplicate email
        elseif (DB::emailExists($_POST['email'])) {
            $error = 'This email address is already registered.';
        }
        // If needs invoice but no details provided
        elseif (isset($_POST['needs_invoice']) && empty(trim($_POST['invoice_details']))) {
            $error = 'Please provide invoice details (company name, VAT number, address).';
        } else {
            // All validations passed, insert registration
            try {
                $registrationData = [
                    'full_name' => trim($_POST['full_name']),
                    'email' => trim($_POST['email']),
                    'affiliation' => trim($_POST['affiliation']),
                    'dietary_requirements' => trim($_POST['dietary_requirements'] ?? ''),
                    'needs_transport' => isset($_POST['needs_transport']),
                    'arrival_date' => trim($_POST['arrival_date'] ?? ''),
                    'arrival_time' => trim($_POST['arrival_time'] ?? ''),
                    'needs_invoice' => isset($_POST['needs_invoice']),
                    'invoice_details' => trim($_POST['invoice_details'] ?? ''),
                    'additional_notes' => trim($_POST['additional_notes'] ?? '')
                ];

                if (DB::insertRegistration($registrationData)) {
                    // Store registration data in session for confirmation page
                    $_SESSION['registration_success'] = $registrationData;
                    // Redirect to success page
                    header('Location: registration_success.php');
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            } catch (Exception $e) {
                $error = 'An error occurred. Please try again.';
            }
        }
    }
}

// Get current stats
$currentCount = DB::getRegistrationCount();
$capacityLimit = (int)DB::getConfig('capacity_limit');
$deadline = DB::getConfig('registration_deadline');
$spotsRemaining = $capacityLimit - $currentCount;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
        <meta name="author" content="RAMICS 2026" />
        <link rel="stylesheet" type="text/css" href="../CSS/site.css" title="RAMICS stylesheet" />
        <title>RAMICS 2026 - Registration</title>
        <script type="text/javascript">
            function toggleInvoiceDetails() {
                var checkbox = document.getElementById('needs_invoice');
                var details = document.getElementById('invoice_details_section');
                details.style.display = checkbox.checked ? 'block' : 'none';
            }
        </script>
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
            <div>Contents</div>
            <a href="../index.html">Home</a>
            <a href="../index.html#invited">Invited speakers</a>
            <a href="../index.html#venue">Venue</a>
            <a href="../index.html#history">History</a>
            <a href="../index.html#sponsors">Sponsors</a>
            <a href="mailto:ramics2026@easychair.org">Contact</a>
        </div>

        <div class="sideBox LHS">
            <div>Registration</div>
            <span>Deadline: <?php echo htmlspecialchars(date('F j, Y', strtotime($deadline))); ?></span>
            <span>Spots remaining: <?php echo htmlspecialchars($spotsRemaining); ?>/<?php echo htmlspecialchars($capacityLimit); ?></span>
        </div>

        <!-- ###### Body Text ###### -->

        <div id="bodyText">

            <h1>Conference Registration</h1>

            <p>Please complete the form below to register for RAMICS 2026.</p>

            <?php if (!empty($error)): ?>
                <div style="padding: 1em; border: 2px solid red; background-color: #fff0f0; margin-bottom: 1em;">
                    <p style="color: red; margin: 0;"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" style="max-width: 700px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

                <h3>Personal Information</h3>

                <p>
                    <label for="full_name"><strong>Full Name:</strong> <span style="color: red;">*</span></label><br />
                    <input type="text" id="full_name" name="full_name" required="required"
                           value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>"
                           style="width: 100%; padding: 0.5em; margin-top: 0.5em; font-size: 100%;" />
                </p>

                <p>
                    <label for="email"><strong>Email Address:</strong> <span style="color: red;">*</span></label><br />
                    <input type="email" id="email" name="email" required="required"
                           value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                           style="width: 100%; padding: 0.5em; margin-top: 0.5em; font-size: 100%;" />
                </p>

                <p>
                    <label for="affiliation"><strong>Affiliation:</strong> <span style="color: red;">*</span></label><br />
                    <input type="text" id="affiliation" name="affiliation" required="required"
                           value="<?php echo htmlspecialchars($formData['affiliation'] ?? ''); ?>"
                           style="width: 100%; padding: 0.5em; margin-top: 0.5em; font-size: 100%;" />
                    <br />
                    <small>University, company, or institution</small>
                </p>

                <h3>Travel and Accommodation</h3>

                <p>
                    <label for="dietary_requirements"><strong>Dietary Requirements:</strong></label><br />
                    <input type="text" id="dietary_requirements" name="dietary_requirements"
                           value="<?php echo htmlspecialchars($formData['dietary_requirements'] ?? ''); ?>"
                           style="width: 100%; padding: 0.5em; margin-top: 0.5em; font-size: 100%;" />
                    <br />
                    <small>E.g., vegetarian, vegan, gluten-free, allergies</small>
                </p>

                <p>
                    <input type="checkbox" id="needs_transport" name="needs_transport"
                           <?php echo isset($formData['needs_transport']) ? 'checked="checked"' : ''; ?> />
                    <label for="needs_transport"><strong>I need transport assistance from the airport/train station</strong></label>
                </p>

                <p>
                    <label for="arrival_date"><strong>Expected Arrival Date:</strong></label><br />
                    <input type="date" id="arrival_date" name="arrival_date"
                           value="<?php echo htmlspecialchars($formData['arrival_date'] ?? ''); ?>"
                           style="padding: 0.5em; margin-top: 0.5em; font-size: 100%;" />
                </p>

                <p>
                    <label for="arrival_time"><strong>Expected Arrival Time:</strong></label><br />
                    <input type="time" id="arrival_time" name="arrival_time"
                           value="<?php echo htmlspecialchars($formData['arrival_time'] ?? ''); ?>"
                           style="padding: 0.5em; margin-top: 0.5em; font-size: 100%;" />
                </p>

                <h3>Invoicing</h3>

                <p>
                    <input type="checkbox" id="needs_invoice" name="needs_invoice"
                           onclick="toggleInvoiceDetails()"
                           <?php echo isset($formData['needs_invoice']) ? 'checked="checked"' : ''; ?> />
                    <label for="needs_invoice"><strong>I need an invoice</strong></label>
                </p>

                <div id="invoice_details_section" style="display: <?php echo isset($formData['needs_invoice']) ? 'block' : 'none'; ?>;">
                    <p>
                        <label for="invoice_details"><strong>Invoice Details:</strong></label><br />
                        <textarea id="invoice_details" name="invoice_details" rows="5"
                                  style="width: 100%; padding: 0.5em; margin-top: 0.5em; font-size: 100%;"><?php echo htmlspecialchars($formData['invoice_details'] ?? ''); ?></textarea>
                        <br />
                        <small>Please provide: Company/Institution name, VAT number (if applicable), billing address</small>
                    </p>
                </div>

                <h3>Additional Information</h3>

                <p>
                    <label for="additional_notes"><strong>Additional Notes:</strong></label><br />
                    <textarea id="additional_notes" name="additional_notes" rows="4"
                              style="width: 100%; padding: 0.5em; margin-top: 0.5em; font-size: 100%;"><?php echo htmlspecialchars($formData['additional_notes'] ?? ''); ?></textarea>
                    <br />
                    <small>Any other information you would like to share with the organizers</small>
                </p>

                <p style="margin-top: 2em;">
                    <span style="color: red;">*</span> Required fields
                </p>

                <p>
                    <input type="submit" value="Submit Registration"
                           style="padding: 0.7em 1.5em; font-size: 110%; background-color: DodgerBlue;
                                  color: white; border: none; cursor: pointer;" />
                </p>

            </form>

            <div style="padding: 1em; border: 1px solid #ccc; background-color: #f9f9f9; margin-top: 2em; max-width: 700px;">
                <p><strong>Important Notes:</strong></p>
                <ul>
                    <li>You will receive confirmation details on the screen after submitting the form</li>
                    <li>Registration deadline: <?php echo htmlspecialchars(date('F j, Y', strtotime($deadline))); ?></li>
                    <li>Limited to <?php echo htmlspecialchars($capacityLimit); ?> participants</li>
                    <li>For questions, contact <a href="mailto:ramics2026@easychair.org">ramics2026@easychair.org</a></li>
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
