<?php
/**
 * Email Service — Resend API (primary) with PHPMailer SMTP fallback
 */
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {

    private static function getBaseTemplate(string $title, string $content, string $buttonText = '', string $buttonLink = ''): string {
        $buttonHtml = $buttonText && $buttonLink ? "
            <div style='margin: 40px 0;'>
                <a href='{$buttonLink}' style='background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 16px 36px; border-radius: 14px; text-decoration: none; font-weight: 800; font-size: 14px; letter-spacing: 0.1em; text-transform: uppercase; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);'>
                    {$buttonText}
                </a>
            </div>
        " : "";

        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <style>
                    body { margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
                    .wrapper { padding: 40px 20px; }
                    .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
                    .header { background: #1e1b4b; padding: 40px; text-align: center; }
                    .content { padding: 40px; text-align: center; color: #1e293b; }
                    .footer { padding: 30px; text-align: center; background: #f1f5f9; color: #64748b; font-size: 12px; }
                    h1 { color: #ffffff; margin: 0; font-weight: 900; letter-spacing: -0.02em; font-size: 24px; text-transform: uppercase; }
                    p { font-size: 16px; line-height: 1.7; margin-bottom: 24px; color: #475569; }
                </style>
            </head>
            <body>
                <div class='wrapper'>
                    <div class='container'>
                        <div class='header'>
                            <h1>CAMPUS<span style='color: #6366f1;'>DIVE</span></h1>
                        </div>
                        <div class='content'>
                            <h2 style='color: #1e1b4b; font-weight: 800; margin-bottom: 20px;'>{$title}</h2>
                            {$content}
                            {$buttonHtml}
                        </div>
                        <div class='footer'>
                            <p style='margin: 0; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em;'>The Digital Mainframe</p>
                            <p style='margin-top: 10px;'>This is an automated transmission. No reply required.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Resolve the API key — checks RESEND_API_KEY first, then MAIL_PASSWORD.
     */
    private static function resolveApiKey(): string {
        // Prefer RESEND_API_KEY env var directly
        $key = getenv('RESEND_API_KEY');
        if (!empty($key)) return $key;

        // Fallback to MAIL_PASSWORD constant / env var
        if (defined('MAIL_PASSWORD') && !empty(MAIL_PASSWORD)) return MAIL_PASSWORD;
        $key = getenv('MAIL_PASSWORD');
        if (!empty($key)) return $key;

        return '';
    }

    /**
     * Resolve the from address.
     * IMPORTANT: onboarding@resend.dev can only deliver to the Resend account-owner email.
     * For sending to any recipient you MUST set MAIL_FROM_ADDRESS to an address on a
     * verified domain in your Resend dashboard (e.g. noreply@yourdomain.com).
     */
    private static function resolveFrom(): array {
        $fromAddress = getenv('MAIL_FROM_ADDRESS');
        if (empty($fromAddress) && defined('MAIL_FROM_ADDRESS')) $fromAddress = MAIL_FROM_ADDRESS;
        if (empty($fromAddress)) $fromAddress = '';

        $fromName = getenv('MAIL_FROM_NAME');
        if (empty($fromName) && defined('MAIL_FROM_NAME')) $fromName = MAIL_FROM_NAME;
        if (empty($fromName)) $fromName = 'Campus Dive';

        $apiKey = self::resolveApiKey();

        // If using Resend and from address is a personal email provider, switch to shared test domain.
        // WARNING: onboarding@resend.dev only delivers to the Resend account owner's email.
        // To send to ANY recipient, add a verified domain in Resend dashboard and set
        // MAIL_FROM_ADDRESS=noreply@yourdomain.com on Railway.
        if (str_starts_with($apiKey, 're_')) {
            $isPersonalEmail = (
                str_contains($fromAddress, 'gmail.com') ||
                str_contains($fromAddress, 'outlook.com') ||
                str_contains($fromAddress, 'yahoo.com') ||
                str_contains($fromAddress, 'hotmail.com') ||
                empty($fromAddress)
            );
            if ($isPersonalEmail) {
                error_log('[EmailService] WARNING: MAIL_FROM_ADDRESS is a personal email or not set. '
                    . 'Falling back to onboarding@resend.dev which can ONLY deliver to your Resend account email. '
                    . 'Set MAIL_FROM_ADDRESS=noreply@yourdomain.com (verified in Resend) to send to any recipient.');
                $fromAddress = 'onboarding@resend.dev';
            }
        }

        return [$fromAddress, $fromName];
    }

    /**
     * Resolve SMTP credentials for fallback sending.
     * Returns null if SMTP is not configured.
     */
    private static function smtpConfigured(): bool {
        $host = getenv('SMTP_HOST') ?: getenv('MAIL_HOST') ?: (defined('MAIL_HOST') ? MAIL_HOST : '');
        $user = getenv('SMTP_USERNAME') ?: getenv('MAIL_USERNAME') ?: (defined('MAIL_USERNAME') ? MAIL_USERNAME : '');
        $pass = getenv('SMTP_PASSWORD') ?: getenv('MAIL_SMTP_PASSWORD') ?: '';
        return !empty($host) && !empty($user) && !empty($pass);
    }

    private static function getSMTPCredentials(): array {
        return [
            'host' => getenv('SMTP_HOST') ?: getenv('MAIL_HOST') ?: (defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com'),
            'user' => getenv('SMTP_USERNAME') ?: getenv('MAIL_USERNAME') ?: (defined('MAIL_USERNAME') ? MAIL_USERNAME : ''),
            'pass' => getenv('SMTP_PASSWORD') ?: getenv('MAIL_SMTP_PASSWORD') ?: '',
            'port' => (int)(getenv('SMTP_PORT') ?: getenv('MAIL_PORT') ?: (defined('MAIL_PORT') ? MAIL_PORT : 465)),
        ];
    }

    public static function send(string $to, string $subject, string $htmlBody): bool {
        $apiKey = self::resolveApiKey();
        [$fromAddress, $fromName] = self::resolveFrom();

        if (empty($apiKey)) {
            error_log('[EmailService] ERROR: No email API key/password found. Set RESEND_API_KEY or MAIL_PASSWORD on Railway.');
            // Still try SMTP fallback if configured
            if (self::smtpConfigured()) {
                error_log('[EmailService] Attempting SMTP fallback (no Resend key set).');
                $smtp = self::getSMTPCredentials();
                return self::sendViaSMTP($smtp['pass'], $fromAddress, $fromName, $to, $subject, $htmlBody, $smtp);
            }
            return false;
        }

        // Primary: Resend API
        if (str_starts_with($apiKey, 're_')) {
            $sent = self::sendViaResend($apiKey, $fromAddress, $fromName, $to, $subject, $htmlBody);
            if ($sent) return true;

            // Resend failed — try SMTP fallback
            if (self::smtpConfigured()) {
                error_log('[EmailService] Resend failed. Attempting SMTP fallback.');
                $smtp = self::getSMTPCredentials();
                // Use SMTP from address (can be different from Resend's verified domain)
                $smtpFrom = getenv('SMTP_FROM_ADDRESS') ?: $smtp['user'];
                return self::sendViaSMTP($smtp['pass'], $smtpFrom, $fromName, $to, $subject, $htmlBody, $smtp);
            }

            error_log('[EmailService] Resend failed and no SMTP fallback is configured. '
                . 'Set SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD on Railway to enable fallback.');
            return false;
        }

        // Primary: SMTP (no Resend key)
        return self::sendViaSMTP($apiKey, $fromAddress, $fromName, $to, $subject, $htmlBody);
    }

    private static function sendViaResend(string $apiKey, string $from, string $fromName, string $to, string $subject, string $html): bool {
        $payload = [
            'from'    => "{$fromName} <{$from}>",
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $html,
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log('[EmailService] Resend cURL error: ' . $curlError);
            return false;
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log('[EmailService] Resend OK — id=' . ($decoded['id'] ?? 'n/a') . ' to=' . $to . ' from=' . $from);
            return true;
        }

        // Log the full Resend error response so it's visible in Railway logs
        $resendMessage = $decoded['message'] ?? $decoded['error'] ?? $response;
        error_log('[EmailService] Resend FAILED — HTTP ' . $httpCode
            . ' | from=' . $from
            . ' | to=' . $to
            . ' | error=' . $resendMessage
            . ' | hint: if using onboarding@resend.dev, it only delivers to the Resend account email.'
            . ' Set MAIL_FROM_ADDRESS=noreply@yourdomain.com after verifying your domain in Resend.');
        return false;
    }

    /**
     * @param array|null $creds  Optional override: ['host','user','pass','port']
     *                           When null, falls back to MAIL_* constants / env vars.
     */
    private static function sendViaSMTP(string $password, string $from, string $fromName, string $to, string $subject, string $html, ?array $creds = null): bool {
        $host = $creds['host'] ?? (defined('MAIL_HOST') ? MAIL_HOST : (getenv('MAIL_HOST') ?: 'smtp.gmail.com'));
        $user = $creds['user'] ?? (defined('MAIL_USERNAME') ? MAIL_USERNAME : (getenv('MAIL_USERNAME') ?: ''));
        $pass = $creds['pass'] ?? $password;
        $port = (int)($creds['port'] ?? (defined('MAIL_PORT') ? MAIL_PORT : (int)(getenv('MAIL_PORT') ?: 465)));

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $user;
            $mail->Password   = $pass;
            $mail->SMTPSecure = ($port == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $port;
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);
            $mail->send();
            error_log('[EmailService] SMTP OK — host=' . $host . ' to=' . $to);
            return true;
        } catch (Exception $e) {
            error_log('[EmailService] SMTP FAILED — host=' . $host . ' to=' . $to . ' | error=' . $mail->ErrorInfo);
            return false;
        }
    }

    public static function sendVerification(string $to, string $firstname, string $token): bool {
        // Verification link must always point to the backend API
        $backendUrl = rtrim(getenv('APP_URL') ?: ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
        $link = $backendUrl . '/api/auth/verify-email?token=' . $token;
        $content = "<p>Welcome to the mainframe, <strong>{$firstname}</strong>.</p>"
                 . "<p>Establish your clearance level by verifying your email address.</p>"
                 . "<p style='font-size:12px;color:#94a3b8;'>If the button doesn't work, copy this link: {$link}</p>";
        $html = self::getBaseTemplate('Verify Your Email', $content, 'VERIFY EMAIL', $link);
        return self::send($to, 'Campus Dive: Verify Your Email', $html);
    }

    public static function sendPasswordReset(string $to, string $firstname, string $token): bool {
        $frontendUrl = rtrim(getenv('FRONTEND_URL') ?: 'https://campus-dive-v2.vercel.app', '/');
        $link = $frontendUrl . '/#/reset-password?token=' . $token;
        $content = "<p>A password reset was requested for your account, <strong>{$firstname}</strong>.</p>"
                 . "<p>If you did not request this, ignore this email — your password won't change.</p>"
                 . "<p style='font-size:12px;color:#94a3b8;'>Link: {$link}</p>";
        $html = self::getBaseTemplate('Password Reset', $content, 'RESET PASSWORD', $link);
        return self::send($to, 'Campus Dive: Reset Your Password', $html);
    }

    public static function sendTest(string $to): bool {
        $content = "<p>Your email integration is working correctly. Emails will be delivered to your users.</p>";
        $html = self::getBaseTemplate('Email Test Successful', $content, 'GO TO DASHBOARD', getenv('FRONTEND_URL') ?: '#');
        return self::send($to, 'Campus Dive: Email Test', $html);
    }

    public static function sendNotification(string $to, string $subject, string $message): bool {
        $html = self::getBaseTemplate($subject, "<p>{$message}</p>");
        return self::send($to, $subject, $html);
    }

    /**
     * Returns diagnostic info about current email configuration (for the debug endpoint).
     */
    public static function getDiagnostics(): array {
        $apiKey = self::resolveApiKey();
        [$fromAddress, $fromName] = self::resolveFrom();
        $isResend = str_starts_with($apiKey, 're_');

        $warning = null;
        if ($isResend && $fromAddress === 'onboarding@resend.dev') {
            $warning = 'Using onboarding@resend.dev — emails can ONLY be delivered to your Resend account email. '
                . 'To send to any recipient, verify a domain in Resend and set MAIL_FROM_ADDRESS=noreply@yourdomain.com on Railway.';
        }

        $smtpHost = getenv('SMTP_HOST') ?: getenv('MAIL_HOST') ?: (defined('MAIL_HOST') ? MAIL_HOST : '');
        $smtpUser = getenv('SMTP_USERNAME') ?: getenv('MAIL_USERNAME') ?: (defined('MAIL_USERNAME') ? MAIL_USERNAME : '');
        $smtpPass = getenv('SMTP_PASSWORD') ?: getenv('MAIL_SMTP_PASSWORD') ?: '';
        $smtpPort = getenv('SMTP_PORT') ?: getenv('MAIL_PORT') ?: (defined('MAIL_PORT') ? MAIL_PORT : '');

        return [
            'driver'              => $isResend ? 'Resend API' : 'SMTP (PHPMailer)',
            'api_key_set'         => !empty($apiKey),
            'api_key_prefix'      => $apiKey ? substr($apiKey, 0, 8) . '...' : 'NOT SET',
            'from_address'        => $fromAddress ?: 'NOT SET',
            'from_name'           => $fromName,
            'warning'             => $warning,
            'RESEND_API_KEY'      => getenv('RESEND_API_KEY') ? 'set' : 'not set',
            'MAIL_PASSWORD'       => getenv('MAIL_PASSWORD') ? 'set' : 'not set',
            'MAIL_FROM_ADDRESS'   => getenv('MAIL_FROM_ADDRESS') ?: 'not set (using fallback)',
            'APP_URL'             => getenv('APP_URL') ?: 'not set',
            'FRONTEND_URL'        => getenv('FRONTEND_URL') ?: 'not set',
            'smtp_fallback'       => [
                'configured'      => self::smtpConfigured(),
                'SMTP_HOST'       => $smtpHost ?: 'NOT SET',
                'SMTP_USERNAME'   => $smtpUser ?: 'NOT SET',
                'SMTP_PASSWORD'   => $smtpPass ? 'set' : 'NOT SET',
                'SMTP_PORT'       => $smtpPort ?: '465 (default)',
                'SMTP_FROM'       => getenv('SMTP_FROM_ADDRESS') ?: 'not set (uses SMTP_USERNAME)',
            ],
        ];
    }
}
