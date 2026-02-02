<?php

class DB {

    const DB_PATH = __DIR__ . '/data/registrations.db';
    private static $connection = null;

    /**
     * Initialize database and create tables if they don't exist
     */
    static function init() {
        $db = self::getConnection();

        // Create registrations table
        $db->exec("CREATE TABLE IF NOT EXISTS registrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            affiliation TEXT NOT NULL,
            dietary_requirements TEXT,
            needs_transport INTEGER DEFAULT 0,
            arrival_date TEXT,
            arrival_time TEXT,
            needs_invoice INTEGER DEFAULT 0,
            invoice_details TEXT,
            additional_notes TEXT,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address TEXT
        )");

        // Create config table
        $db->exec("CREATE TABLE IF NOT EXISTS config (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Create rate_limit table for tracking registration attempts
        $db->exec("CREATE TABLE IF NOT EXISTS rate_limit (
            ip_address TEXT PRIMARY KEY,
            attempt_count INTEGER DEFAULT 1,
            first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Insert default config values if not present
        $defaults = [
            'registration_deadline' => '2026-03-15 23:59:59',
            'capacity_limit' => '50',
            'registration_enabled' => '1',
            'admin_password_hash' => '',
            'admin_setup_locked' => '0'
        ];

        foreach ($defaults as $key => $value) {
            $stmt = $db->prepare("INSERT OR IGNORE INTO config (key, value) VALUES (:key, :value)");
            $stmt->execute([':key' => $key, ':value' => $value]);
        }

        return true;
    }

    /**
     * Get PDO connection to database
     */
    static function getConnection() {
        if (self::$connection === null) {
            // Ensure data directory exists
            $dataDir = dirname(self::DB_PATH);
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }

            self::$connection = new PDO('sqlite:' . self::DB_PATH);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return self::$connection;
    }

    /**
     * Insert new registration
     */
    static function insertRegistration($data) {
        $db = self::getConnection();

        $stmt = $db->prepare("INSERT INTO registrations
            (full_name, email, affiliation, dietary_requirements, needs_transport,
             arrival_date, arrival_time, needs_invoice, invoice_details, additional_notes, ip_address)
            VALUES
            (:full_name, :email, :affiliation, :dietary_requirements, :needs_transport,
             :arrival_date, :arrival_time, :needs_invoice, :invoice_details, :additional_notes, :ip_address)");

        return $stmt->execute([
            ':full_name' => $data['full_name'],
            ':email' => $data['email'],
            ':affiliation' => $data['affiliation'],
            ':dietary_requirements' => $data['dietary_requirements'] ?? '',
            ':needs_transport' => isset($data['needs_transport']) ? 1 : 0,
            ':arrival_date' => $data['arrival_date'] ?? '',
            ':arrival_time' => $data['arrival_time'] ?? '',
            ':needs_invoice' => isset($data['needs_invoice']) ? 1 : 0,
            ':invoice_details' => $data['invoice_details'] ?? '',
            ':additional_notes' => $data['additional_notes'] ?? '',
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    }

    /**
     * Get all registrations
     */
    static function getAllRegistrations() {
        $db = self::getConnection();
        $stmt = $db->query("SELECT * FROM registrations ORDER BY registration_date DESC");
        return $stmt->fetchAll();
    }

    /**
     * Get registration count
     */
    static function getRegistrationCount() {
        $db = self::getConnection();
        $stmt = $db->query("SELECT COUNT(*) as count FROM registrations");
        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Get config value
     */
    static function getConfig($key) {
        $db = self::getConnection();
        $stmt = $db->prepare("SELECT value FROM config WHERE key = :key");
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : null;
    }

    /**
     * Set config value
     */
    static function setConfig($key, $value) {
        $db = self::getConnection();
        $stmt = $db->prepare("INSERT OR REPLACE INTO config (key, value, updated_at)
                              VALUES (:key, :value, CURRENT_TIMESTAMP)");
        return $stmt->execute([':key' => $key, ':value' => $value]);
    }

    /**
     * Search/filter registrations
     */
    static function searchRegistrations($filters = []) {
        $db = self::getConnection();

        $where = [];
        $params = [];

        if (!empty($filters['name'])) {
            $where[] = "full_name LIKE :name";
            $params[':name'] = '%' . $filters['name'] . '%';
        }

        if (!empty($filters['email'])) {
            $where[] = "email LIKE :email";
            $params[':email'] = '%' . $filters['email'] . '%';
        }

        if (!empty($filters['affiliation'])) {
            $where[] = "affiliation LIKE :affiliation";
            $params[':affiliation'] = '%' . $filters['affiliation'] . '%';
        }

        if (isset($filters['needs_transport']) && $filters['needs_transport'] !== '') {
            $where[] = "needs_transport = :needs_transport";
            $params[':needs_transport'] = (int)$filters['needs_transport'];
        }

        if (isset($filters['needs_invoice']) && $filters['needs_invoice'] !== '') {
            $where[] = "needs_invoice = :needs_invoice";
            $params[':needs_invoice'] = (int)$filters['needs_invoice'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "registration_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "registration_date <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $sql = "SELECT * FROM registrations";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY registration_date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Export registrations to CSV format
     */
    static function exportToCSV($filters = []) {
        $registrations = empty($filters) ? self::getAllRegistrations() : self::searchRegistrations($filters);

        $output = fopen('php://temp', 'r+');

        // Write header
        fputcsv($output, [
            'ID', 'Full Name', 'Email', 'Affiliation', 'Dietary Requirements',
            'Needs Transport', 'Arrival Date', 'Arrival Time', 'Needs Invoice',
            'Invoice Details', 'Additional Notes', 'Registration Date', 'IP Address'
        ], ',', '"', '\\');

        // Write data
        foreach ($registrations as $reg) {
            fputcsv($output, [
                $reg['id'],
                $reg['full_name'],
                $reg['email'],
                $reg['affiliation'],
                $reg['dietary_requirements'],
                $reg['needs_transport'] ? 'Yes' : 'No',
                $reg['arrival_date'],
                $reg['arrival_time'],
                $reg['needs_invoice'] ? 'Yes' : 'No',
                $reg['invoice_details'],
                $reg['additional_notes'],
                $reg['registration_date'],
                $reg['ip_address']
            ], ',', '"', '\\');
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Verify admin password
     */
    static function verifyAdminPassword($password) {
        $hash = self::getConfig('admin_password_hash');
        if (empty($hash)) {
            return false;
        }
        return password_verify($password, $hash);
    }

    /**
     * Set admin password
     */
    static function setAdminPassword($password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        return self::setConfig('admin_password_hash', $hash);
    }

    /**
     * Check if email already registered
     */
    static function emailExists($email) {
        $db = self::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM registrations WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Check and update rate limit for IP address
     * Returns true if IP is allowed to register, false if rate limit exceeded
     */
    static function checkRateLimit($ip, $limit = 3, $window = 3600) {
        $db = self::getConnection();

        // Get current rate limit record
        $stmt = $db->prepare("SELECT * FROM rate_limit WHERE ip_address = :ip");
        $stmt->execute([':ip' => $ip]);
        $record = $stmt->fetch();

        $now = time();

        if (!$record) {
            // First attempt from this IP
            $stmt = $db->prepare("INSERT INTO rate_limit (ip_address, attempt_count, first_attempt, last_attempt)
                                  VALUES (:ip, 1, datetime('now'), datetime('now'))");
            $stmt->execute([':ip' => $ip]);
            return true;
        }

        $firstAttempt = strtotime($record['first_attempt']);

        // Check if window has expired
        if (($now - $firstAttempt) > $window) {
            // Reset counter
            $stmt = $db->prepare("UPDATE rate_limit
                                  SET attempt_count = 1, first_attempt = datetime('now'), last_attempt = datetime('now')
                                  WHERE ip_address = :ip");
            $stmt->execute([':ip' => $ip]);
            return true;
        }

        // Check if limit exceeded
        if ($record['attempt_count'] >= $limit) {
            return false;
        }

        // Increment counter
        $stmt = $db->prepare("UPDATE rate_limit
                              SET attempt_count = attempt_count + 1, last_attempt = datetime('now')
                              WHERE ip_address = :ip");
        $stmt->execute([':ip' => $ip]);
        return true;
    }

    /**
     * Get statistics for admin panel
     */
    static function getStatistics() {
        $db = self::getConnection();

        $stats = [];

        // Total registrations
        $stats['total'] = self::getRegistrationCount();

        // Transport needs
        $stmt = $db->query("SELECT COUNT(*) as count FROM registrations WHERE needs_transport = 1");
        $result = $stmt->fetch();
        $stats['transport'] = $result['count'];

        // Invoice needs
        $stmt = $db->query("SELECT COUNT(*) as count FROM registrations WHERE needs_invoice = 1");
        $result = $stmt->fetch();
        $stats['invoice'] = $result['count'];

        // Dietary requirements breakdown
        $stmt = $db->query("SELECT dietary_requirements, COUNT(*) as count
                           FROM registrations
                           WHERE dietary_requirements != '' AND dietary_requirements IS NOT NULL
                           GROUP BY dietary_requirements
                           ORDER BY count DESC");
        $stats['dietary'] = $stmt->fetchAll();

        return $stats;
    }
}

// Initialize database on first load
DB::init();
