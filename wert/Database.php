<?php
/**
 * Database.php - schmale Huelle um PDO (select, insert, execute ...)
 *
 * Bis 4.6 stand die Klasse in db.php. Unveraendert hierher verschoben, damit
 * die Tests (tests/GeschaeftskernTestCase.php) dieselbe Klasse mit einer
 * SQLite-Datenbank benutzen koennen - ohne die MySQL-Verbindung und die
 * Sitzung, die db.php beim Einbinden aufbaut. db.php bindet sie ein und legt
 * $db wie bisher an.
 */


// ============================================================================
// DATABASE KLASSE - WICHTIG!
// ============================================================================

class Database {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function select($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database select error: " . $e->getMessage());
            return [];
        }
    }
    
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database selectOne error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * SCHREIBENDE METHODEN WERFEN WEITER (seit 4.3.18).
     *
     * Bis 4.3.17 fingen insert() und execute() die PDOException hier ab,
     * schrieben ins PHP-Fehlerlog und lieferten false. Beim Aufrufer kam nie
     * eine Ausnahme an - und weil kein einziger der damals 70 execute()- und
     * 11 insert()-Aufrufe den Rueckgabewert prueft, blieb jeder
     * fehlgeschlagene Schreibvorgang stumm. Die Seiten meldeten Erfolg.
     *
     * Aufgefallen am 06.09.2026: auf Instanzen, deren Tabelle versicherungen
     * die 2026 hinzugekommenen Spalten nicht hatte, lehnte MySQL das UPDATE
     * ab; "Versicherung aktualisiert." erschien trotzdem in Gruen.
     *
     * 51 der 70 execute()-Aufrufe und alle 11 insert()-Aufrufe stehen bereits
     * in einem try-Block. Deren catch-Zweige waren nie tot gedacht, sondern
     * nur tot: sie setzen Fehlermeldungen, machen Transaktionen rueckgaengig
     * (delete.php) und raeumen hochgeladene Dateien wieder weg
     * (manage_documents.php). Das Weiterwerfen macht sie wirksam.
     *
     * Das error_log bleibt: es nennt die SQL-Ursache, die der Aufrufer dem
     * Nutzer nicht zeigen soll.
     *
     * select() und selectOne() sind bewusst NICHT umgestellt. Ein
     * fehlgeschlagenes select() sieht zwar aus wie ein leeres Inventar, hat
     * aber 202 Aufrufstellen - eigene Entscheidung, eigenes Release.
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database insert error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function update($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /** Siehe den Hinweis ueber insert(): wirft seit 4.3.18 weiter. */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database execute error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollBack();
    }
}
