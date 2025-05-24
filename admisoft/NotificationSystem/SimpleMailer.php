<?php
    namespace NotificationSystem;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    /**
     * Mailer pour gérer les envois d'e-mails suivant les événements et les cas
     */
    class Mailer {
        private string $fromEmail;
        private string $fromName;
        private string $smtpHost;
        private int $smtpPort;
        private string $smtpUser;
        private string $smtpPass;
        private bool $useSMTP;


        public function __construct(
            string $fromEmail = 'no-replay@admisoft.com',
            string $fromName  = 'AdmiSoft',
            bool $useSMTP     = true,
            string $smtpHost  = 'smtp.google.com',
            int $smtpPort     = 587,
            string $smtpUser  = 'user@google.com',
            string $smtpPass  = 'password'
        ) {
            $this->fromEmail = $fromEmail;
            $this->fromName  = $fromName;
            $this->useSMTP   = $useSMTP;
            $this->smtpHost  = $smtpHost;
            $this->smtpPort  = $smtpPort;
            $this->smtpUser  = $smtpUser;
            $this->smtpPass  = $smtpPass;
        }

        /**
         * Envoi un e-mail 
         * @param string $to
         * @param string $subject
         * @param string $body
         * @return bool
         */
        public function send(string $to, string $subject, string $body): bool {
            $mail = new PHPMailer(exceptions: true);
            try {
                if ($this->useSMTP) {
                    $mail->isSMTP();
                    $mail->Host = $this->smtpHost;
                    $mail->SMTPAuth = true;
                    $mail->Username = $this->smtpUser;
                    $mail->Password = $this->smtpPass;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $this->smtpPort;
                }

                $mail->setFrom(address: $this->fromEmail, name: $this->fromName);
                $mail->addAddress(address: $to);
                $mail->isHTML(isHtml: true);
                $mail->Subject = $subject;
                $mail->Body = $body;

                return $mail->send();
            } catch (Exception $e) {
                error_log(message: "Erreur PHPMailer: $mail->ErrorInfo, Exception: " . $e->getMessage());
                return false;
            }
        }
    }