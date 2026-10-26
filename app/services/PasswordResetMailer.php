<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envía enlaces de recuperación usando el SMTP configurado en el entorno.
 */
final class PasswordResetMailer
{
    private string $appUrl;
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromAddress;
    private string $fromName;

    /**
     * Lee y valida APP_URL, SMTP y la dirección remitente desde el entorno.
     *
     * @throws RuntimeException Si falta o no es válida una variable requerida.
     */
    public function __construct()
    {
        $this->appUrl = rtrim((string) getenv('APP_URL'), '/');
        $urlParts = parse_url($this->appUrl);
        if (!filter_var($this->appUrl, FILTER_VALIDATE_URL)
            || !is_array($urlParts)
            || !in_array(strtolower((string) ($urlParts['scheme'] ?? '')), ['http', 'https'], true)
            || empty($urlParts['host'])) {
            throw new RuntimeException('APP_URL debe ser una URL HTTP o HTTPS completa.');
        }

        $this->host = trim((string) getenv('SMTP_HOST'));
        $this->port = (int) (getenv('SMTP_PORT') ?: 587);
        $this->username = (string) getenv('SMTP_USERNAME');
        $this->password = (string) getenv('SMTP_PASSWORD');
        $this->encryption = strtolower(trim((string) (getenv('SMTP_ENCRYPTION') ?: 'tls')));
        $this->fromAddress = trim((string) getenv('MAIL_FROM_ADDRESS'));
        $this->fromName = trim((string) (getenv('MAIL_FROM_NAME') ?: 'Formación Continua'));

        if ($this->host === ''
            || $this->username === ''
            || $this->password === ''
            || $this->port < 1
            || $this->port > 65535
            || !in_array($this->encryption, ['tls', 'ssl'], true)
            || !filter_var($this->fromAddress, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('La configuración SMTP de recuperación está incompleta o no es válida.');
        }
    }

    /**
     * Envía el enlace sin exponer el token en recursos de terceros.
     */
    public function send(string $email, string $name, string $token): void
    {
        $resetUrl = $this->appUrl . '/password/restablecer?token=' . rawurlencode($token);
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $this->host;
        $mailer->SMTPAuth = true;
        $mailer->Username = $this->username;
        $mailer->Password = $this->password;
        $mailer->SMTPSecure = $this->encryption === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = $this->port;
        $mailer->Timeout = 10;
        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->setFrom($this->fromAddress, $this->fromName);
        $mailer->addAddress($email, $name);
        $mailer->isHTML(true);
        $mailer->Subject = 'Recupera tu contraseña';
        $mailer->Body = '<p>Hola ' . $safeName . ',</p>'
            . '<p>Recibimos una solicitud para cambiar la contraseña de tu cuenta.</p>'
            . '<p><a href="' . $safeUrl . '">Crear una nueva contraseña</a></p>'
            . '<p>El enlace vence en una hora y solo puede utilizarse una vez. '
            . 'Si no solicitaste el cambio, puedes ignorar este mensaje.</p>';
        $mailer->AltBody = "Hola {$name},\n\n"
            . "Para crear una nueva contraseña, visita este enlace (vence en una hora):\n{$resetUrl}\n\n"
            . 'Si no solicitaste el cambio, ignora este mensaje.';
        $mailer->send();
    }
}
