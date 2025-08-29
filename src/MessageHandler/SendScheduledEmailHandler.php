<?php

namespace App\MessageHandler;

use App\Entity\ScheduledEmail;
use App\Message\SendScheduledEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendScheduledEmailHandler
{
    public function __construct(private EntityManagerInterface $em, private MailerInterface $mailer) {}

    public function __invoke(SendScheduledEmail $message): void
    {
        $scheduledEmail = $this->em->getRepository(ScheduledEmail::class)->find($message->scheduledEmailID);
        if (!$scheduledEmail || $scheduledEmail->getStatus() !== 'pending') {
            return;
        }

        if($scheduledEmail->getSendAt() > new \DateTimeImmutable('now')) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from('mailer@campus-eni.fr')
            ->to($scheduledEmail->getEmail())
            ->subject($scheduledEmail->getSubject())
            ->htmlTemplate($scheduledEmail->getTemplate())
            ->context($scheduledEmail->getContext());


        $this->mailer->send($email);

        $scheduledEmail->setStatus('sent');
        $this->em->flush();

    }

}