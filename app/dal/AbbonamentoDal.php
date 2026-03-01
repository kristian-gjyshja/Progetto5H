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

        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM servizi");
            $colonne = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (PDOException $e) {
            $this->colonnaFrequenzaServizio = null;
            return null;
        }

        foreach (['frequenza_rinnovo', 'frequenza'] as $colonna) {
            if (in_array($colonna, $colonne, true)) {
                $this->colonnaFrequenzaServizio = $colonna;
                return $colonna;
            }
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
        foreach ($parametri as $nome => $valore) {
            $tipo = is_int($valore) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($nome, $valore, $tipo);
        }
        $stmt->execute();

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
        AND LOWER(TRIM(CASE WHEN s.$colonnaFrequenza IS NULL THEN '' ELSE s.$colonnaFrequenza END)) = 'annuale'
        ORDER BY a.data_fine ASC, a.id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUtente(int $utenteId)
    {
        $selectFrequenza = $this->hasColonnaAbbonamenti('frequenza')
            ? "CASE WHEN a.frequenza IS NULL OR TRIM(a.frequenza) = '' THEN 'mensile' ELSE LOWER(TRIM(a.frequenza)) END AS frequenza"
            : "'mensile' AS frequenza";

        $sql = "SELECT a.id, a.attivo, a.data_fine,
        CASE WHEN s.nome IS NULL OR TRIM(s.nome) = '' THEN 'Servizio non disponibile' ELSE s.nome END AS servizio,
        CASE WHEN s.costo IS NULL THEN 0 ELSE s.costo END AS costo,
        CASE WHEN s.categoria IS NULL OR TRIM(s.categoria) = '' THEN 'Senza categoria' ELSE s.categoria END AS categoria,
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

        $selectFrequenza = $this->hasColonnaAbbonamenti('frequenza')
            ? "CASE WHEN a.frequenza IS NULL OR TRIM(a.frequenza) = '' THEN 'mensile' ELSE LOWER(TRIM(a.frequenza)) END AS frequenza"
            : "'mensile' AS frequenza";

        $sql = "SELECT a.id, a.attivo, a.data_fine,
        CASE WHEN s.nome IS NULL OR TRIM(s.nome) = '' THEN 'Servizio non disponibile' ELSE s.nome END AS servizio,
        CASE WHEN s.costo IS NULL THEN 0 ELSE s.costo END AS costo,
        CASE WHEN s.categoria IS NULL OR TRIM(s.categoria) = '' THEN 'Senza categoria' ELSE s.categoria END AS categoria,
        $selectFrequenza
        FROM abbonamenti a
        LEFT JOIN servizi s ON a.servizio_id = s.id
        WHERE a.utente_id = :utente_id
        AND a.archiviato = 0
        AND a.attivo = 1
        AND a.data_fine BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND LOWER(TRIM(CASE WHEN s.$colonnaFrequenza IS NULL THEN '' ELSE s.$colonnaFrequenza END)) = 'annuale'
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
        AND LOWER(TRIM(CASE WHEN s.$colonnaFrequenza IS NULL THEN '' ELSE s.$colonnaFrequenza END)) = 'mensile'");
        return $stmt->fetch(PDO::FETCH_ASSOC)['totale'] ?? 0;
    }

    public function spesaAnnuale()
    {
        $colonnaFrequenza = $this->getColonnaFrequenzaServizio();
        if ($colonnaFrequenza === null) {
            $stmt = $this->pdo->query("SELECT SUM(s.costo) * 12 AS totale FROM abbonamenti a JOIN servizi s ON a.servizio_id = s.id WHERE a.attivo = 1 AND a.archiviato = 0");
            return $stmt->fetch(PDO::FETCH_ASSOC)['totale'] ?? 0;
        }

        $stmt = $this->pdo->query("SELECT SUM(
            CASE
                WHEN LOWER(TRIM(CASE WHEN s.$colonnaFrequenza IS NULL THEN '' ELSE s.$colonnaFrequenza END)) = 'mensile' THEN s.costo * 12
                WHEN LOWER(TRIM(CASE WHEN s.$colonnaFrequenza IS NULL THEN '' ELSE s.$colonnaFrequenza END)) = 'annuale' THEN s.costo
                ELSE 0
            END
        ) AS totale
        FROM abbonamenti a
        JOIN servizi s ON a.servizio_id = s.id
        WHERE a.attivo = 1
        AND a.archiviato = 0");
        return $stmt->fetch(PDO::FETCH_ASSOC)['totale'] ?? 0;
    }

    public function spesaTotaleRicorrente()
    {
        $stmt = $this->pdo->query("SELECT SUM(s.costo) AS totale FROM abbonamenti a JOIN servizi s ON a.servizio_id = s.id WHERE a.attivo = 1 AND a.archiviato = 0");
        return $stmt->fetch(PDO::FETCH_ASSOC)['totale'] ?? 0;
    }

    public function spesaRicorrentePerCategoria()
    {
        $stmt = $this->pdo->query("SELECT
            CASE
                WHEN s.categoria IS NULL OR TRIM(s.categoria) = '' THEN 'Senza categoria'
                ELSE s.categoria
            END AS categoria,
            CASE
                WHEN SUM(s.costo) IS NULL THEN 0
                ELSE SUM(s.costo)
            END AS totale
            FROM abbonamenti a
            JOIN servizi s ON a.servizio_id = s.id
            WHERE a.attivo = 1
              AND a.archiviato = 0
            GROUP BY
            CASE
                WHEN s.categoria IS NULL OR TRIM(s.categoria) = '' THEN 'Senza categoria'
                ELSE s.categoria
            END
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

    public function Mettiinpausa(int $id)
    {
        return $this->disattiva($id);
    }

    public function ScadenzaProxMese()
    {
        $stmt = $this->pdo->query("SELECT s.nome, s.categoria, a.data_fine, s.costo
        FROM abbonamenti a
        JOIN servizi s ON a.servizio_id = s.id
        WHERE a.data_fine BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
        AND a.attivo = 1
        AND a.archiviato = 0;");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
}
