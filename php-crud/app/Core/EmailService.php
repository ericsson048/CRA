<?php
declare(strict_types=1);

namespace App\Core;

final class EmailService
{
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        if (!AppConfig::emailEnabled()) {
            Logger::info('Email ignore: configuration SMTP incomplete', ['to' => $toEmail, 'subject' => $subject]);
            return false;
        }

        $transport = AppConfig::emailImplicitTls() ? 'ssl://' : '';
        $socket = @stream_socket_client(
            $transport . AppConfig::emailHost() . ':' . AppConfig::emailPort(),
            $errorCode,
            $errorMessage,
            20,
            STREAM_CLIENT_CONNECT
        );

        if ($socket === false) {
            Logger::error('Connexion SMTP impossible', ['error' => $errorMessage, 'code' => $errorCode]);
            return false;
        }

        stream_set_timeout($socket, 20);

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO localhost', [250]);

            if (AppConfig::emailUseTls() && !AppConfig::emailImplicitTls()) {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Activation TLS impossible.');
                }
                $this->command($socket, 'EHLO localhost', [250]);
            }

            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode(AppConfig::emailUsername()), [334]);
            $this->command($socket, base64_encode(AppConfig::emailPassword()), [235]);
            $this->command($socket, 'MAIL FROM:<' . AppConfig::emailFromAddress() . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $body = $this->buildMimeMessage($toEmail, $toName, $subject, $htmlBody, $textBody);
            fwrite($socket, $body . "\r\n.\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
            fclose($socket);
            return true;
        } catch (\Throwable $exception) {
            Logger::error('Echec envoi email SMTP', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
            fclose($socket);
            return false;
        }
    }

    private function buildMimeMessage(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $textBody): string
    {
        $boundary = 'bnd_' . bin2hex(random_bytes(8));
        $textBody = $textBody ?? trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));

        $headers = [
            'From: ' . AppConfig::emailFromName() . ' <' . AppConfig::emailFromAddress() . '>',
            'To: ' . $toName . ' <' . $toEmail . '>',
            'Subject: ' . $this->mimeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $parts = [];
        $parts[] = '--' . $boundary;
        $parts[] = 'Content-Type: text/plain; charset=UTF-8';
        $parts[] = 'Content-Transfer-Encoding: base64';
        $parts[] = '';
        $parts[] = chunk_split(base64_encode($textBody));
        $parts[] = '--' . $boundary;
        $parts[] = 'Content-Type: text/html; charset=UTF-8';
        $parts[] = 'Content-Transfer-Encoding: base64';
        $parts[] = '';
        $parts[] = chunk_split(base64_encode($htmlBody));
        $parts[] = '--' . $boundary . '--';

        return implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $parts);
    }

    private function mimeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    /**
     * @param array<int, int> $codes
     */
    private function command($socket, string $command, array $codes): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $codes);
    }

    /**
     * @param array<int, int> $codes
     */
    private function expect($socket, array $codes): void
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        $status = (int)substr($response, 0, 3);
        if (!in_array($status, $codes, true)) {
            throw new \RuntimeException('SMTP response invalide: ' . trim($response));
        }
    }
}
