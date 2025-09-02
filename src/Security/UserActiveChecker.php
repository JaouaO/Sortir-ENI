<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserActiveChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) return;

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Votre compte est désactivé.');
        }

    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Vérifications après authentification si nécessaire
    }
}
