<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\TicketType;
use App\Service\GoogleSheetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GoogleSheetsController extends AbstractController
{
    private GoogleSheetsService $googleSheetsService;
    private CacheInterface $cache;
    private const SPREADSHEET_ID = '1YK_RWejtfBeROGt2-KEmM0W_SRnP2O8LtQmr3pZqzSM';

    public function __construct(GoogleSheetsService $googleSheetsService, CacheInterface $cache)
    {
        $this->googleSheetsService = $googleSheetsService;
        $this->cache = $cache;
    }

    #[Route('/tickets', name: 'list_tickets')]
    public function listTickets(): Response
    {
        $range = 'Sheet1!A2:N'; // Plage à lire, ajustez selon les besoins

        // Utilisation du cache pour éviter des appels répétés
        $tickets = $this->cache->get('tickets_list', function (ItemInterface $item) use ($range) {
            $item->expiresAfter(3600); // Le cache expire après 1 heure
            return $this->googleSheetsService->readSheet(self::SPREADSHEET_ID, $range);
        });

        return $this->render('ticket/list.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/tickets/archive', name: 'list_archive')]
    public function listArchive(): Response
    {
        $range = 'Archive!A2:N'; // Plage à lire, ajustez selon les besoins

        // Utilisation du cache pour éviter des appels répétés
        $tickets = $this->cache->get('archive_tickets_list', function (ItemInterface $item) use ($range) {
            $item->expiresAfter(3600); // Le cache expire après 1 heure
            return $this->googleSheetsService->readSheet(self::SPREADSHEET_ID, $range);
        });

        return $this->render('ticket/archive.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/tickets/new', name: 'new_ticket')]
    public function new(Request $request): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->googleSheetsService->addTicket(self::SPREADSHEET_ID, $this->ticketToArray($ticket), 'Sheet1!A1:A');
            $this->addFlash('success', 'Ticket ajouté avec succès !');

            // Invalider le cache
            $this->cache->delete('tickets_list');

            return $this->redirectToRoute('list_tickets');
        }

        return $this->render('ticket/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete-ticket/{id}', name: 'delete_ticket')]
    public function deleteTicketAction(int $id): Response
    {
        try {
            $this->googleSheetsService->deleteTicket(self::SPREADSHEET_ID, $id);
            $this->addFlash('success', 'Ticket supprimé avec succès !');

            // Invalider le cache
            $this->cache->delete('tickets_list');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du ticket : ' . $e->getMessage());
        }

        return $this->redirectToRoute('list_tickets');
    }

    #[Route('/edit-ticket/{id}', name: 'edit_ticket')]
    public function editTicketAction(Request $request, int $id): Response
    {
        $ticket = $this->getTicketById($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket non trouvé.');
        }

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->googleSheetsService->updateTicket(self::SPREADSHEET_ID, $id, $form->getData());
            $this->addFlash('success', 'Ticket mis à jour avec succès !');

            // Invalider le cache
            $this->cache->delete('tickets_list');

            return $this->redirectToRoute('list_tickets');
        }

        return $this->render('ticket/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route("/archive-ticket/{id}", name: "archive_ticket")]
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

    private function getTicketById(int $id): ?Ticket
    {
        $range = 'Sheet1!A' . $id . ':M' . $id;
        $ticketData = $this->googleSheetsService->readSheet(self::SPREADSHEET_ID, $range);

        if (empty($ticketData)) {
            return null; // Aucun ticket trouvé
        }

        // Créer une instance de Ticket et remplir les données
        $ticket = new Ticket();
        $ticket->setStatut($ticketData[0][0]);
        $ticket->setCGVDECH($ticketData[0][1]);
        $ticket->setClient($ticketData[0][2]);

        // Convertir la date en DateTime
        $dateString = $ticketData[0][3];
        $date = \DateTime::createFromFormat('m/d/Y', $dateString); // Changez le format selon ce que vous attendez
        if ($date === false) {
            // Gérer l'erreur de conversion si nécessaire
            throw new \RuntimeException('Date invalide: ' . $dateString);
        }
        $ticket->setDateJour($date); // $date est un objet DateTime qui implémente DateTimeInterface

        $ticket->setTECH($ticketData[0][4]);
        $ticket->setNumeroClient($ticketData[0][5]);
        $ticket->setDetails($ticketData[0][6]);
        $ticket->setMateriel($ticketData[0][7]);
        $ticket->setPrestations($ticketData[0][8]);
        $ticket->setAccepte($ticketData[0][9]);
        $ticket->setResultat($ticketData[0][10]);
        $ticket->setTarif($ticketData[0][11]);
        $ticket->setPrevenu($ticketData[0][12]);

        return $ticket;
    }

    private function ticketToArray(Ticket $ticket): array
    {
        return [
            'statut' => $ticket->getStatut(),
            'CGV_DECH' => $ticket->getCGVDECH(),
            'client' => $ticket->getClient(),
            'date_jour' => $ticket->getDateJour()->format('d/m/Y'),
            'TECH' => $ticket->getTECH(),
            'numero_client' => $ticket->getNumeroClient(),
            'details' => $ticket->getDetails(),
            'materiel' => $ticket->getMateriel(),
            'prestations' => $ticket->getPrestations(),
            'accepte' => $ticket->getAccepte(),
            'resultat' => $ticket->getResultat(),
            'tarif' => $ticket->getTarif(),
            'prevenu' => $ticket->getPrevenu()
        ];
    }
}
