<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\User;
use App\Form\UserImportType;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration', name: 'admin')]
final class AdminController extends AbstractController
{
    #[Route('/interface', name: '_interface')]
    public function index(EventRepository $eventRepository, UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $events = $eventRepository->findAll();


        return $this->render('admin/interface.html.twig', ['events' => $events, 'users' => $users]);
    }

    #[Route('/{id}/desactiver', name: '_desactivate')]
    public function desactivate(User $user, EntityManagerInterface $em,  MailerInterface $mailer): Response
    {

        $user->setIsActive(false);

        foreach ($user->getRegisteredEvents() as $event) {
            $event->removeRegisteredParticipant($user);
        }


        $email = (new TemplatedEmail())
            ->from(new Address('mailer@campus-eni.fr', 'ENI MAIL BOT'))
            ->to((string) $user->getEmail())
            ->subject('You have been deactivated')
            ->htmlTemplate('email/deactivate.html.twig')
            ->context([
                'user' => $user,
            ])
          ;

        $mailer->send($email);

        $em->flush();



        $this->addFlash('success', "L'utilisateur {$user->getName()} a bien été désactivé.");
        return $this->redirectToRoute('admin_interface');
    }

    #[Route('/{id}/reactiver', name: '_reactivate')]
    public function reactivate(User $user, EntityManagerInterface $em): Response
    {

        $user->setIsActive(true);
        $em->flush();

        $this->addFlash('success', "L'utilisateur {$user->getName()} a bien été réactivé.");
        return $this->redirectToRoute('admin_interface');
    }

    #[Route('/{id}/supprimer', name: '_delete')]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('danger', "L'utilisateur avec l'ID $id n'existe pas.");
            return $this->redirectToRoute('admin_interface');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', "L'utilisateur {$user->getName()} a bien été supprimé.");
        return $this->redirectToRoute('admin_interface');
    }

    #[Route('/import', name: '_import')]
    public function import(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $form = $this->createForm(UserImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('csvFile')->getData();

            if (!$file) {
                $this->addFlash('error', 'Fichier non trouvé.');
                return $this->redirectToRoute('admin_import');
            }

            if (($handle = fopen($file->getPathname(), 'r')) !== false) {

                // Lecture de l'entête et suppression éventuelle du BOM
                $headerLine = fgets($handle);
                $headerLine = preg_replace('/\x{FEFF}/u', '', $headerLine); // supprime BOM UTF-8
                $header = str_getcsv($headerLine, ';');
                $header = array_map('trim', $header);

                $importedCount = 0;
                $skippedCount  = 0;
                $errors        = [];

                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    $data = array_map('trim', $data);

                    // Ignorer les lignes vides
                    if (empty(array_filter($data))) {
                        $skippedCount++;
                        continue;
                    }

                    // Vérifier que le nombre de colonnes correspond au header
                    if (count($data) !== count($header)) {
                        $skippedCount++;
                        $errors[] = 'Colonnes != header : ' . implode(';', $data);
                        continue;
                    }

                    $row = array_combine($header, $data);
                    if (!$row || empty($row['email'])) {
                        $skippedCount++;
                        $errors[] = 'Email manquant ou array_combine échoué : ' . implode(';', $data);
                        continue;
                    }

                    try {
                        $user = new User();
                        $user->setEmail($row['email']);
                        $user->setPseudo($row['pseudo']);
                        $user->setName($row['name']);
                        $user->setFirstName($row['firstName']);
                        $user->setPhone($row['phone']);

                        $roles = !empty($row['roles']) ? explode(',', $row['roles']) : ['ROLE_USER'];
                        $user->setRoles($roles);

                        $password = !empty($row['password']) ? $row['password'] : '123456';
                        $hashedPassword = $passwordHasher->hashPassword($user, $password);
                        $user->setPassword($hashedPassword);

                        $user->setIsAdmin((bool)$row['isAdmin']);
                        $user->setIsActive((bool)$row['isActive']);

                        $site = $em->getRepository(Site::class)->findOneBy(['name' => $row['site']]);
                        if ($site) {
                            $user->setSite($site);
                        } else {
                            $errors[] = "Site introuvable pour l'utilisateur {$row['email']} : {$row['site']}";
                        }

                        $em->persist($user);
                        $importedCount++;

                    } catch (\Exception $e) {
                        $skippedCount++;
                        $errors[] = "Erreur pour l'utilisateur {$row['email']}: " . $e->getMessage();
                    }
                }

                fclose($handle);

                try {
                    $em->flush();
                } catch (\Exception $e) {
                    $errors[] = "Erreur lors du flush : " . $e->getMessage();
                }

                // Construction du message de rapport
                $message = "Import terminé. Utilisateurs importés : $importedCount. Lignes ignorées : $skippedCount.";
                if (!empty($errors)) {
                    $message .= " Erreurs : " . implode(' | ', $errors);
                }

                $this->addFlash('success', $message);

                return $this->redirectToRoute('admin_import');
            }
        }

        return $this->render('admin/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }


}