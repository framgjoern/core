<?php

declare(strict_types=1);

namespace Stu\Component\Player\Register;

use Noodlehaus\ConfigInterface;
use Stu\Orm\Entity\UserInterface;
use Zend\Mail\Exception\RuntimeException;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail;

final class RegistrationEmailSender implements RegistrationEmailSenderInterface
{
    private $config;

    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    public function send(UserInterface $player, string $password): void
    {
        $body = <<<EOT
        Hallo %s\n\n
        Vielen Dank für Deine Anmeldung bei Star Trek Universe. Du kannst Dich nun mit folgendem Passwort und Deinem gewählten Loginnamen einloggen.\n\n
        Login:  %s\n
        Passwort:  %s\n\n
        Bitte ändere das Passwort und auch Deinen Spielernamen gleich nach Deinem ersten Login.\n
        Und nun wünschen wir Dir viel Spaß!\n\n
        Das STU-Team\n\n
        %s
        EOT;

        $mail = new Message();
        $mail->addTo($player->getEmail());
        $mail->setSubject(_('Star Trek Universe - Anmeldung'));
        $mail->setFrom($this->config->get('game.email_sender_address'));
        $mail->setBody(
            sprintf(
                $body,
                $player->getLogin(),
                $player->getLogin(),
                $password,
                $this->config->get('game.base_url')
            )
        );
        try {
            $transport = new Sendmail();
            $transport->send($mail);
        } catch (RuntimeException $e) {
            return;
        }
    }
}
