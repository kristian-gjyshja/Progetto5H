<?php

class AbbonamentoDal
{
    private  $pdo;
    private $colonnaFrequenzaServizio = null;
    private $colonnaFrequenzaRisolta = false;
    private $colonneAbbonamenti = [];
    private $colonneAbbonamentiRisolte = false;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function getColonnaFrequenzaServizio(): ?string
    {
        if ($this->colonnaFrequenzaRisolta) {
            return $this->colonnaFrequenzaServizio;
        }

        $this->colonnaFrequenzaRisolta = true;

        $colonne = $this->getColonneAbbonamenti();
        if (in_array('frequenza', $colonne, true)) {
            $this->colonnaFrequenzaServizio = 'frequenza';
            return $this->colonnaFrequenzaServizio;
        }

        $this->colonnaFrequenzaServizio = null;
        return null;
    }

    private function getColonneAbbonamenti(): array
    {
        if ($this->colonneAbbonamentiRisolte) {
            return $this->colonneAbbonamenti;
        }

        $this->colonneAbbonamentiRisolte = true;

        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM abbonamenti");
            $this->colonneAbbonamenti = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (PDOException $e) {
            $this->colonneAbbonamenti = [];
        }

        return $this->colonneAbbonamenti;
    }

    private function hasColonnaAbbonamenti(string $colonna): bool
    {
        return in_array($colonna, $this->getColonneAbbonamenti(), true);
    }

