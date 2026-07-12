<?php
/**
 * Adullam Site Watch
 *
 * Captures PHP errors, fatal crashes, uncaught exceptions, and suspicious
 * request patterns, then logs and emails a structured alert to maintainers.
 */

if (defined('ADULLAM_SITE_WATCH_ACTIVE')) {
    return;
}

define('ADULLAM_SITE_WATCH_ACTIVE', true);

if (!defined('SITE_WATCH_EMAIL_ENABLED')) {
    define('SITE_WATCH_EMAIL_ENABLED', true);
}

if (!defined('SITE_WATCH_BLOCK_THREATS')) {
    define('SITE_WATCH_BLOCK_THREATS', false);
}

if (!defined('SITE_WATCH_ALERT_INTERVAL')) {
    define('SITE_WATCH_ALERT_INTERVAL', 900);
}

if (!defined('SITE_WATCH_LOG_DIR')) {
    define('SITE_WATCH_LOG_DIR', __DIR__ . '/logs');
}

if (!defined('SITE_WATCH_LOG_FILE')) {
    define('SITE_WATCH_LOG_FILE', SITE_WATCH_LOG_DIR . '/site_watch.log');
}

if (!defined('SITE_WATCH_STATE_FILE')) {
    define('SITE_WATCH_STATE_FILE', SITE_WATCH_LOG_DIR . '/site_watch_alerts.json');
}

function site_watch_recipients()
{
    return [
        ['email' => 'noahabayomi14@gmail.com', 'name' => 'Noah Abayomi'],
        ['email' => 'ngbedebarnabas@gmail.com', 'name' => 'Ngbede Barnabas'],
    ];
}

function site_watch_boot()
{
    static $booted = false;

    if ($booted) {
        return;
    }

    $booted = true;
    site_watch_ensure_log_dir();

    $GLOBALS['SITE_WATCH_PREVIOUS_ERROR_HANDLER'] = set_error_handler('site_watch_error_handler');
    $GLOBALS['SITE_WATCH_PREVIOUS_EXCEPTION_HANDLER'] = set_exception_handler('site_watch_exception_handler');

    register_shutdown_function('site_watch_shutdown_handler');
    site_watch_scan_request();
}

function site_watch_ensure_log_dir()
{
    if (!is_dir(SITE_WATCH_LOG_DIR)) {
        @mkdir(SITE_WATCH_LOG_DIR, 0755, true);
    }
}

function site_watch_error_handler($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity)) {
        return false;
    }

    $level = site_watch_error_level($severity);
    $alertSeverity = in_array($severity, [E_USER_ERROR, E_RECOVERABLE_ERROR], true) ? 'high' : 'medium';

    site_watch_report('PHP ' . $level, $alertSeverity, $message, [
        'file' => $file,
        'line' => $line,
        'php_error_level' => $level,
    ]);

    return false;
}

function site_watch_exception_handler($exception)
{
    site_watch_report('Uncaught Exception', 'critical', $exception->getMessage(), [
        'class' => get_class($exception),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => site_watch_trim_text($exception->getTraceAsString(), 5000),
    ]);

    $previous = isset($GLOBALS['SITE_WATCH_PREVIOUS_EXCEPTION_HANDLER'])
        ? $GLOBALS['SITE_WATCH_PREVIOUS_EXCEPTION_HANDLER']
        : null;

    if (is_callable($previous)) {
        call_user_func($previous, $exception);
        return;
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    echo 'A server error occurred. The technical team has been notified.';
}

function site_watch_shutdown_handler()
{
    $error = error_get_last();

    if (!$error) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];

    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    site_watch_report('PHP Fatal Error', 'critical', $error['message'], [
        'file' => isset($error['file']) ? $error['file'] : null,
        'line' => isset($error['line']) ? $error['line'] : null,
        'php_error_level' => site_watch_error_level($error['type']),
    ]);
}

