<?php

function silah_mailer_config() {
    $host = getenv('SILAH_SMTP_HOST');
    $port = getenv('SILAH_SMTP_PORT');
    $secure = getenv('SILAH_SMTP_SECURE');
    $user = getenv('SILAH_SMTP_USER');
    $pass = getenv('SILAH_SMTP_PASS');
    $fromEmail = getenv('SILAH_FROM_EMAIL');
    $fromName = getenv('SILAH_FROM_NAME');

    if (!$port || !is_numeric($port)) {
        $port = 587;
    } else {
        $port = (int)$port;
    }

    $secure = $secure ? strtolower(trim((string)$secure)) : '';
    if ($secure !== 'tls' && $secure !== 'ssl' && $secure !== '') {
        $secure = '';
    }

    return [
        'smtp_host' => $host ? (string)$host : '',
        'smtp_port' => $port,
        'smtp_secure' => $secure,
        'smtp_user' => $user ? (string)$user : '',
        'smtp_pass' => $pass ? (string)$pass : '',
        'from_email' => $fromEmail ? (string)$fromEmail : '',
        'from_name' => $fromName ? (string)$fromName : 'Silah',
    ];
}

function silah_send_email($to, $subject, $body, $replyToEmail = '', $replyToName = '') {
    $cfg = silah_mailer_config();
    $toList = is_array($to) ? $to : [$to];
    $toList = array_values(array_filter(array_map('trim', array_map('strval', $toList))));
    if (empty($toList)) return false;

    $subject = (string)$subject;
    $body = (string)$body;
    $replyToEmail = trim((string)$replyToEmail);
    $replyToName = trim((string)$replyToName);

    $fromEmail = $cfg['from_email'] !== '' ? $cfg['from_email'] : ($cfg['smtp_user'] !== '' ? $cfg['smtp_user'] : ('silah@' . (isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : 'localhost')));
    $fromName = $cfg['from_name'] !== '' ? $cfg['from_name'] : 'Silah';

    $useSmtp = $cfg['smtp_host'] !== '' && $cfg['smtp_user'] !== '' && $cfg['smtp_pass'] !== '';
    if ($useSmtp) {
        return silah_smtp_send($cfg, $fromEmail, $fromName, $toList, $subject, $body, $replyToEmail, $replyToName);
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [];
    $headers[] = 'From: ' . silah_format_address($fromEmail, $fromName);
    if ($replyToEmail !== '') {
        $headers[] = 'Reply-To: ' . silah_format_address($replyToEmail, $replyToName);
    } else {
        $headers[] = 'Reply-To: ' . $fromEmail;
    }
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';

    $ok = true;
    foreach ($toList as $addr) {
        $sent = @mail($addr, $encodedSubject, $body, implode("\r\n", $headers));
        if (!$sent) $ok = false;
    }
    return $ok;
}

function silah_format_address($email, $name) {
    $email = trim((string)$email);
    $name = trim((string)$name);
    if ($name === '') return $email;
    $encoded = '=?UTF-8?B?' . base64_encode($name) . '?=';
    return $encoded . ' <' . $email . '>';
}

function silah_smtp_send($cfg, $fromEmail, $fromName, $toList, $subject, $body, $replyToEmail = '', $replyToName = '') {
    $host = (string)$cfg['smtp_host'];
    $port = (int)$cfg['smtp_port'];
    $secure = (string)$cfg['smtp_secure'];
    $user = (string)$cfg['smtp_user'];
    $pass = (string)$cfg['smtp_pass'];

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host;
    $fp = @fsockopen($remote, $port, $errno, $errstr, 12);
    if (!$fp) return false;
    stream_set_timeout($fp, 12);

    $ok = silah_smtp_expect($fp, [220]);
    if (!$ok) { fclose($fp); return false; }

    $heloHost = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : 'localhost';
    if (!silah_smtp_cmd($fp, "EHLO " . $heloHost, [250])) { fclose($fp); return false; }

    if ($secure === 'tls') {
        if (!silah_smtp_cmd($fp, "STARTTLS", [220])) { fclose($fp); return false; }
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($fp); return false; }
        if (!silah_smtp_cmd($fp, "EHLO " . $heloHost, [250])) { fclose($fp); return false; }
    }

    if (!silah_smtp_cmd($fp, "AUTH LOGIN", [334])) { fclose($fp); return false; }
    if (!silah_smtp_cmd($fp, base64_encode($user), [334])) { fclose($fp); return false; }
    if (!silah_smtp_cmd($fp, base64_encode($pass), [235])) { fclose($fp); return false; }

    if (!silah_smtp_cmd($fp, "MAIL FROM:<" . $fromEmail . ">", [250])) { fclose($fp); return false; }
    foreach ($toList as $addr) {
        if (!silah_smtp_cmd($fp, "RCPT TO:<" . $addr . ">", [250, 251])) { fclose($fp); return false; }
    }

    if (!silah_smtp_cmd($fp, "DATA", [354])) { fclose($fp); return false; }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [];
    $headers[] = 'From: ' . silah_format_address($fromEmail, $fromName);
    $headers[] = 'To: ' . implode(', ', $toList);
    $headers[] = 'Subject: ' . $encodedSubject;
    if (trim((string)$replyToEmail) !== '') {
        $headers[] = 'Reply-To: ' . silah_format_address($replyToEmail, (string)$replyToName);
    }
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: base64';

    $data = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
    $data = preg_replace("/\r\n\./", "\r\n..", $data);
    fwrite($fp, $data . "\r\n.\r\n");

    if (!silah_smtp_expect($fp, [250])) { fclose($fp); return false; }
    silah_smtp_cmd($fp, "QUIT", [221, 250]);
    fclose($fp);
    return true;
}

function silah_smtp_cmd($fp, $cmd, $expected) {
    fwrite($fp, $cmd . "\r\n");
    return silah_smtp_expect($fp, $expected);
}

function silah_smtp_expect($fp, $expectedCodes) {
    $line = '';
    $code = 0;
    while (!feof($fp)) {
        $line = fgets($fp, 515);
        if ($line === false) break;
        if (strlen($line) >= 3 && ctype_digit(substr($line, 0, 3))) {
            $code = (int)substr($line, 0, 3);
        }
        if (isset($line[3]) && $line[3] !== '-') {
            break;
        }
    }
    return in_array($code, $expectedCodes, true);
}
