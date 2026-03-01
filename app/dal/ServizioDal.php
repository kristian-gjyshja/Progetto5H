<?php

class ServizioDAL {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM servizi");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function aggiungi(string $nome, string $categoria, float $costo): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO servizi (nome, categoria, costo)
            VALUES (:nome, :categoria, :costo)");
        $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindValue(':categoria', $categoria, PDO::PARAM_STR);
        $stmt->bindValue(':costo', $costo);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function SpesaPercategoriaIntrattenimento(){
        $stmt = $this->pdo->query("SELECT categoria, SUM(s.costo * 12) AS totale
        FROM servizi s JOIN abbonamenti a ON s.id = a.servizio_id
        WHERE s.categoria = 'intrattenimento' AND a.attivo = 1 AND a.archiaviato = 0
        GROUP BY categoria;");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNonRinnovatiUltimoAnno()
    {
        $stmt = $this->pdo->query("SELECT s.id, s.nome, s.categoria, s.costo, MAX(a.data_fine) AS ultimo_rinnovo
        FROM servizi s
        LEFT JOIN abbonamenti a ON a.servizio_id = s.id
        GROUP BY s.id, s.nome, s.categoria, s.costo
        HAVING ultimo_rinnovo IS NULL OR ultimo_rinnovo < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
        ORDER BY ultimo_rinnovo IS NULL DESC, ultimo_rinnovo ASC, s.nome ASC");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
