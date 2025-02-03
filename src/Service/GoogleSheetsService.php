<?php

namespace App\Service;

use Google\Client;
use Google\Service\Sheets;
use App\Entity\Ticket;

class GoogleSheetsService
{
    private $client;
    private $service;

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
     * Lecture d'une feuille de calcul Google Sheets
     *
     * @param string $spreadsheetId Identifiant de la feuille de calcul
     * @param string $range Plage de cellules à lire
     *
     * @return array Tableau de valeurs lues
     */
    public function readSheet($spreadsheetId, $range)
    {
        try {
            // Utiliser le service Sheets pour obtenir les valeurs
            $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);

            // Vérifier si des valeurs ont été retournées
            if ($response->getValues()) {
                return $response->getValues();
            } else {
                error_log('Aucune valeur trouvée dans la plage spécifiée.');
                return []; // Retourner un tableau vide si aucune valeur n'est trouvée
            }
        } catch (\Exception $e) {
            // Gérer l'erreur (journaliser, lancer une exception, etc.)
            error_log('Erreur lors de la lecture de la feuille : ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de la lecture de la feuille', 0, $e);
        }
    }

    /**
     * Ajouter un ticket à la feuille de calcul Google Sheets
     *
     * @param string $spreadsheetId Identifiant de la feuille de calcul
     * @param array $ticket Données du ticket à ajouter
     */
    public function addTicket($spreadsheetId, array $ticket, $range)
    {
        try {
            $values = [
                [
                    $ticket['statut'],
                    $ticket['CGV_DECH'],
                    $ticket['client'],
                    $ticket['date_jour']->format('d/m/Y'),
                    $ticket['TECH'],
                    $ticket['numero_client'],
                    $ticket['details'],
                    $ticket['materiel'],
                    $ticket['prestations'],
                    $ticket['accepte'],
                    $ticket['resultat'],
                    $ticket['tarif'],
                    $ticket['prevenu']
                ],
            ];
            $body = new \Google\Service\Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW'];

            // Utiliser append pour ajouter à la première ligne vide
            $this->service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'ajout du ticket : ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de l\'ajout du ticket', 0, $e);
        }
    }

    /**
     * Supprimer un ticket de la feuille de calcul Google Sheets
     *
     * @param string $spreadsheetId Identifiant de la feuille de calcul
     * @param int $rowIndex Index de la ligne à supprimer (1-indexé)
     */
    public function deleteTicket($spreadsheetId, $rowIndex)
    {
        try {
            // Créer une requête pour supprimer la ligne
            $request = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => [
                    [
                        'deleteDimension' => [
                            'range' => [
                                'sheetId' => 0, // ID de la feuille, 0 pour la première feuille
                                'dimension' => 'ROWS',
                                'startIndex' => $rowIndex - 1,
                                'endIndex' => $rowIndex
                            ]
                        ]
                    ]
                ]
            ]);

            // Exécuter la requête
            $this->service->spreadsheets->batchUpdate($spreadsheetId, $request);
        } catch (\Exception $e) {
            error_log('Erreur lors de la suppression du ticket : ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de la suppression du ticket', 0, $e);
        }
    }

    /**
     * Mettre à jour un ticket dans la feuille de calcul Google Sheets
     *
     * @param string $spreadsheetId Identifiant de la feuille de calcul
     * @param int $rowIndex Index de la ligne à mettre à jour (1-indexé)
     * @param Ticket $ticket Objet Ticket à mettre à jour
     */
    public function updateTicket($spreadsheetId, $rowIndex, Ticket $ticket)
    {
        try {
            // Définir la plage à mettre à jour
            $range = 'Sheet1!A' . $rowIndex . ':M' . $rowIndex; // Ajustez la plage selon vos colonnes

            $values = [
                [
                    $ticket->getStatut(),
                    $ticket->getCGVDECH(),
                    $ticket->getClient(),
                    $ticket->getDateJour()->format('d-m-Y'),
                    $ticket->getTECH(),
                    $ticket->getNumeroClient(),
                    $ticket->getDetails(),
                    $ticket->getMateriel(),
                    $ticket->getPrestations(),
                    $ticket->getAccepte(),
                    $ticket->getResultat(),
                    $ticket->getTarif(),
                    $ticket->getPrevenu()
                ],
            ];

            $body = new \Google\Service\Sheets\ValueRange(['values' => $values]);
            $params = ['valueInputOption' => 'RAW'];

            // Utiliser update pour mettre à jour la ligne spécifiée
            $this->service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
        } catch (\Exception $e) {
            error_log('Erreur lors de la mise à jour du ticket : ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de la mise à jour du ticket', 0, $e);
        }
    }

    /**
     * Archiver un ticket en le copiant dans une autre feuille et en le supprimant de la feuille d'origine
     *
     * @param string $spreadsheetId Identifiant de la feuille de calcul
     * @param int $rowIndex Index de la ligne à archiver (1-indexé)
     */
    public function archiveTicket($spreadsheetId, $rowIndex)
    {
        try {
            // Lire le ticket à partir de la feuille d'origine
            $ticketValues = $this->readSheet($spreadsheetId, 'Sheet1!A' . $rowIndex . ':M' . $rowIndex);
            $range = 'Archive!A1'; // Plage de départ pour l'ajout

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
            $this->deleteTicket($spreadsheetId, $rowIndex);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'archivage du ticket : ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de l\'archivage du ticket', 0, $e);
        }
    }
}