function site_watch_scan_request()
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $body = '';
    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

    if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        $body = site_watch_trim_text((string) @file_get_contents('php://input'), 3000);
    }

    $payload = implode(' ', [
        isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
        isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '',
        isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
        site_watch_trim_text(json_encode(site_watch_redact($_GET)), 3000),
        site_watch_trim_text(json_encode(site_watch_redact($_POST)), 3000),
        $body,
    ]);

    $rules = [
        ['SQL Injection Attempt', 'high', '/(\bunion\b\s+(\ball\b\s+)?\bselect\b|\binformation_schema\b|\bsleep\s*\(|\bbenchmark\s*\(|(\bor\b|\band\b)\s+1\s*=\s*1|--\s|\/\*)/i'],
        ['Cross-Site Scripting Attempt', 'high', '/(<\s*script\b|javascript\s*:|onerror\s*=|onload\s*=|document\s*\.\s*cookie|<\s*iframe\b)/i'],
        ['Path Traversal Attempt', 'high', '/(\.\.\/|\.\.\\\\|\/etc\/passwd|boot\.ini|win\.ini)/i'],
        ['Remote Code Execution Probe', 'critical', '/(php:\/\/input|php:\/\/filter|data:\/\/|base64_decode\s*\(|shell_exec\s*\(|passthru\s*\(|system\s*\(|\bcmd\s*=|\bexec\s*=)/i'],
        ['Sensitive File Probe', 'high', '/(\/\.env\b|\/\.git\b|composer\.(json|lock)|\/vendor\/|wp-admin|wp-login|config\.php|backup\.sql)/i'],
        ['Automated Scanner User Agent', 'medium', '/(sqlmap|nikto|acunetix|nessus|wpscan|dirbuster|masscan|nmap|zgrab|gobuster)/i'],
    ];

    foreach ($rules as $rule) {
        if (preg_match($rule[2], $payload, $match)) {
            site_watch_report($rule[0], $rule[1], 'Suspicious request pattern detected.', [
                'matched_pattern' => isset($match[0]) ? site_watch_trim_text($match[0], 120) : null,
                'request_sample' => site_watch_trim_text($payload, 2500),
                'block_mode' => SITE_WATCH_BLOCK_THREATS ? 'enabled' : 'alert_only',
            ]);

            if (SITE_WATCH_BLOCK_THREATS) {
                if (!headers_sent()) {
                    http_response_code(403);
                }
                exit('Forbidden');
            }

            break;
        }
    }
}

function site_watch_report($type, $severity, $message, array $context = [])
{
    static $sending = false;

    $event = [
        'time' => gmdate('c'),
        'site' => site_watch_site_name(),
        'type' => $type,
        'severity' => strtoupper($severity),
        'message' => site_watch_trim_text($message, 2000),
        'context' => site_watch_redact($context),
        'request' => site_watch_request_context(),
    ];

    site_watch_log($event);

    if ($sending || !SITE_WATCH_EMAIL_ENABLED) {
        return;
    }

    $fingerprint = sha1($event['type'] . '|' . $event['message'] . '|' . json_encode($event['context']) . '|' . $event['request']['uri']);

    if (site_watch_is_rate_limited($fingerprint)) {
        return;
    }

    $sending = true;
    site_watch_send_email($event);
    $sending = false;
}

function site_watch_request_context()
{
    $session = [];

    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION)) {
        foreach (['user_id', 'admin_id', 'role', 'user_role', 'email', 'username'] as $key) {
            if (isset($_SESSION[$key])) {
                $session[$key] = $_SESSION[$key];
            }
        }
    }

    return [
        'ip' => site_watch_client_ip(),
        'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null,
        'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null,
        'referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
        'host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null,
        'script' => isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : null,
        'session' => site_watch_redact($session),
        'get' => site_watch_redact($_GET),
        'post' => site_watch_redact($_POST),
    ];
}

function site_watch_client_ip()
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }

        $value = trim((string) $_SERVER[$key]);
        $parts = explode(',', $value);
        return trim($parts[0]);
    }

    return null;
}

function site_watch_error_level($type)
{
    $levels = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    return isset($levels[$type]) ? $levels[$type] : 'E_UNKNOWN';
}

