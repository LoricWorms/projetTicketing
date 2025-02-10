<?php

namespace App\Service;

use Google\Client;
use Google\Service\Sheets;
use App\Entity\Ticket;

/**
 * Class GoogleSheetsService
 * 
 * Ce service gère les interactions avec Google Sheets pour les opérations liées aux tickets,
 * y compris la lecture, l'ajout, la mise à jour, la suppression et l'archivage des tickets.
 */
class GoogleSheetsService
{
    private Client $client;
    private Sheets $service;

    private const SHEET_NAME = 'Sheet1';
    private const ARCHIVE_SHEET_NAME = 'Archive';

    /**
     * GoogleSheetsService constructeur.
     * 
     * Initialise le client Google et le service Sheets.
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('projetTicketing');
        $this->client->setAuthConfig(__DIR__ . "/../../config/projetticketing.json");
        $this->client->setScopes(Sheets::SPREADSHEETS);
        $this->client->setAccessType('offline');

        // Initialiser le service Sheets
        $this->service = new Sheets($this->client);
    }

    /**
     * Gère les erreurs en les enregistrant et en lançant une exception.
     * 
     * @param \Exception $e L'exception à gérer.
     * @param string $context Le contexte de l'erreur.
     * @throws \RuntimeException
     */
    private function handleError(\Exception $e, string $context): void
    {
        error_log("Erreur lors de {$context} : " . $e->getMessage());
        throw new \RuntimeException("Erreur lors de {$context}", 0, $e);
    }

    /**
     * Convertit un objet Ticket en tableau.
     * 
     * @param Ticket $ticket L'objet Ticket à convertir.
     * @return array Le tableau représentant le ticket.
     */
    private function ticketToArray(Ticket $ticket): array
    {
        return [
            $ticket->getStatut(),
            $ticket->getCGVDECH(),
            $ticket->getClient(),
            $ticket->getDateJour()->format('d/m/Y'),
            $ticket->getTECH(),
            $ticket->getNumeroClient(),
            $ticket->getDetails(),
            $ticket->getMateriel(),
            $ticket->getPrestations(),
            $ticket->getAccepte(),
            $ticket->getResultat(),
            $ticket->getTarif(),
            $ticket->getPrevenu()
        ];
    }

    /**
     * Lit les valeurs d'une feuille Google Sheets.
     * 
     * @param string $spreadsheetId L'ID de la feuille de calcul.
     * @param string $range La plage de cellules à lire.
     * @return array Les valeurs lues de la feuille.
     * @throws \RuntimeException
     */
    public function readSheet(string $spreadsheetId, string $range): array
    {
        try {
            $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
            return $response->getValues() ?: [];
        } catch (\Exception $e) {
            $this->handleError($e, 'la lecture de la feuille');
        }
    }

