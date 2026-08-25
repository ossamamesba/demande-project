<?php

namespace App\Controller;

use App\Entity\Projet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HistoriqueController extends AbstractController
{
    #[Route('/historique', name: 'app_historique')]
    public function index(EntityManagerInterface $em): Response
    {
        $projets = $em->getRepository(Projet::class)->findBy([], ['dateCreation' => 'DESC']);

        return $this->render('historique/index.html.twig', [
            'projets' => $projets,
        ]);
    }

    #[Route('/historique/valider/{id}', name: 'app_historique_valider')]
    public function valider(Projet $projet, EntityManagerInterface $em): Response
    {
        $projet->setDateFin(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Projet ' . $projet->getNumeroProjet() . ' validé, date de fin enregistrée.');

        return $this->redirectToRoute('app_historique');
    }
}