function site_watch_redact($value)
{
    if (is_array($value)) {
        $clean = [];

        foreach ($value as $key => $item) {
            if (site_watch_is_sensitive_key($key)) {
                $clean[$key] = '[redacted]';
                continue;
            }

            $clean[$key] = site_watch_redact($item);
        }

        return $clean;
    }

    if (is_object($value)) {
        return '[object ' . get_class($value) . ']';
    }

    if (is_string($value)) {
        return site_watch_trim_text($value, 1000);
    }

    return $value;
}

function site_watch_is_sensitive_key($key)
{
    return (bool) preg_match('/password|passwd|pass|pwd|otp|token|secret|key|authorization|cookie|csrf|credential/i', (string) $key);
}

function site_watch_trim_text($text, $limit)
{
    $text = (string) $text;
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    if (function_exists('mb_strlen') && mb_strlen($text) > $limit) {
        return mb_substr($text, 0, $limit) . '...';
    }

    if (strlen($text) > $limit) {
        return substr($text, 0, $limit) . '...';
    }

    return $text;
}

function site_watch_log(array $event)
{
    site_watch_ensure_log_dir();
    @file_put_contents(SITE_WATCH_LOG_FILE, json_encode($event) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function site_watch_is_rate_limited($fingerprint)
{
    site_watch_ensure_log_dir();

    $now = time();
    $state = [];

    if (is_file(SITE_WATCH_STATE_FILE)) {
        $decoded = json_decode((string) @file_get_contents(SITE_WATCH_STATE_FILE), true);
        if (is_array($decoded)) {
            $state = $decoded;
        }
    }

    foreach ($state as $key => $lastSentAt) {
        if (!is_numeric($lastSentAt) || ($now - (int) $lastSentAt) > 86400) {
            unset($state[$key]);
        }
    }

    if (isset($state[$fingerprint]) && ($now - (int) $state[$fingerprint]) < SITE_WATCH_ALERT_INTERVAL) {
        @file_put_contents(SITE_WATCH_STATE_FILE, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX);
        return true;
    }

    $state[$fingerprint] = $now;
    @file_put_contents(SITE_WATCH_STATE_FILE, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX);

    return false;
}

function site_watch_send_email(array $event)
{
    $autoloadPaths = [
        __DIR__ . '/vendor/autoload.php',
        '/home/adullamn/public_html/vendor/autoload.php',
    ];

    foreach ($autoloadPaths as $autoloadPath) {
        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
            break;
        }
    }

    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        site_watch_log([
            'time' => gmdate('c'),
            'site' => site_watch_site_name(),
            'type' => 'Site Watch Mailer Unavailable',
            'severity' => 'WARNING',
            'message' => 'PHPMailer was not available, so the alert email could not be sent.',
            'context' => [],
            'request' => site_watch_request_context(),
        ]);
        return false;
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'adullamadmissions@gmail.com';
        $mail->Password = 'lbwo hnjp ylnj hruh';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom('adullamadmissions@gmail.com', 'Adullam Site Watch');

        foreach (site_watch_recipients() as $recipient) {
            $mail->addAddress($recipient['email'], $recipient['name']);
        }

        $mail->isHTML(true);
        $mail->Subject = '[Adullam Site Watch] ' . strtoupper($event['severity']) . ': ' . $event['type'];
        $mail->Body = site_watch_email_html($event);
        $mail->AltBody = site_watch_email_text($event);
        $mail->send();

        return true;
    } catch (\Throwable $mailError) {
        site_watch_log([
            'time' => gmdate('c'),
            'site' => site_watch_site_name(),
            'type' => 'Site Watch Email Failure',
            'severity' => 'ERROR',
            'message' => $mailError->getMessage(),
            'context' => [],
            'request' => site_watch_request_context(),
        ]);
        return false;
    }
}