    /**
     * Ajoute un ticket à la feuille Google Sheets.
     * 
     * @param string $spreadsheetId L'ID de la feuille de calcul.
     * @param array $ticketData Les données du ticket à ajouter.
     * @param string $range La plage où ajouter le ticket.
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function addTicket(string $spreadsheetId, array $ticketData, string $range): void
    {
        try {
            // Vérifiez que toutes les données nécessaires sont présentes
            if (count($ticketData) < 13) {
                throw new \InvalidArgumentException('Les données du ticket sont incomplètes.');
            }

            // Formater les valeurs pour l'API
            $values = [
                [
                    $ticketData['statut'],
                    $ticketData['CGV_DECH'],
                    $ticketData['client'],
                    $ticketData['date_jour']->format('d/m/Y'),
                    $ticketData['TECH'],
                    $ticketData['numero_client'],
                    $ticketData['details'],
                    $ticketData['materiel'],
                    $ticketData['prestations'],
                    $ticketData['accepte'],
                    $ticketData['resultat'],
                    $ticketData['tarif'],
                    $ticketData['prevenu']
                ],
            ];

            $body = new \Google\Service\Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW'];

            // Utiliser append pour ajouter à la première ligne vide
            $this->service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
        } catch (\Exception $e) {
            $this->handleError($e, 'l\'ajout du ticket');
        }
    }

    /**
     * Supprime un ticket de la feuille Google Sheets.
     * 
     * @param string $spreadsheetId L'ID de la feuille de calcul.
     * @param int $rowIndex L'index de la ligne à supprimer.
     * @param int $sheetId L'ID de la feuille.
     * @throws \RuntimeException
     */
    public function delete(string $spreadsheetId, int $rowIndex, int $sheetId): void
    {
        try {
            $request = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => [
                    [
                        'deleteDimension' => [
                            'range' => [
                                'sheetId' => $sheetId,
                                'dimension' => 'ROWS',
                                'startIndex' => $rowIndex - 1,
                                'endIndex' => $rowIndex
                            ]
                        ]
                    ]
                ]
            ]);
            $this->service->spreadsheets->batchUpdate($spreadsheetId, $request);
        } catch (\Exception $e) {
            $this->handleError($e, 'la suppression du ticket');
        }
    }

    /**
     * Met à jour un ticket dans la feuille Google Sheets.
     * 
     * @param string $spreadsheetId L'ID de la feuille de calcul.
     * @param int $rowIndex L'index de la ligne à mettre à jour.
     * @param Ticket $ticket L'objet Ticket contenant les nouvelles données.
     * @throws \RuntimeException
     */
    public function updateTicket(string $spreadsheetId, int $rowIndex, Ticket $ticket): void
    {
        try {
            $range = self::SHEET_NAME . '!A' . $rowIndex . ':M' . $rowIndex;

            $values = [$this->ticketToArray($ticket)];
            $body = new \Google\Service\Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW'];
            $this->service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
        } catch (\Exception $e) {
            $this->handleError($e, 'la mise à jour du ticket');
        }
    }

    /**
     * Archive un ticket en le déplaçant vers la feuille d'archive.
     * 
     * @param string $spreadsheetId L'ID de la feuille de calcul.
     * @param int $rowIndex L'index de la ligne à archiver.
     * @throws \RuntimeException
     */
    public function archiveTicket(string $spreadsheetId, int $rowIndex): void
    {
        try {
            // Lire le ticket à partir de la feuille d'origine
            $ticketValues = $this->readSheet($spreadsheetId, self::SHEET_NAME . '!A' . $rowIndex . ':M' . $rowIndex);
            $range = self::ARCHIVE_SHEET_NAME . '!A1'; // Plage de départ pour l'ajout

            if (empty($ticketValues)) {
                throw new \RuntimeException('Aucun ticket trouvé à archiver.');
            }

            // Vérifiez que les valeurs du ticket sont bien formatées
            if (count($ticketValues[0]) < 13) {
                throw new \RuntimeException('Les données du ticket sont incomplètes.');
            }

            // Formater les valeurs du ticket en tableau associatif
            $ticketData = [
                'statut' => $ticketValues[0][0],
                'CGV_DECH' => $ticketValues[0][1],
                'client' => $ticketValues[0][2],
                'date_jour' => new \DateTime($ticketValues[0][3]),
                'TECH' => $ticketValues[0][4],
                'numero_client' => $ticketValues[0][5],
                'details' => $ticketValues[0][6],
                'materiel' => $ticketValues[0][7],
                'prestations' => $ticketValues[0][8],
                'accepte' => $ticketValues[0][9],
                'resultat' => $ticketValues[0][10],
                'tarif' => $ticketValues[0][11],
                'prevenu' => $ticketValues[0][12],
            ];

            // Ajouter le ticket à la feuille d'archive
            $this->addTicket($spreadsheetId, $ticketData, $range);

            // Supprimer le ticket de la feuille d'origine
            $this->delete($spreadsheetId, $rowIndex, 0);
        } catch (\Exception $e) {
            $this->handleError($e, 'l\'archivage du ticket');
        }
    }
}
