<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventCancelType;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sortie', name: 'event')]
final class EventController extends AbstractController
{
    #[Route('/creer', name: '_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();

        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Une nouvelle sortie a été créée');
            return $this->redirectToRoute('home');
            #return $this->redirectToRoute('event_display', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig', [
            'event_form' => $form,
            'mode' => 'create'
        ]);
    }

    #[Route('/{id}/modifier', name: '_edit', requirements: ['id' => '\d+'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {

        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Une sortie à été modifiée avec succès');

            return $this->render('event_display', ['id' => $event->getId()]);
        }


        return $this->render('event/edit.html.twig', [
            'event_form' => $form,
            'mode' => 'edit'
        ]);
    }

    #[Route('/{id}/annuler', name: '_cancel')]
    //#[IsGranted('ROLE_ORGANISATEUR')]
    public function cancel(
        Event                  $event,
        Request                $request,
        EntityManagerInterface $em,
        StateRepository        $stateRepository
    ): Response
    {
        $form = $this->createForm(EventCancelType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cancelState = $stateRepository->findOneBy(['description' => 'Annulée']);
            $event->setState($cancelState);

            $em->flush();

            $this->addFlash('danger', 'La sortie a été annulé.');
            return $this->redirectToRoute('home');
        }

        return $this->render('event/cancel.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: '_display')]
    public function display(Event $event): Response
    {

        $state = $event->getState();

        return $this->render('event/display.html.twig', ['event' => $event, 'state' => $state->getDescription()]);
    }

    #[Route('/{id}/inscription', name: '_register')]
    public function register(Event $event, EntityManagerInterface $em): Response
    {

        $user = $this->getUser();
        $state = $event->getState();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.e pour vous inscrire.');
        }

        if ($event->getRegisteredParticipants()->contains($user)) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit.e à cette sortie.');
        } elseif (count($event->getRegisteredParticipants()) >= $event->getMaxParticipants()) {
            $this->addFlash('danger', 'Le nombre maximum de participants est atteint.');
        } elseif ($event->getRegistrationDeadline() < new \DateTimeImmutable()) {
            $this->addFlash('danger', 'La date limite d’inscription est passée.');
        }elseif ($state->getDescription() == 'Annulée') {
            $this->addFlash('danger', 'La sortie a été annullée.');
        }else {
            $event->addRegisteredParticipant($user);
            $em->flush();
            $this->addFlash('success', 'Vous êtes bien inscrit.e à la sortie!');
        }

        return $this->redirectToRoute('event_display', ['id' => $event->getId()]);
    }

    #[Route('/{id}/desinscription', name: '_unregister')]
    public function unregister(Event $event, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($event->getRegisteredParticipants()->contains($user)) {
            $this->addFlash('warning', 'Vous vous êtes bien désinscrit.e de cette sortie.');
            $event->removeRegisteredParticipant($user);
            $em->flush();
            return $this->redirectToRoute('event_display', ['id' => $event->getId()]);
        }else{
            $this->addFlash('warning', 'Vous n\'êtes pas inscrit.e à cette sortie.');
            return $this->redirectToRoute('event_display', ['id' => $event->getId()]);
        }
    }

}
