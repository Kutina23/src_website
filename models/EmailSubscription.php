<?php
require_once __DIR__ . '/../config/email.php';

class EmailSubscription {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function create($data) {
        $token = bin2hex(random_bytes(32));
        $sql = "INSERT INTO email_subscribers (email, full_name, token) VALUES (?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            $data['email'],
            $data['full_name'] ?? null,
            $token
        ]);
        return $this->db->getConnection()->lastInsertId();
    }

    public function subscribe($email, $fullName = null) {
        $existing = $this->getByEmail($email);
        if ($existing) {
            if (!$existing['is_active']) {
                $this->db->execute(
                    "UPDATE email_subscribers SET is_active = TRUE, unsubscribed_at = NULL WHERE id = ?",
                    [$existing['id']]
                );
            }
            return $existing['id'];
        }
        return $this->create(['email' => $email, 'full_name' => $fullName]);
    }

    public function unsubscribe($token) {
        return $this->db->execute(
            "UPDATE email_subscribers SET is_active = FALSE, unsubscribed_at = NOW() WHERE token = ?",
            [$token]
        );
    }

    public function getByEmail($email) {
        return $this->db->fetch("SELECT * FROM email_subscribers WHERE email = ?", [$email]);
    }

    public function getByToken($token) {
        return $this->db->fetch("SELECT * FROM email_subscribers WHERE token = ?", [$token]);
    }

    public function updateLastSent($id) {
        return $this->db->execute(
            "UPDATE email_subscribers SET last_sent = NOW() WHERE id = ?",
            [$id]
        );
    }

    public function getAllActive() {
        return $this->db->fetchAll("SELECT * FROM email_subscribers WHERE is_active = TRUE ORDER BY subscribed_at DESC");
    }

    public function count() {
        return $this->db->fetch("SELECT COUNT(*) as count FROM email_subscribers WHERE is_active = TRUE")['count'];
    }

    public function delete($id) {
        return $this->db->execute("DELETE FROM email_subscribers WHERE id = ?", [$id]);
    }

    /**
     * Send newsletter to all active subscribers
     * 
     * @param string $subject Email subject
     * @param string $body Email HTML body (can include {UNSUBSCRIBE_LINK} placeholder)
     * @param string $contentType text/html or text/plain
     * @return array ['sent' => count, 'failed' => count, 'errors' => []]
     */
    public function sendNewsletter($subject, $body, $contentType = 'html') {
        $config = getMailConfig();
        
        $subscribers = $this->getAllActive();
        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($subscribers as $subscriber) {
            try {
                $unsubscribeUrl = $this->buildUnsubscribeUrl($subscriber['token']);
                $emailBody = str_replace('{UNSUBSCRIBE_LINK}', $unsubscribeUrl, $body);

                if (strpos($body, '{UNSUBSCRIBE_LINK}') === false) {
                    $emailBody .= "\n\n<hr style='margin: 20px 0;'><small style='color: #666;'>";
                    $emailBody .= "<a href='{$unsubscribeUrl}'>Unsubscribe from announcements</a>";
                    $emailBody .= "</small>";
                }

                $config = getMailConfig();
                $headers = [
                    'From' => $config['from_address'],
                    'Content-Type' => $contentType === 'html' ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8',
                    'Reply-To' => $config['reply_to']
                ];

                if (sendEmail($subscriber['email'], $subject, $emailBody, $headers)) {
                    $this->updateLastSent($subscriber['id']);
                    $sent++;
                } else {
                    $failed++;
                    $errors[] = "Failed to send to {$subscriber['email']}";
                }
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Error sending to {$subscriber['email']}: " . $e->getMessage();
            }
        }

        return [
            'success' => $failed === 0,
            'sent' => $sent,
            'failed' => $failed,
            'total_subscribers' => count($subscribers),
            'errors' => $errors
        ];
    }

    /**
     * Send email to specific subscribers
     * 
     * @param array $emails Array of email addresses
     * @param string $subject
     * @param string $body
     * @return array
     */
    public function sendToEmails($emails, $subject, $body, $contentType = 'html') {
        $config = getMailConfig();
        
        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($emails as $email) {
            try {
                $subscriber = $this->getByEmail($email);
                
                $emailBody = $body;
                
                if ($subscriber) {
                    $unsubscribeUrl = $this->buildUnsubscribeUrl($subscriber['token']);
                    $emailBody .= "\n\n<hr style='margin: 20px 0;'><small style='color: #666;'>";
                    $emailBody .= "<a href='{$unsubscribeUrl}'>Unsubscribe</a>";
                    $emailBody .= "</small>";
                }

                $config = getMailConfig();
                $headers = [
                    'From' => $config['from_address'],
                    'Content-Type' => $contentType === 'html' ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8',
                    'Reply-To' => $config['reply_to']
                ];

                if (sendEmail($email, $subject, $emailBody, $headers)) {
                    if ($subscriber) {
                        $this->updateLastSent($subscriber['id']);
                    }
                    $sent++;
                } else {
                    $failed++;
                    $errors[] = "Failed to send to {$email}";
                }
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Error sending to {$email}: " . $e->getMessage();
            }
        }

        return [
            'success' => $failed === 0,
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Build unsubscribe URL
     * 
     * @param string $token
     * @return string
     */
    private function buildUnsubscribeUrl($token) {
        $baseUrl = '';
        if (function_exists('getAppUrl')) {
            $url = getAppUrl();
            if ($url) {
                $baseUrl = rtrim($url, '/');
            }
        }
        if (!$baseUrl) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $baseUrl = rtrim($protocol . '://' . $_SERVER['HTTP_HOST'], '/');
        }
        
        return $baseUrl . '/api/email-subscribe.php?action=unsubscribe&token=' . $token;
    }
}
?>