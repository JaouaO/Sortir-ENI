<?php

namespace App\Service;

use App\Entity\State;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class EventService {

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function eventFilter (array $params, $user) : array {
        $filters = [];

        if (isset($params['site']) && !empty($params['site'])) {
            $filters['site'] = $params['site'];
        }

        if (isset($params['search']) && !empty($params['search'])) {
            $filters['search'] = $params['search'];
        }

        if (isset($params['dateStart']) && !empty($params['dateStart'])) {
            // Check Beginning date + Date Time format
            $startAtTime = new DateTime($params['dateStart']);
            $filters['dateStart'] = $startAtTime;
        }

        if (isset($params['dateEnd']) && !empty($params['dateEnd'])) {
            // Check End date + Date Time format
            $endAtTime = new DateTime($params['dateEnd']);
            $filters['dateEnd'] = $endAtTime;
        }


        // Checkbox "Sorties dont je suis l'organisateur/trice"
        if (isset($params['organizer'])){
            $filters['organizer'] = $user;
        }

        // Checkbox "Sorties auxquelles je suis inscrit/e"
        if (isset($params['registered'])) {
            $filters['registered'] = $user;
        }

        // Checkbox "Sorties auxquelles je ne suis pas inscrit/e"
        if (isset($params['notRegistered'])) {
            $filters['notRegistered'] = $user;
        }

        // Checkbox "Sorties passées"
        if (isset($params['outingPast'])) {
            $filters['outingPast'] = new DateTime();
        }

        return $filters;
    }

    public function determinestate($event, $nbRegisteredByEvent, $eventId, $today): ?State
    {
        $startDate = $event->getStartDateTime();
        $endDate = $event->getEndDateTime();
        $maxParticipants = $event->getMaxParticipants();

        $stateRepository = $this->em->getRepository(State::class);

        if ($endDate < $today) {
            // Past events
            $stateEntity = $stateRepository->find(17);
        } elseif ($startDate <= $today && $endDate >= $today) {
            // Ongoing events
            $stateEntity = $stateRepository->find(16);
        } elseif ($startDate > $today && $nbRegisteredByEvent[$eventId] >= $maxParticipants) {
            // closed registrations on coming events
            $stateEntity = $stateRepository->find(15);
        } else {
            // Open registrations on coming events
            $stateEntity = $stateRepository->find(14);
        }
        
        return $stateEntity;
        
    }

}