<?php
/**
 * Simple SMTP Mailer for Gmail
 * Sends emails via Gmail SMTP without requiring external libraries
 */

class SMTPMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $socket;
    
    public function __construct($host, $port, $username, $password, $encryption = 'tls') {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
    }
    
    public function send($fromEmail, $fromName, $toEmail, $toName, $subject, $body, $isHTML = true) {
        try {
            // Connect to SMTP server
            $context = stream_context_create();
            if ($this->encryption === 'tls') {
                $this->socket = stream_socket_client(
                    "tcp://{$this->host}:{$this->port}",
                    $errno,
                    $errstr,
                    30,
                    STREAM_CLIENT_CONNECT,
                    $context
                );
            } else {
                $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
            }
            
            if (!$this->socket) {
                throw new Exception("Failed to connect: $errstr ($errno)");
            }
            
            // Read server greeting
            $this->readResponse();
            
            // Send EHLO
            $this->sendCommand("EHLO " . $this->host);
            
            // Start TLS if needed
            if ($this->encryption === 'tls') {
                $this->sendCommand("STARTTLS");
                stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->sendCommand("EHLO " . $this->host);
            }
            
            // Authenticate
            $this->sendCommand("AUTH LOGIN");
            $this->sendCommand(base64_encode($this->username));
            $this->sendCommand(base64_encode($this->password));
            
            // Set sender
            $this->sendCommand("MAIL FROM: <{$fromEmail}>");
            
            // Set recipient
            $this->sendCommand("RCPT TO: <{$toEmail}>");
            
            // Send email data
            $this->sendCommand("DATA");
            
            $headers = "From: {$fromName} <{$fromEmail}>\r\n";
            $headers .= "To: {$toName} <{$toEmail}>\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            if ($isHTML) {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }
            $headers .= "\r\n";
            
            fwrite($this->socket, $headers . $body . "\r\n.\r\n");
            $this->readResponse();
            
            // Quit
            $this->sendCommand("QUIT");
            fclose($this->socket);
            
            return true;
        } catch (Exception $e) {
            if ($this->socket) {
                fclose($this->socket);
            }
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendCommand($command) {
        fwrite($this->socket, $command . "\r\n");
        return $this->readResponse();
    }
    
    private function readResponse() {
        $response = '';
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }
}

