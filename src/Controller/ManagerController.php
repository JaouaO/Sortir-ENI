<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Site;
use App\Repository\CityRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\DocBlock\Tags\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/gestion', name: 'manager')]
#[IsGranted('ROLE_ADMINISTRATEUR')]
final class ManagerController extends AbstractController
{
    #[Route('/villes', name: '_cities', methods: ['GET'])]
    public function cities(CityRepository $cityRepository, Request $request): Response
    {
        $searchTerm = $request->query->get('search');
        $cities = $cityRepository->findCities($searchTerm);

        return $this->render('manager/cities.html.twig', [
            'cities' => $cities,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/creer', name: '_create')]
    public function createCity(
        Request $request,
        EntityManagerInterface $em,
    ): Response {

            $city = new City();

        if ($request->isMethod('POST')) {
            $town = $request->request->get('town');
            $cp = $request->request->get('cp');

            if (empty($town)) {
                $this->addFlash('error', "Le nom de la ville est obligatoire.");
            } elseif($town != $city->getName()){
                $this->addFlash('error', "La ville est déjà présente");
            } elseif (empty($cp)) {
                $this->addFlash('error', "Le code postal est obligatoire.");
            } elseif (!preg_match('/^\d{5}$/', $cp)) {
                $this->addFlash('error', "Le code postal doit comporter 5 chiffres.");
            } else {

                $city->setName($town);
                $city->setPostCode($cp);

                $em->persist($city);
                $em->flush();

                $this->addFlash('success', "La ville {$city->getName()} a été ajoutée");
                return $this->redirectToRoute('manager_cities');
            }
        }

        return $this->render('manager/update-city.html.twig', [
            'mode' => 'create',
            'city' => $city, // Pour pré-remplir le formulaire
        ]);
    }

    #[Route('/{id}/modifier', name: '_update', requirements: ['id' => '\d+'])]
    public function updateCity(
        Request $request,
        City $city,
        EntityManagerInterface $em,
        ): Response {

        if ($request->isMethod('POST')) {
            $town = $request->request->get('town');
            $cp = $request->request->get('cp');

            if (empty($town)) {
                $this->addFlash('error', "Le nom de la ville est obligatoire.");
            } elseif($town != $city->getName()){
                $this->addFlash('error', "La ville est déjà présente");
            } elseif (empty($cp)) {
                $this->addFlash('error', "Le code postal est obligatoire.");
            } elseif (!preg_match('/^\d{5}$/', $cp)) {
                $this->addFlash('error', "Le code postal doit comporter 5 chiffres.");
            } else {

                $city->setName($town);
                $city->setPostCode($cp);

                $em->persist($city);
                $em->flush();

                $this->addFlash('success', "La ville {$city->getName()} a été ajoutée");
                return $this->redirectToRoute('manager_cities');
            }
        }

        return $this->render('manager/update-city.html.twig', [
            'mode' => 'update',
            'city' => $city, // Pour pré-remplir le formulaire
        ]);
    }

    #[Route('/{id}/supprimer', name: '_delete', requirements: ['id' => '\d+'])]
    public function deleteCity(int $id, EntityManagerInterface $em): Response {

        $city = $em->getRepository(City::class)->find($id);

        if(!$city){
            $this->addFlash('danger', 'La ville n\'existe pas');
            return $this->redirectToRoute('manager_cities');
        }

        $em->remove($city);
        $em->flush();

        $this->addFlash('success', "La ville {$city->getName()} a été supprimé");
        return $this->redirectToRoute('manager_cities');
    }



    // PARTIE CAMPUS

    #[Route('/campus', name: '_sites')]
    public function places(SiteRepository $siteRepository, Request $request): Response
    {
        $searchSite = $request->query->get('search');
        $sites = $siteRepository->findSites($searchSite);

        return $this->render('manager/sites.html.twig', [
            'sites' => $sites,
            'searchSite' => $searchSite
        ]);

        return $this->render('manager/sites.html.twig');
    }


    #[Route('/creer-campus', name: '_create_site')]
    public function createSite(
        Request $request,
        EntityManagerInterface $em,
    ): Response {

        $site = new Site();

        if ($request->isMethod('POST')) {
            $town = $request->request->get('town');

            if (empty($town)) {
                $this->addFlash('error', "Le nom du campus est obligatoire.");
            }elseif($town != $site->getName()){
                $this->addFlash('error', "Le campus est déjà présent");
            } else {

                $site->setName($town);

                $em->persist($site);
                $em->flush();

                $this->addFlash('success', "Le campus {$site->getName()} a été ajouté");
                return $this->redirectToRoute('manager_sites');
            }
        }

        return $this->render('manager/update-site.html.twig', [
            'mode' => 'create',
            'site' => $site, // Pour pré-remplir le formulaire
        ]);
    }

    #[Route('/{id}/modifier-campus', name: '_update_site', requirements: ['id' => '\d+'])]
    public function updateSite(
        Request $request,
        EntityManagerInterface $em,
        Site $site,
    ): Response {

        if ($request->isMethod('POST')) {
            $town = $request->request->get('town');

            if (empty($town)) {
                $this->addFlash('error', "Le nom du campus est obligatoire.");
            }elseif($town != $site->getName()){
                $this->addFlash('error', "Le campus est déjà présent");
            } else {

                $site->setName($town);

                $em->persist($site);
                $em->flush();

                $this->addFlash('success', "Le campus {$site->getName()} a été ajouté");
                return $this->redirectToRoute('manager_sites');
            }
        }

        return $this->render('manager/update-site.html.twig', [
            'mode' => 'update',
            'site' => $site, // Pour pré-remplir le formulaire
        ]);
    }

    #[Route('/{id}/supprimer-campus', name: '_delete_site', requirements: ['id' => '\d+'])]
    public function deleteSite(int $id, EntityManagerInterface $em): Response {

        $site = $em->getRepository(Site::class)->find($id);

        if(!$site){
            $this->addFlash('danger', 'Le campus n\'existe pas');
            return $this->redirectToRoute('manager_sites');
        }

        $em->remove($site);
        $em->flush();

        $this->addFlash('success', "Le campus {$site->getName()} a été supprimé");
        return $this->redirectToRoute('manager_sites');
    }

}
