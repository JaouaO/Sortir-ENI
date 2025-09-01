<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\State;
use App\Repository\EventRepository;
use App\Service\EventService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    private EventRepository $eventRepository;
    private EntityManagerInterface $em;
    private EventService $eventService;

    public function __construct(EventRepository $eventRepository, EventService $eventService, EntityManagerInterface $em)
    {
        $this->eventRepository = $eventRepository;
        $this->eventService = $eventService;
        $this->em = $em;
    }


    #[Route(['/', '/accueil'], name: 'home')]
    public function index(Request $request): Response
    {
        global $state;
        $user = $this->getUser(); //connected user
        $nbRegisteredByEvent = [];
        $isUserEventRegistered = [];

        $siteRepository = $this->em->getRepository(Site::class);
        $sites = $siteRepository->findAll();

        $params = $request->query->all();
        //call filters set in EventService
        $filters = $this->eventService->eventFilter($params, $user);
        //call requests in Event Repo
        $events = $this->eventRepository->queryFilters($filters);
        $today = new \DateTimeImmutable();// today

        foreach ($events as $event) {

            $eventId = $event->getId();
            $nbRegisteredByEvent[$eventId] = $event->getRegisteredParticipants()->count();
            $isUserEventRegistered[$eventId] = $event->getRegisteredParticipants()->contains($user);

            // Update status
            $stateEntity = $this->eventService->determineState($event, $nbRegisteredByEvent, $eventId, $today);
            if ($stateEntity) {
                $event->setState($stateEntity);
                $this->em->persist($event);
            } else {
                throw new \RuntimeException("État introuvable pour l'ID donné.");
            }
            $this->em->flush();
        }



            return $this->render('main/index.html.twig', [
                'user' => $user,
                'events' => $events,
                'sites' => $sites,
                'nbRegisteredByEvent' => $nbRegisteredByEvent,
                'isUserEventRegistered' => $isUserEventRegistered,
            ]);
        }
}
 

 