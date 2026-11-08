<?php
/** Réinitialisation de mot de passe, indépendant des anciens scripts de trading. */

declare(strict_types=1);

function coreenResetSettings(): array
{
    $file = '/etc/coreen/mail.php';
    if (!is_readable($file)) {
        throw new RuntimeException('Configuration SMTP absente ou illisible : /etc/coreen/mail.php');
    }
    $settings = require $file;
    if (!is_array($settings)) {
        throw new RuntimeException('Configuration SMTP invalide.');
    }
    $base = rtrim((string)($settings['base_url'] ?? ''), '/');
    $parts = parse_url($base);
    if (!$parts || !in_array($parts['scheme'] ?? '', ['https', 'http'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        throw new RuntimeException('base_url doit désigner l’adresse publique exacte du dossier de l’application.');
    }
    if (($settings['require_https'] ?? true) && ($parts['scheme'] !== 'https')) {
        throw new RuntimeException('La réinitialisation exige une adresse HTTPS dans base_url.');
    }
    $settings['base_url'] = $base;
    return $settings;
}

function coreenResetIsSecureRequest(array $settings): bool
{
    if (!($settings['require_https'] ?? true)) {
        return true;
    }
    // Ne pas se fier à X-Forwarded-Proto fourni par un client non approuvé.
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

function coreenResetSmtpResponse($stream, array $expected): string
{
    $data = '';
    for ($i = 0; $i < 30; $i++) {
        $line = fgets($stream, 2048);
        if ($line === false) {
            throw new RuntimeException('Réponse SMTP absente.');
        }
        $data .= $line;
        if (strlen($data) > 16000) {
            throw new RuntimeException('Réponse SMTP excessive.');
        }
        if (preg_match('/^([0-9]{3}) /', $line, $match)) {
            if (!in_array((int)$match[1], $expected, true)) {
                throw new RuntimeException('Erreur du serveur SMTP (code ' . $match[1] . ').');
            }
            return $data;
        }
    }
    throw new RuntimeException('Réponse SMTP invalide.');
}

function coreenResetSmtpCommand($stream, string $command, array $expected): string
{
    if (fwrite($stream, $command . "\r\n") === false) {
        throw new RuntimeException('Écriture SMTP impossible.');
    }
    return coreenResetSmtpResponse($stream, $expected);
}

function coreenResetSendMail(array $cfg, string $address, string $url): void
{
    $smtp = $cfg['smtp'] ?? null;
    if (!is_array($smtp)) {
        throw new RuntimeException('Section SMTP absente.');
    }
    $host = (string)($smtp['host'] ?? '');
    $port = (int)($smtp['port'] ?? 587);
    $mode = (string)($smtp['encryption'] ?? 'starttls');
    $username = (string)($smtp['username'] ?? '');
    $password = (string)($smtp['password'] ?? '');
    $from = (string)($smtp['from_email'] ?? '');
    if (!preg_match('/^[A-Za-z0-9.-]+$/D', $host) || $port < 1 || $port > 65535 ||
        !in_array($mode, ['starttls', 'ssl'], true) || $username === '' || $password === '' ||
        !filter_var($from, FILTER_VALIDATE_EMAIL) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Paramètres SMTP incomplets ou invalides.');
    }
    $ctx = stream_context_create(['ssl' => [
        'verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false,
        'peer_name' => $host,
    ]]);
    $endpoint = ($mode === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $stream = @stream_socket_client($endpoint, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $ctx);
    if (!$stream) {
        throw new RuntimeException('Impossible de joindre le relais SMTP (code ' . $errno . ').');
    }
    stream_set_timeout($stream, 15);
    try {
        coreenResetSmtpResponse($stream, [220]);
        $hello = coreenResetSmtpCommand($stream, 'EHLO raspberry-coreen', [250]);
        if ($mode === 'starttls') {
            if (stripos($hello, 'STARTTLS') === false) {
                throw new RuntimeException('Le relais SMTP ne prend pas en charge STARTTLS.');
            }
            coreenResetSmtpCommand($stream, 'STARTTLS', [220]);
            if (stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                throw new RuntimeException('Négociation TLS SMTP impossible.');
            }
            coreenResetSmtpCommand($stream, 'EHLO raspberry-coreen', [250]);
        }
        coreenResetSmtpCommand($stream, 'AUTH LOGIN', [334]);
        coreenResetSmtpCommand($stream, base64_encode($username), [334]);
        coreenResetSmtpCommand($stream, base64_encode($password), [235]);
        coreenResetSmtpCommand($stream, 'MAIL FROM:<' . $from . '>', [250]);
        coreenResetSmtpCommand($stream, 'RCPT TO:<' . $address . '>', [250, 251]);
        coreenResetSmtpCommand($stream, 'DATA', [354]);

        $subject = 'Réinitialisation du mot de passe · Vocabulaire coréen';
        $body = "Bonjour,\n\nUne demande de réinitialisation a été faite pour votre compte Vocabulaire coréen.\n\n" .
            "Ouvrez ce lien (valable 30 minutes, une seule utilisation) :\n" . $url . "\n\n" .
            "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.\n";
        $headers = [
            'From: Vocabulaire coreen <' . $from . '>',
            'To: <' . $address . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Date: ' . date(DATE_RFC2822),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'X-Auto-Response-Suppress: All',
        ];
        $email = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
        // Le contenu est encodé en base64, donc aucune ligne ne commence par « . ».
        if (fwrite($stream, $email . "\r\n.\r\n") === false) {
            throw new RuntimeException('Envoi SMTP interrompu.');
        }
        coreenResetSmtpResponse($stream, [250]);
        coreenResetSmtpCommand($stream, 'QUIT', [221]);
    } finally {
        fclose($stream);
    }
}

function coreenResetRateLimit(PDO $db, string $email): bool
{
    // Limites cumulées : 6 requêtes par IP / heure et 3 par adresse / heure.
    $keys = [
        [hash('sha256', 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')), 6],
        [hash('sha256', 'mail:' . strtolower($email)), 3],
    ];
    $allowed = true;
    $db->beginTransaction();
    try {
        $get = $db->prepare('SELECT `attempts`, `window_start`, (`window_start` >= NOW() - INTERVAL 1 HOUR) AS fresh FROM `password_reset_limits` WHERE `scope_hash` = :id FOR UPDATE');
        $insert = $db->prepare('INSERT INTO `password_reset_limits` (`scope_hash`, `attempts`, `window_start`) VALUES (:id, 1, NOW())');
        $update = $db->prepare('UPDATE `password_reset_limits` SET `attempts` = :n, `window_start` = :stamp WHERE `scope_hash` = :id');
        foreach ($keys as [$id, $limit]) {
            $get->execute([':id' => $id]);
            $row = $get->fetch(PDO::FETCH_ASSOC);
            $get->closeCursor();
            if (!$row) {
                $insert->execute([':id' => $id]);
                continue;
            }
            $fresh = (bool)(int)$row['fresh'];
            $count = $fresh ? (int)$row['attempts'] : 0;
            if ($count >= $limit) {
                $allowed = false;
            }
            $update->execute([':id' => $id, ':n' => min($count + 1, 65535), ':stamp' => $fresh ? $row['window_start'] : date('Y-m-d H:i:s')]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
    return $allowed;
}

/** Génère un jeton aléatoire, n'en stocke que l'empreinte et envoie le lien. */
function coreenResetIssue(PDO $db, array $user, ?string $recipient = null): bool
{
    if (!(int)$user['Valider']) {
        return false;
    }
    $email = $recipient !== null ? trim($recipient) : trim((string)($user['Mail1'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $cfg = coreenResetSettings();
    $token = bin2hex(random_bytes(32));
    $digest = hash('sha256', $token);
    $userId = (int)$user['User_id'];
    $db->beginTransaction();
    try {
        $old = $db->prepare('SELECT (`created_at` > NOW() - INTERVAL 5 MINUTE) AS too_recent FROM `password_reset_tokens` WHERE `User_id` = :id FOR UPDATE');
        $old->execute([':id' => $userId]);
        $current = $old->fetch(PDO::FETCH_ASSOC);
        if ($current && (int)$current['too_recent']) {
            $db->rollBack();
            return false;
        }
        $save = $db->prepare('INSERT INTO `password_reset_tokens` (`User_id`, `token_hash`, `created_at`, `expires_at`)
            VALUES (:id, :hash, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))
            ON DUPLICATE KEY UPDATE `token_hash`=VALUES(`token_hash`), `created_at`=NOW(), `expires_at`=VALUES(`expires_at`)');
        $save->execute([':id' => $userId, ':hash' => $digest]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }

    $url = $cfg['base_url'] . '/ResetPassword.php?token=' . rawurlencode($token);
    try {
        coreenResetSendMail($cfg, $email, $url);
    } catch (Throwable $e) {
        $remove = $db->prepare('DELETE FROM `password_reset_tokens` WHERE `User_id` = :id AND `token_hash` = :hash');
        $remove->execute([':id' => $userId, ':hash' => $digest]);
        error_log('[coreen-reset] Echec d’envoi SMTP : ' . $e->getMessage());
        throw $e;
    }
    return true;
}

function coreenResetFindValid(PDO $db, string $token, bool $lock = false): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
        return null;
    }
    $sql = 'SELECT t.`User_id`, u.`Login` FROM `password_reset_tokens` t
        INNER JOIN `utilisateurs` u ON u.`User_id` = t.`User_id`
        WHERE t.`token_hash` = :hash AND t.`expires_at` > NOW() AND u.`Valider` = 1 LIMIT 1';
    if ($lock) $sql .= ' FOR UPDATE';
    $q = $db->prepare($sql);
    $q->execute([':hash' => hash('sha256', $token)]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}
