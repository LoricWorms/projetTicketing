<?php

namespace App\Controller;

use App\Entity\Devis;
use App\Form\DevisType;
use App\Service\GoogleSheetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class GoogleSheetsBisController
 * 
 * Ce contrôleur gère les opérations liées aux devis, y compris la création, la liste, la modification et la suppression,
 * en utilisant Google Sheets comme backend.
 */
class GoogleSheetsBisController extends AbstractController
{
    private GoogleSheetsService $googleSheetsService;
    private CacheInterface $cache;
    private const SPREADSHEET_ID = '1YK_RWejtfBeROGt2-KEmM0W_SRnP2O8LtQmr3pZqzSM';

    /**
     * GoogleSheetsBisController constructor.
     * 
     * @param GoogleSheetsService $googleSheetsService
     * @param CacheInterface $cache
     */
    public function __construct(GoogleSheetsService $googleSheetsService, CacheInterface $cache)
    {
        $this->googleSheetsService = $googleSheetsService;
        $this->cache = $cache;
    }

    /**
     * Liste tous les devis.
     * 
     * @return Response
     */
    #[Route('/devis', name: 'list_devis')]
    public function listDevis(): Response
    {
        $range = 'Sheet2!A2:I'; // Plage à lire, ajustez selon les besoins

        // Utilisation du cache pour éviter des appels répétés
        $devis = $this->cache->get('devis_list', function (ItemInterface $item) use ($range) {
            $item->expiresAfter(3600); // Le cache expire après 1 heure
            return $this->googleSheetsService->readSheet(self::SPREADSHEET_ID, $range);
        });

        return $this->render('devis/list.html.twig', [
            'devis' => $devis,
        ]);
    }

    /**
     * Liste les devis archivés.
     * 
     * @return Response
     */
    #[Route('/devis/archive', name: 'devis_archive')]
    public function listArchive(): Response
    {
        $range = 'Archive2!A2:I'; // Plage à lire, ajustez selon les besoins

        // Utilisation du cache pour éviter des appels répétés
        $devis = $this->cache->get('archive_devis_list', function (ItemInterface $item) use ($range) {
            $item->expiresAfter(3600); // Le cache expire après 1 heure
            return $this->googleSheetsService->readSheet(self::SPREADSHEET_ID, $range);
        });

        return $this->render('devis/archive.html.twig', [
            'devis' => $devis,
        ]);
    }

    /**
     * Crée un nouveau devis.
     * 
     * @param Request $request
     * @return Response
     */
    #[Route('/devis/new', name: 'new_devis')]
    public function new_devis(Request $request): Response
    {
        $devis = new Devis();
        $form = $this->createForm(DevisType::class, $devis);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->googleSheetsService->addTicket(self::SPREADSHEET_ID, $this->devisToArray($devis), 'Sheet2!A1:A');
            $this->addFlash('success', 'Devis ajouté avec succès !');

            // Invalider le cache
            $this->cache->delete('devis_list');

            return $this->redirectToRoute('list_devis');
        }

        return $this->render('ticket/new.html.twig', [
            'form' => $form->createView(),
            'titre' => "Devis",
            'retour' => "list_devis"
        ]);
    }

    /**
     * Supprime un devis par son ID.
     * 
     * @param int $id
     * @return Response
     */
    #[Route('/delete-devis/{id}', name: 'delete_devis')]
    public function deleteDevisAction(int $id): Response
    {
        try {
            $this->googleSheetsService->delete(self::SPREADSHEET_ID, $id, 1);
            $this->addFlash('success', 'Devis supprimé avec succès !');

            // Invalider le cache
            $this->cache->delete('devis_list');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du ticket : ' . $e->getMessage());
        }

        return $this->redirectToRoute('list_devis');
    }

    /**
     * Modifie un devis existant.
     * 
     * @param Request $request
     * @param int $id
     * @return Response
     */
    #[Route('/edit-devis/{id}', name: 'edit_devis')]
    public function editDevisAction(Request $request, int $id): Response
    {
        $devis = $this->getDevisById($id);
        if (!$devis) {
            throw $this->createNotFoundException('Ticket non trouvé.');
        }

        $form = $this->createForm(DevisType::class, $devis);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->googleSheetsService->updateDevis(self::SPREADSHEET_ID, $id, $form->getData());
            $this->addFlash('success', 'Devis mis à jour avec succès !');

            // Invalider le cache
            $this->cache->delete('devis_list');

            return $this->redirectToRoute('list_devis');
        }

        return $this->render('ticket/edit.html.twig', [
            'form' => $form->createView(),
            'titre' => "Devis",
            'retour' => "list_devis"
        ]);
    }

    /**
     * Archive un devis par son ID.
     * 
     * @param int $id
     * @return Response
     */
    #[Route("/archive-devis/{id}", name: "archive_devis")]
    public function archiveTicket(int $id): Response
    {
        try {
            $this->googleSheetsService->archiveTicket(self::SPREADSHEET_ID, $id);
            $this->addFlash('success', 'Ticket archivé avec succès !');

            // Invalider le cache
            $this->cache->delete('tickets_list');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'archivage du ticket : ' . $e->getMessage());
        }

        return $this->redirectToRoute('list_tickets');
    }

    /**
     * Récupère un devis par son ID.
     * 
     * @param int $id
     * @return Devis|null
     */
    private function getDevisById(int $id): ?Devis
    {
        $range = 'Sheet2!A' . $id . ':I' . $id;
        $devisData = $this->googleSheetsService->readSheet(self::SPREADSHEET_ID, $range);

        if (empty($devisData)) {
            return null; // Aucun devis trouvé
        }

        // Créer une instance de devis et remplir les données
        $devis = new Devis();
        $devis->setClient($devisData[0][0]);

        // Convertir la date en DateTime
        $dateString = $devisData[0][1];
        $date = \DateTime::createFromFormat('m/d/Y', $dateString); // Changez le format selon ce que vous attendez
        if ($date === false) {
            // Gérer l'erreur de conversion si nécessaire
            throw new \RuntimeException('Date invalide: ' . $dateString);
        }
        $devis->setDateJour($date); // $date est un objet DateTime qui implémente DateTimeInterface

        $devis->setDescription($devisData[0][2]);
        $devis->setQuantite($devisData[0][3]);
        $devis->setUnite($devisData[0][4]);
        $devis->setPrixUnitHT($devisData[0][5]);
        $devis->setTotalHT($devisData[0][6]);
        $devis->setTva($devisData[0][7]);
        $devis->setTTC($devisData[0][8]);

        return $devis;
    }

    /**
     * Convertit un objet Devis en tableau.
     * 
     * @param Devis $devis
     * @return array
     */
    private function devisToArray(Devis $devis): array
    {
        return [
            'client' => $devis->getClient(),
            'date_jour' => $devis->getDateJour()->format('d/m/Y'),
            'description' => $devis->getDescription(),
            'quantite' => $devis->getQuantite(),
            'unite' => $devis->getUnite(),
            'prixUnitHT' => $devis->getPrixUnitHT(),
            'totalHT' => $devis->getTotalHT(),
            'TVA' => $devis->getTva(),
            'TTC' => $devis->getTTC()
        ];
    }
}
