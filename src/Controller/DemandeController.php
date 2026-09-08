<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Form\ProjetType;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DemandeController extends AbstractController
{
    // Colonnes obligatoires attendues dans le fichier data
    private const COLONNES_REQUISES = ['reference', 'designation', 'prix'];

    #[Route('/demande', name: 'app_demande')]
    public function index(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $projet = new Projet();
        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);

        $anomalies = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $typeDemande = $form->get('typeDemande')->getData();
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $dataFile */
            $dataFile = $form->get('dataFile')->getData();

            // Statut selon l'action choisie dans le menu déroulant
            $statutParType = [
                'creer' => 'nouvelle',
                'activer' => 'active',
                'desactiver' => 'desactivee',
            ];
            $projet->setStatut($statutParType[$typeDemande] ?? 'nouvelle');
            $projet->setDateCreation(new \DateTimeImmutable());

            // Numéro de projet auto-généré si vide
            if (!$projet->getNumeroProjet()) {
                $projet->setNumeroProjet('PRJ-' . date('Y') . '-' . str_pad((string) (random_int(1, 999)), 3, '0', STR_PAD_LEFT));
            }

            $projet->setCreePar($this->getUser()?->getUserIdentifier());

            if ($dataFile) {
                $anomalies = $this->verifierFichierData($dataFile->getPathname(), $dataFile->getClientOriginalExtension());

                if (!empty($anomalies)) {
                    $this->envoyerAlerteAnomalies($mailer, $projet, $anomalies);
                    $this->addFlash('warning', 'Anomalies détectées dans le fichier data : ' . implode(' | ', $anomalies));
                } else {
                    $this->addFlash('success', 'Fichier data validé sans anomalie.');
                }
            }

            $em->persist($projet);
            $em->flush();

            $this->addFlash('success', 'Demande enregistrée avec succès (N° ' . $projet->getNumeroProjet() . ').');

            return $this->redirectToRoute('app_demande');
        }

        return $this->render('demande/index.html.twig', [
            'form' => $form,
            'anomalies' => $anomalies,
        ]);
    }

    /**
     * Récupère le nom du projet en lisant le <title> de la page pointée par le lien.
     * Appelée en AJAX par le formulaire quand l'utilisateur quitte le champ "Lien".
     */
    #[Route('/api/nom-projet', name: 'api_nom_projet', methods: ['GET'])]
    public function nomProjet(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $lien = $request->query->get('lien');

        if (!$lien || !filter_var($lien, FILTER_VALIDATE_URL)) {
            return $this->json(['nom' => null, 'erreur' => 'Lien invalide.'], 400);
        }

        try {
            $response = $httpClient->request('GET', $lien, ['timeout' => 8]);
            $html = $response->getContent();

            $nom = null;
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
                $nom = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
                $nom = trim(explode('|', $nom)[0]);
            }

            return $this->json(['nom' => $nom]);
        } catch (\Throwable $e) {
            return $this->json(['nom' => null, 'erreur' => 'Impossible de récupérer le nom du projet.'], 502);
        }
    }

    /**
     * Vérifie le fichier Excel/CSV : colonnes manquantes et lignes avec prix = 0.
     *
     * @return string[] liste des messages d'anomalies (vide si tout est correct)
     */
    private function verifierFichierData(string $chemin, string $extension): array
    {
        $anomalies = [];

        try {
            $spreadsheet = IOFactory::load($chemin);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows)) {
                return ['Le fichier est vide.'];
            }

            $entetes = array_map(fn($v) => strtolower(trim((string) $v)), $rows[0]);

            // 1. Vérification des colonnes manquantes
            $colonnesManquantes = array_diff(self::COLONNES_REQUISES, $entetes);
            if (!empty($colonnesManquantes)) {
                $anomalies[] = 'Colonne(s) manquante(s) : ' . implode(', ', $colonnesManquantes);
            }

            // 2. Vérification prix = 0 (seulement si la colonne prix existe)
            $indexPrix = array_search('prix', $entetes, true);
            if ($indexPrix !== false) {
                $lignesPrixZero = [];
                foreach (array_slice($rows, 1) as $i => $row) {
                    $valeur = $row[$indexPrix] ?? null;
                    if ($valeur === 0 || $valeur === '0' || $valeur === 0.0) {
                        $lignesPrixZero[] = $i + 2; // +2 = ligne réelle dans le fichier (en-tête = ligne 1)
                    }
                }
                if (!empty($lignesPrixZero)) {
                    $anomalies[] = 'Prix égal à 0 détecté ligne(s) : ' . implode(', ', $lignesPrixZero);
                }
            }
        } catch (\Throwable $e) {
            $anomalies[] = 'Impossible de lire le fichier : ' . $e->getMessage();
        }

        return $anomalies;
    }

    private function envoyerAlerteAnomalies(MailerInterface $mailer, Projet $projet, array $anomalies): void
    {
        $email = (new Email())
            ->from('plateforme@al-omrane.local')
            ->to('direction@al-omrane.local')
            ->subject('Anomalie détectée - Projet ' . ($projet->getNumeroProjet() ?? 'N/A'))
            ->text(
                "Une anomalie a été détectée lors de l'import du fichier data.\n\n" .
                "Projet : " . ($projet->getNumeroProjet() ?? 'N/A') . "\n" .
                "Anomalies :\n- " . implode("\n- ", $anomalies)
            );

        $mailer->send($email);
    }
}