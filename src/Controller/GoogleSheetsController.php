<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\TicketType;
use App\Service\GoogleSheetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleSheetsController extends AbstractController
{
    private GoogleSheetsService $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    #[Route('/tickets', name: 'list_tickets')]
    public function listTickets(): Response
    {
        $spreadsheetId = '1YK_RWejtfBeROGt2-KEmM0W_SRnP2O8LtQmr3pZqzSM';
        $range = 'Sheet1!A2:N'; // Plage à lire, ajustez selon les besoins

        // Utilisation de la méthode readSheet pour obtenir les données
        $tickets = $this->googleSheetsService->readSheet($spreadsheetId, $range);

        return $this->render('ticket/list.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/tickets/archive', name: 'list_archive')]
    public function listArchive(): Response
    {
        $spreadsheetId = '1YK_RWejtfBeROGt2-KEmM0W_SRnP2O8LtQmr3pZqzSM';
        $range = 'Archive!A2:N'; // Plage à lire, ajustez selon les besoins

        // Utilisation de la méthode readSheet pour obtenir les données
        $tickets = $this->googleSheetsService->readSheet($spreadsheetId, $range);

        return $this->render('ticket/archive.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/tickets/new', name: 'new_tickets')]
    public function new(Request $request): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Ajouter le ticket à Google Sheets
            $this->googleSheetsService->addTicket('1YK_RWejtfBeROGt2-KEmM0W_SRnP2O8LtQmr3pZqzSM', [
                'statut' => $ticket->getStatut(),
                'CGV_DECH' => $ticket->getCGVDECH(),
                'client' => $ticket->getClient(),
                'date_jour' => $ticket->getDateJour(),
                'TECH' => $ticket->getTECH(),
                'numero_client' => $ticket->getNumeroClient(),
                'details' => $ticket->getDetails(),
                'materiel' => $ticket->getMateriel(),
                'prestations' => $ticket->getPrestations(),
                'accepte' => $ticket->getAccepte(),
                'resultat' => $ticket->getResultat(),
                'tarif' => $ticket->getTarif(),
                'prevenu' => $ticket->getPrevenu()
            ], 'Sheet1!A1:A');

            $this->addFlash('success', 'Ticket ajouté avec succès !');
            return $this->redirectToRoute('list_tickets');
        }

        return $this->render('ticket/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete-ticket/{id}', name: 'delete_tickets')]
    public function deleteTicketAction(int $id): Response
    {
        $spreadsheetId = '1YK_RWejtfBeROGt2-KEmM0W_SRnP2O8LtQmr3pZqzSM';

        $rowIndex = $id;

        // Utiliser le service injecté pour supprimer le ticket
        $this->googleSheetsService->deleteTicket($spreadsheetId, $rowIndex);

        // Rediriger ou retourner une réponse
        return $this->redirectToRoute('list_tickets');
    }

    #[Route('/edit-ticket/{id}', name: 'edit_ticket')]
    public function editTicketAction(Request $request, int $id): Response
    {
        $spreadsheetId = '1YK_RWejtfBeROGt2-KEmM0W_SRnP2O8LtQmr3pZqzSM';

        // Lire les données existantes du ticket
        $range = 'Sheet1!A' . $id . ':M' . $id;
        $ticketData = $this->googleSheetsService->readSheet($spreadsheetId, $range)[0];

        // Créer une instance de Ticket et remplir les données
        $ticket = new Ticket();
        $ticket->setStatut($ticketData[0]);
        $ticket->setCGVDECH($ticketData[1]);
        $ticket->setClient($ticketData[2]);
        $ticket->setDateJour(new \DateTime($ticketData[3]));
        $ticket->setTECH($ticketData[4]);
        $ticket->setNumeroClient($ticketData[5]);
        $ticket->setDetails($ticketData[6]);
        $ticket->setMateriel($ticketData[7]);
        $ticket->setPrestations($ticketData[8]);
        $ticket->setAccepte($ticketData[9]);
        $ticket->setResultat($ticketData[10]);
        $ticket->setTarif($ticketData[11]);
        $ticket->setPrevenu($ticketData[12]);

        // Créer un formulaire pour le ticket
        $form = $this->createForm(TicketType::class, $ticket);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour le ticket dans Google Sheets
            $this->googleSheetsService->updateTicket($spreadsheetId, $id, $form->getData());

            return $this->redirectToRoute('list_tickets');
        }

        return $this->render('ticket/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route("/archive-ticket/{id}", name: "archive_ticket")]
    public function archiveTicket(int $id): Response
    {
        $spreadsheetId = '1YK_RWejtfBeROGt2-KEmM0W_SRnP2O8LtQmr3pZqzSM';

        try {
            // Appeler le service pour archiver le ticket
            $this->googleSheetsService->archiveTicket($spreadsheetId, $id); // Utilisez $rowIndex ici

            // Ajouter un message flash pour indiquer le succès
            $this->addFlash('success', 'Ticket archivé avec succès !');
        } catch (\Exception $e) {
            // Ajouter un message flash pour indiquer une erreur
            $this->addFlash('error', 'Erreur lors de l\'archivage du ticket : ' . $e->getMessage());
        }

        // Rediriger vers la liste des tickets ou une autre page
        return $this->redirectToRoute('list_tickets'); // Remplacez par la route appropriée
    }
}
