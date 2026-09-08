<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Form\ProjetEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HistoriqueController extends AbstractController
{
    #[Route('/historique', name: 'app_historique')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $statutFiltre = $request->query->get('statut');
        $recherche = trim((string) $request->query->get('q', ''));

        $qb = $em->getRepository(Projet::class)->createQueryBuilder('p')
            ->orderBy('p.dateCreation', 'DESC');

        if ($statutFiltre === 'termine') {
            $qb->andWhere('p.dateFin IS NOT NULL');
        } elseif ($statutFiltre === 'incomplet') {
            $qb->andWhere('p.dateFin IS NULL');
        } else {
            $statutFiltre = null;
        }

        if ($recherche !== '') {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('LOWER(p.numeroProjet)', ':recherche'),
                $qb->expr()->like('LOWER(p.nomProjet)', ':recherche'),
                $qb->expr()->like('LOWER(p.lienProjet)', ':recherche')
            ))->setParameter('recherche', '%' . mb_strtolower($recherche) . '%');
        } else {
            $recherche = null;
        }

        $projets = $qb->getQuery()->getResult();

        return $this->render('historique/index.html.twig', [
            'projets' => $projets,
            'statutFiltre' => $statutFiltre,
            'recherche' => $recherche,
        ]);
    }

    #[Route('/historique/valider/{id}', name: 'app_historique_valider')]
    #[IsGranted('ROLE_ADMIN')]
    public function valider(Projet $projet, EntityManagerInterface $em): Response
    {
        $projet->setDateFin(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Projet ' . $projet->getNumeroProjet() . ' validé, date de fin enregistrée.');

        return $this->redirectToRoute('app_historique');
    }

    #[Route('/historique/supprimer/{id}', name: 'app_historique_supprimer')]
    #[IsGranted('ROLE_ADMIN')]
    public function supprimer(Projet $projet, EntityManagerInterface $em): Response
    {
        $numero = $projet->getNumeroProjet();
        $em->remove($projet);
        $em->flush();

        $this->addFlash('success', 'Projet ' . $numero . ' supprimé.');

        return $this->redirectToRoute('app_historique');
    }

    #[Route('/historique/{id}', name: 'app_historique_detail', requirements: ['id' => '\d+'])]
    public function detail(Projet $projet): Response
    {
        return $this->render('historique/detail.html.twig', [
            'projet' => $projet,
        ]);
    }

    #[Route('/historique/{id}/modifier', name: 'app_historique_modifier', requirements: ['id' => '\d+'])]
    public function modifier(Projet $projet, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $estAutorise = $this->isGranted('ROLE_ADMIN')
            || ($user && $user->getUserIdentifier() === $projet->getCreePar());

        if (!$estAutorise) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette demande.');
        }

        $form = $this->createForm(ProjetEditType::class, $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Projet ' . $projet->getNumeroProjet() . ' modifié.');
            return $this->redirectToRoute('app_historique_detail', ['id' => $projet->getId()]);
        }

        return $this->render('historique/modifier.html.twig', [
            'form' => $form,
            'projet' => $projet,
        ]);
    }

    #[Route('/historique/{id}/commentaire', name: 'app_historique_commentaire', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function commentaire(Projet $projet, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $estAutorise = $this->isGranted('ROLE_ADMIN')
            || ($user && $user->getUserIdentifier() === $projet->getCreePar());

        if (!$estAutorise) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas commenter cette demande.');
        }

        if (!$this->isCsrfTokenValid('commentaire' . $projet->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $projet->setCommentaire($request->request->get('commentaire'));
        $em->flush();

        $this->addFlash('success', 'Commentaire enregistré.');

        return $this->redirectToRoute('app_historique_detail', ['id' => $projet->getId()]);
    }
}