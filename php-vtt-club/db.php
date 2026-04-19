<?php
/**
 * =====================================================
 * Connexion à la base de données MySQL
 * =====================================================
 * Utilisation de PDO pour une connexion sécurisée
 */

require_once __DIR__ . '/config.php';

/**
 * Obtenir une connexion PDO à la base de données
 * @return PDO Instance de connexion PDO
 */
function getDB() {
    static $pdo = null;
    
    // Si la connexion existe déjà, on la réutilise (singleton)
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        // Construction du DSN (Data Source Name)
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // Options PDO recommandées
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Exceptions en cas d'erreur
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Retourne des tableaux associatifs
            PDO::ATTR_EMULATE_PREPARES   => false,                   // Requêtes préparées natives
        ];
        
        // Création de la connexion
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // En mode debug, afficher l'erreur complète
        if (DEBUG_MODE) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        } else {
            die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
        }
    }
}

/**
 * Exécuter une requête SELECT et retourner tous les résultats
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return array Tableau des résultats
 */
function dbFetchAll($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Exécuter une requête SELECT et retourner un seul résultat
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return array|false Un résultat ou false
 */
function dbFetchOne($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Exécuter une requête INSERT, UPDATE ou DELETE
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return int Nombre de lignes affectées
 */
function dbExecute($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Obtenir le dernier ID inséré
 * @return string Dernier ID
 */
function dbLastInsertId() {
    return getDB()->lastInsertId();
}
