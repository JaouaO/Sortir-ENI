<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\ScheduledEmail;
use App\Message\SendScheduledEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class EmailController extends AbstractController
{
    #[Route('/event/{id}/schedule-email', name: 'schedule_email')]
    public function index(int $id, MessageBusInterface $bus, EntityManagerInterface $em): Response
    {
            $user = $this->getUser();
            $event = $em->getRepository(Event::class)->find($id);
            $scheduledEmail = new ScheduledEmail();
            $scheduledEmail->setEvent($event)
                ->setEmail($user->getEmail())
                ->setTemplate('email/event-soon.html.twig')
                ->setSubject('rappel')
                ->setContext([
                    'event_name' => $event->getName(),
                    'event_date' => $event->getStartDateTime()->format('d/m/Y H:i'),
                    'user_name' => $user->getName(),
                ]);

            $scheduledEmail->setSendAt(new \DateTimeImmutable());

            $em->persist($scheduledEmail);
            $em->flush();

            $bus->dispatch(new SendScheduledEmail($scheduledEmail->getId()));

            $this->addFlash('success', 'email-sent');
            return $this->redirectToRoute('event_display', ['id' => $event->getId()]);


        }

}
