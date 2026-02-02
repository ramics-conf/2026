<?php
session_start();

// Check if registration was successful
if (!isset($_SESSION['registration_success'])) {
    header('Location: register.php');
    exit;
}

$data = $_SESSION['registration_success'];
// Clear the session data
unset($_SESSION['registration_success']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
        <meta name="author" content="RAMICS 2026" />
        <link rel="stylesheet" type="text/css" href="../CSS/site.css" title="RAMICS stylesheet" />
        <title>RAMICS 2026 - Registration Successful</title>
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

        <!-- ###### Body Text ###### -->

        <div id="bodyText">

            <h1>Registration Successful!</h1>

            <div style="padding: 1.5em; border: 2px solid green; background-color: #f0fff0; margin-bottom: 2em;">
                <p style="font-size: 110%; margin: 0;"><strong>Thank you for registering for RAMICS 2026!</strong></p>
            </div>

            <p>Your registration has been received. Please review your submitted information below:</p>

            <div style="background-color: #ECF6F7; padding: 1.5em; margin: 1.5em 0; max-width: 600px;">
                <h3 style="margin-top: 0;">Registration Details</h3>

                <p><strong>Name:</strong> <?php echo htmlspecialchars($data['full_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($data['email']); ?></p>
                <p><strong>Affiliation:</strong> <?php echo htmlspecialchars($data['affiliation']); ?></p>

                <?php if (!empty($data['dietary_requirements'])): ?>
                    <p><strong>Dietary Requirements:</strong> <?php echo htmlspecialchars($data['dietary_requirements']); ?></p>
                <?php endif; ?>

                <?php if ($data['needs_transport']): ?>
                    <p><strong>Transport:</strong> Assistance requested</p>
                    <?php if (!empty($data['arrival_date'])): ?>
                        <p><strong>Arrival Date:</strong> <?php echo htmlspecialchars($data['arrival_date']); ?>
                        <?php if (!empty($data['arrival_time'])): ?>
                            at <?php echo htmlspecialchars($data['arrival_time']); ?>
                        <?php endif; ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($data['needs_invoice']): ?>
                    <p><strong>Invoice:</strong> Requested</p>
                    <?php if (!empty($data['invoice_details'])): ?>
                        <p><strong>Invoice Details:</strong><br />
                        <?php echo nl2br(htmlspecialchars($data['invoice_details'])); ?></p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($data['additional_notes'])): ?>
                    <p><strong>Additional Notes:</strong><br />
                    <?php echo nl2br(htmlspecialchars($data['additional_notes'])); ?></p>
                <?php endif; ?>
            </div>

            <h3>What's Next?</h3>

            <p>The organizing committee will review your registration. If you have any questions or need to make changes to your registration, please contact us at <a href="mailto:ramics2026@easychair.org">ramics2026@easychair.org</a>.</p>

            <div style="padding: 1em; border: 1px solid DodgerBlue; background-color: #ECF6F7; margin-top: 2em; max-width: 600px;">
                <p><strong>Important Information:</strong></p>
                <ul>
                    <li>Conference dates: <strong>April 7-10, 2026</strong></li>
                    <li>Location: Institute of Mathematics of the Polish Academy of Sciences, Będlewo, Poland</li>
                    <li>Keep this email address for future correspondence: <strong><?php echo htmlspecialchars($data['email']); ?></strong></li>
                    <li>Visit our <a href="../index.html">conference website</a> for updates</li>
                </ul>
            </div>

            <p style="margin-top: 2em;">
                <a href="../index.html" style="padding: 0.7em 1.5em; background-color: DodgerBlue; color: white;
                          text-decoration: none; border: none; display: inline-block;">Return to Main Site</a>
            </p>

        </div>

        <!-- ###### Footer ###### -->

        <div id="footer">
            <div>
                Website design based on <a href="http://www.oswd.org/design/preview/id/1152">Blue Haze</a> by <a href="http://www.oswd.org/user/profile/id/3013">haran</a> from <a href="http://www.oswd.org/">OSWD</a>.  <a href="mailto:ulrich.fahrenberg@polytechnique.edu">Contact</a>
            </div>
        </div>

    </body>

</html>