function site_watch_email_html(array $event)
{
    $severityColor = site_watch_severity_color($event['severity']);
    $contextRows = site_watch_array_rows($event['context']);
    $requestRows = site_watch_array_rows($event['request']);

    return '<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Adullam Site Watch Alert</title>
</head>
<body style="margin:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;background:#ffffff;border:1px solid #dfe7f3;border-radius:14px;overflow:hidden;">
          <tr>
            <td style="padding:24px 28px;background:#0f172a;color:#ffffff;">
              <img src="https://adullam.ng/assets/img/logo1.png" alt="Adullam" width="82" style="display:block;margin-bottom:16px;">
              <div style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#b7c4d8;">Site Watch Alert</div>
              <h1 style="margin:8px 0 0;font-size:24px;line-height:1.3;">' . site_watch_escape($event['type']) . '</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 28px;">
              <span style="display:inline-block;background:' . $severityColor . ';color:#ffffff;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:bold;letter-spacing:.04em;">' . site_watch_escape($event['severity']) . '</span>
              <p style="font-size:15px;line-height:1.7;margin:18px 0 22px;">' . nl2br(site_watch_escape($event['message'])) . '</p>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-bottom:22px;">
                <tr><td style="padding:10px 0;border-bottom:1px solid #edf1f7;color:#64748b;width:160px;">Site</td><td style="padding:10px 0;border-bottom:1px solid #edf1f7;">' . site_watch_escape($event['site']) . '</td></tr>
                <tr><td style="padding:10px 0;border-bottom:1px solid #edf1f7;color:#64748b;">UTC Time</td><td style="padding:10px 0;border-bottom:1px solid #edf1f7;">' . site_watch_escape($event['time']) . '</td></tr>
              </table>
              <h2 style="font-size:16px;margin:0 0 10px;color:#0f172a;">Technical Context</h2>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-bottom:22px;">' . $contextRows . '</table>
              <h2 style="font-size:16px;margin:0 0 10px;color:#0f172a;">Request Context</h2>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">' . $requestRows . '</table>
            </td>
          </tr>
          <tr>
            <td style="padding:18px 28px;background:#f8fafc;color:#64748b;font-size:13px;line-height:1.6;">
              This automated alert was generated by Adullam Site Watch. Review the log file at <strong>logs/site_watch.log</strong> and rotate exposed credentials immediately if the alert suggests unauthorized access.
              <br>Website: <a href="https://adullam.ng" style="color:#1d4ed8;">adullam.ng</a> | Email: adullamadmissions@gmail.com
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

function site_watch_email_text(array $event)
{
    return "Adullam Site Watch Alert\n"
        . "Severity: {$event['severity']}\n"
        . "Type: {$event['type']}\n"
        . "Time: {$event['time']}\n"
        . "Site: {$event['site']}\n\n"
        . "Message:\n{$event['message']}\n\n"
        . "Context:\n" . print_r($event['context'], true) . "\n"
        . "Request:\n" . print_r($event['request'], true);
}

function site_watch_array_rows(array $items, $prefix = '')
{
    $rows = '';

    foreach ($items as $key => $value) {
        $label = $prefix === '' ? (string) $key : $prefix . '.' . $key;

        if (is_array($value)) {
            $rows .= site_watch_array_rows($value, $label);
            continue;
        }

        $rows .= '<tr>'
            . '<td style="padding:9px 10px;border:1px solid #edf1f7;background:#f8fafc;color:#64748b;width:190px;vertical-align:top;">' . site_watch_escape($label) . '</td>'
            . '<td style="padding:9px 10px;border:1px solid #edf1f7;color:#172033;vertical-align:top;word-break:break-word;">' . nl2br(site_watch_escape((string) $value)) . '</td>'
            . '</tr>';
    }

    if ($rows === '') {
        $rows = '<tr><td style="padding:9px 10px;border:1px solid #edf1f7;color:#64748b;">No additional data captured.</td></tr>';
    }

    return $rows;
}

function site_watch_severity_color($severity)
{
    $severity = strtoupper((string) $severity);

    if ($severity === 'CRITICAL') {
        return '#b91c1c';
    }

    if ($severity === 'HIGH') {
        return '#c2410c';
    }

    if ($severity === 'MEDIUM') {
        return '#b45309';
    }

    return '#2563eb';
}

function site_watch_site_name()
{
    if (!empty($_SERVER['HTTP_HOST'])) {
        return $_SERVER['HTTP_HOST'];
    }

    return 'adullam.ng';
}

function site_watch_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

site_watch_boot();
