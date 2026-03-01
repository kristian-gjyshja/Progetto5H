<?php
require_once 'db.php';

class UtenteDAL {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    


    public function getAll() {
        $stmt = $this->pdo->query("SELECT u.id, u.nome, u.email, u.ruolo,
            SUM(CASE WHEN a.attivo = 1 AND a.archiviato = 0 THEN 1 ELSE 0 END) AS abbonamenti_attivi
            FROM utenti u
            LEFT JOIN abbonamenti a ON a.utente_id = u.id
            GROUP BY u.id, u.nome, u.email, u.ruolo
            ORDER BY u.nome ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function aggiungi(string $nome, string $email, string $password, string $ruolo = 'user'): bool
    {
        $ruoloNorm = strtolower($ruolo) === 'admin' ? 'admin' : 'user';
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("INSERT INTO utenti (nome, email, password, ruolo)
            VALUES (:nome, :email, :password, :ruolo)");
        $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':ruolo', $ruoloNorm, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function elimina(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM utenti
            WHERE id = :id
              AND (ruolo IS NULL OR LOWER(TRIM(ruolo)) <> 'admin')
              AND NOT EXISTS (
                    SELECT 1
                    FROM abbonamenti a
                    WHERE a.utente_id = utenti.id
              )");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