    private function getSelectFrequenza(): string
    {
        if ($this->hasColonnaAbbonamenti('frequenza')) {
            return "COALESCE(NULLIF(TRIM(LOWER(a.frequenza)), ''), 'mensile') AS frequenza";
        }

        return "'mensile' AS frequenza";
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT s.nome AS nomeservizio, u.nome AS nomeutente, a.data_fine,a.attivo,a.id FROM abbonamenti a JOIN servizi s ON a.servizio_id = s.id JOIN utenti u ON a.utente_id = u.id WHERE a.attivo = 1 AND a.archiviato = 0");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUtentiPerSelect(): array
    {
        $stmt = $this->pdo->query("SELECT id, nome FROM utenti ORDER BY nome ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServiziPerSelect(): array
    {
        $stmt = $this->pdo->query("SELECT id, nome FROM servizi ORDER BY nome ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function aggiungi(int $utenteId, int $servizioId, string $dataFine, string $frequenza = 'mensile'): bool
    {
        $frequenzaNormalizzata = strtolower(trim($frequenza));
        if (!in_array($frequenzaNormalizzata, ['mensile', 'annuale'], true)) {
            $frequenzaNormalizzata = 'mensile';
        }

        $colonne = ['utente_id', 'servizio_id', 'data_fine', 'attivo', 'archiviato'];
        $valori = [':utente_id', ':servizio_id', ':data_fine', '1', '0'];
        $parametri = [
            ':utente_id' => $utenteId,
            ':servizio_id' => $servizioId,
            ':data_fine' => $dataFine,
        ];

        if ($this->hasColonnaAbbonamenti('data_inizio')) {
            $colonne[] = 'data_inizio';
            $valori[] = ':data_inizio';
            $parametri[':data_inizio'] = date('Y-m-d');
        }

        if ($this->hasColonnaAbbonamenti('frequenza')) {
            $colonne[] = 'frequenza';
            $valori[] = ':frequenza';
            $parametri[':frequenza'] = $frequenzaNormalizzata;
        }

        $sql = sprintf(
            'INSERT INTO abbonamenti (%s) VALUES (%s)',
            implode(', ', $colonne),
            implode(', ', $valori)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametri);

        return $stmt->rowCount() > 0;
    }

    public function getForAdmin(string $filtro = 'attivi'): array
    {
        if ($filtro === 'scadenza_annuale') {
            return $this->getInScadenzaAnnualiForAdmin();
        }

        $sql = "SELECT a.id, a.data_fine, a.attivo, a.archiviato, s.nome AS nomeservizio, u.nome AS nomeutente
        FROM abbonamenti a
        JOIN servizi s ON a.servizio_id = s.id
        JOIN utenti u ON a.utente_id = u.id";

        if ($filtro === 'disattivati') {
            $sql .= " WHERE a.attivo = 0 AND a.archiviato = 0";
        } elseif ($filtro === 'archiviati') {
            $sql .= " WHERE a.archiviato = 1";
        } elseif ($filtro === 'attivi') {
            $sql .= " WHERE a.attivo = 1 AND a.archiviato = 0";
        }

        $sql .= " ORDER BY a.data_fine ASC, a.id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInScadenzaAnnualiForAdmin(): array
    {
        $colonnaFrequenza = $this->getColonnaFrequenzaServizio();
        if ($colonnaFrequenza === null) {
            return [];
        }

        $sql = "SELECT a.id, a.data_fine, a.attivo, a.archiviato, s.nome AS nomeservizio, u.nome AS nomeutente
        FROM abbonamenti a
        JOIN servizi s ON a.servizio_id = s.id
        JOIN utenti u ON a.utente_id = u.id
        WHERE a.attivo = 1
        AND a.archiviato = 0
        AND a.data_fine BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND LOWER(TRIM(COALESCE(a.$colonnaFrequenza, ''))) = 'annuale'
        ORDER BY a.data_fine ASC, a.id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUtente(int $utenteId)
    {
        $selectFrequenza = $this->getSelectFrequenza();

        $sql = "SELECT a.id, a.attivo, a.data_fine,
        s.nome AS servizio,
        s.costo AS costo,
        s.categoria AS categoria,
        $selectFrequenza
        FROM abbonamenti a
        LEFT JOIN servizi s ON a.servizio_id = s.id
        WHERE a.utente_id = :utente_id
        AND a.archiviato = 0
        ORDER BY a.data_fine ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':utente_id', $utenteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInScadenzaAnnualiByUtente(int $utenteId): array
    {
        $colonnaFrequenza = $this->getColonnaFrequenzaServizio();
        if ($colonnaFrequenza === null) {
            return [];
        }

        $selectFrequenza = $this->getSelectFrequenza();

        $sql = "SELECT a.id, a.attivo, a.data_fine,
        s.nome AS servizio,
        s.costo AS costo,
        s.categoria AS categoria,
        $selectFrequenza
        FROM abbonamenti a
        LEFT JOIN servizi s ON a.servizio_id = s.id
        WHERE a.utente_id = :utente_id
        AND a.archiviato = 0
        AND a.attivo = 1
        AND a.data_fine BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND LOWER(TRIM(COALESCE(a.$colonnaFrequenza, ''))) = 'annuale'
        ORDER BY a.data_fine ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':utente_id', $utenteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
   
    public function inScadenza(): array
    {
        $stmt = $this->pdo->query("SELECT
            a.id,
            s.nome AS nome,
            a.data_fine AS data_fine
            FROM abbonamenti a
            JOIN servizi s ON a.servizio_id = s.id
            WHERE a.attivo = 1
              AND a.archiviato = 0
              AND a.data_fine BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY a.data_fine ASC, a.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function spesaMensile()
    {
        $colonnaFrequenza = $this->getColonnaFrequenzaServizio();
        if ($colonnaFrequenza === null) {
            $stmt = $this->pdo->query("SELECT SUM(s.costo) AS totale FROM abbonamenti a JOIN servizi s ON a.servizio_id = s.id WHERE a.attivo = 1 AND a.archiviato = 0");
            return $stmt->fetch(PDO::FETCH_ASSOC)['totale'] ?? 0;
        }

        $stmt = $this->pdo->query("SELECT SUM(s.costo) AS totale
        FROM abbonamenti a
        JOIN servizi s ON a.servizio_id = s.id
        WHERE a.attivo = 1
        AND a.archiviato = 0
        AND LOWER(TRIM(COALESCE(a.$colonnaFrequenza, ''))) = 'mensile'");
        return $stmt->fetch(PDO::FETCH_ASSOC)['totale'] ?? 0;
    }

    public function spesaAnnuale()
    {
        $colonnaFrequenza = $this->getColonnaFrequenzaServizio();
        if ($colonnaFrequenza === null) {
            $stmt = $this->pdo->query("SELECT SUM(s.costo) * 12 AS totale FROM abbonamenti a JOIN servizi s ON a.servizio_id = s.id WHERE a.attivo = 1 AND a.archiviato = 0");
            return $stmt->fetch(PDO::FETCH_ASSOC)['totale'] ?? 0;
        }

        $sqlBase = "FROM abbonamenti a
            JOIN servizi s ON a.servizio_id = s.id
            WHERE a.attivo = 1
            AND a.archiviato = 0";

        $stmt = $this->pdo->query("SELECT SUM(s.costo) AS totale $sqlBase
            AND LOWER(TRIM(COALESCE(a.$colonnaFrequenza, ''))) = 'mensile'");
        $totMensile = (float) ($stmt->fetch(PDO::FETCH_ASSOC)['totale'] ?? 0);

        $stmt = $this->pdo->query("SELECT SUM(s.costo) AS totale $sqlBase
            AND LOWER(TRIM(COALESCE(a.$colonnaFrequenza, ''))) = 'annuale'");
        $totAnnuale = (float) ($stmt->fetch(PDO::FETCH_ASSOC)['totale'] ?? 0);

        return ($totMensile * 12) + $totAnnuale;
    }

    public function spesaTotaleRicorrente()
    {
        $stmt = $this->pdo->query("SELECT SUM(s.costo) AS totale FROM abbonamenti a JOIN servizi s ON a.servizio_id = s.id WHERE a.attivo = 1 AND a.archiviato = 0");
        return $stmt->fetch(PDO::FETCH_ASSOC)['totale'] ?? 0;
    }

    public function spesaRicorrentePerCategoria()
    {
        $stmt = $this->pdo->query("SELECT
            COALESCE(NULLIF(TRIM(s.categoria), ''), 'Senza categoria') AS categoria,
            COALESCE(SUM(COALESCE(s.costo, 0)), 0) AS totale
            FROM abbonamenti a
            JOIN servizi s ON a.servizio_id = s.id
            WHERE a.attivo = 1
              AND a.archiviato = 0
            GROUP BY COALESCE(NULLIF(TRIM(s.categoria), ''), 'Senza categoria')
            ORDER BY totale DESC, categoria ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function archivia(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE abbonamenti SET archiviato = 1, attivo = 0 WHERE id = :id AND archiviato = 0");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function disattiva(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE abbonamenti SET attivo = 0 WHERE id = :id AND archiviato = 0 AND attivo = 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function disattivaPerUtente(int $id, int $utenteId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE abbonamenti
            SET attivo = 0
            WHERE id = :id
              AND utente_id = :utente_id
              AND archiviato = 0
              AND attivo = 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':utente_id', $utenteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function eliminaPerUtente(int $id, int $utenteId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM abbonamenti
            WHERE id = :id
              AND utente_id = :utente_id
              AND archiviato = 0
              AND (
                    attivo = 0
                    OR data_fine < CURDATE()
              )");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':utente_id', $utenteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function riattivaPerUtente(int $id, int $utenteId, string $nuovaDataFine): bool
    {
        $setSql = "attivo = 1, archiviato = 0, data_fine = :data_fine";
        if ($this->hasColonnaAbbonamenti('data_inizio')) {
            $setSql .= ", data_inizio = CURDATE()";
        }

        $stmt = $this->pdo->prepare("UPDATE abbonamenti
            SET $setSql
            WHERE id = :id
              AND utente_id = :utente_id
              AND attivo = 0
              AND archiviato = 0");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':utente_id', $utenteId, PDO::PARAM_INT);
        $stmt->bindValue(':data_fine', $nuovaDataFine, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function riattiva(int $id, string $nuovaDataFine): bool
    {
        $setSql = "attivo = 1, archiviato = 0, data_fine = :data_fine";
        if ($this->hasColonnaAbbonamenti('data_inizio')) {
            $setSql .= ", data_inizio = CURDATE()";
        }

        $stmt = $this->pdo->prepare("UPDATE abbonamenti
            SET $setSql
            WHERE id = :id
              AND attivo = 0
              AND archiviato = 0");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':data_fine', $nuovaDataFine, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function dearchivia(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE abbonamenti SET archiviato = 0, attivo = 0 WHERE id = :id AND archiviato = 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function elimina(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM abbonamenti WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
