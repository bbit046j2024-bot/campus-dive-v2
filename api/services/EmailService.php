<?php
/**
 * Email Service (PHPMailer wrapper)
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

    /**
     * Get the base template for all emails
     */
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

    public static function send(string $to, string $subject, string $htmlBody): bool {
        $apiKey = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : (getenv('MAIL_PASSWORD') ?: '');
        $fromAddress = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : (getenv('MAIL_FROM_ADDRESS') ?: 'noreply@campusdive.com');
        $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : (getenv('MAIL_FROM_NAME') ?: 'Campus Dive');

        if (empty($apiKey)) {
             self::logError("Email Configuration Missing", ['to' => $to, 'msg' => 'MAIL_PASSWORD not set in .env']);
             return false;
        }

        // If it looks like a Resend key, use Resend API
        if (str_starts_with($apiKey, 're_')) {
            $apiUrl = 'https://api.resend.com/emails';
            $data = [
                'from'    => $fromName . ' <' . $fromAddress . '>',
                'to'      => [$to],
                'subject' => $subject,
                'html'    => $htmlBody,
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) return true;
            
            self::logError("Resend API Failed", ['to' => $to, 'http_code' => $httpCode, 'response' => $response]);
        } 
        else {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = defined('MAIL_HOST') ? MAIL_HOST : getenv('MAIL_HOST');
                $mail->SMTPAuth   = true;
                $mail->Username   = defined('MAIL_USERNAME') ? MAIL_USERNAME : getenv('MAIL_USERNAME');
                $mail->Password   = $apiKey;
                $port             = defined('MAIL_PORT') ? MAIL_PORT : getenv('MAIL_PORT');
                $mail->SMTPSecure = ($port == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $port;
                $mail->setFrom($fromAddress, $fromName);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;
                $mail->AltBody = strip_tags($htmlBody);
                $mail->send();
                return true;
            } catch (Exception $e) {
                self::logError("PHPMailer SMTP Failed", ['to' => $to, 'error' => $mail->ErrorInfo]);
            }
        }
        return false;
    }

    public static function sendVerification(string $to, string $firstname, string $token): bool {
        $baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/Campus-Dive/Campus-Dive-main', '/');
        // Support for Vercel/Production detection
        if (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'railway.app')) {
            $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
        }
        
        $link = $baseUrl . '/api/auth/verify-email?token=' . $token;
        $content = "<p>Welcome to the mainframe, <strong>{$firstname}</strong>.</p><p>Establish your clearance level by synchronizing your email address with our security protocols.</p>";
        $html = self::getBaseTemplate('Security Sync Required', $content, 'ESTABLISH CLEARANCE', $link);
        
        return self::send($to, 'Security Sync: Verify Your Identity', $html);
    }

    public static function sendPasswordReset(string $to, string $firstname, string $token): bool {
        $frontendUrl = rtrim(getenv('FRONTEND_URL') ?: 'https://campus-dive-v2.vercel.app', '/');
        $link = $frontendUrl . '/#/reset-password?token=' . $token;
        
        $content = "<p>A password reset protocol was initiated for your profile. If you did not authorize this, please contact security immediately.</p>";
        $html = self::getBaseTemplate('Protocol: Password Reset', $content, 'REINITIALIZE ACCESS', $link);
        
        return self::send($to, 'Protocol Sync: Reset Password', $html);
    }

    public static function sendTest(string $to): bool {
        $content = "<p>System diagnostics complete. Your SMTP uplink is operational. High-speed communication protocols are now fully active.</p>";
        $html = self::getBaseTemplate('Diagnostic: Uplink Active', $content, 'ACCESS DASHBOARD', getenv('FRONTEND_URL'));
        return self::send($to, 'System Diagnostic: SMTP Uplink Success', $html);
    }

    private static function logError(string $context, array $details): void {
        error_log("$context: " . json_encode($details));
    }
}
",Description: