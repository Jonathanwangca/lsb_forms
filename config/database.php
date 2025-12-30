<?php
/**
 * 数据库配置文件
 * LSB RFQ System
 */

// 加载环境变量
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// 数据库配置
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_DATABASE', $_ENV['DB_DATABASE'] ?? 'aiforms');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

/**
 * 获取PDO数据库连接
 */
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                DB_HOST, DB_PORT, DB_DATABASE, DB_CHARSET
            );

            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

/**
 * 执行查询并返回所有结果
 */
function dbQuery($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * 执行查询并返回单条结果
 */
function dbQueryOne($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * 执行插入并返回插入ID
 */
function dbInsert($table, $data) {
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));

    $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
    $stmt = getDB()->prepare($sql);
    $stmt->execute(array_values($data));

    return getDB()->lastInsertId();
}

/**
 * 执行更新
 */
function dbUpdate($table, $data, $where, $whereParams = []) {
    $sets = [];
    foreach (array_keys($data) as $column) {
        $sets[] = "`$column` = ?";
    }

    $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE $where";
    $stmt = getDB()->prepare($sql);
    $stmt->execute(array_merge(array_values($data), $whereParams));

    return $stmt->rowCount();
}

/**
 * 执行删除
 */
function dbDelete($table, $where, $whereParams = []) {
    $sql = "DELETE FROM `$table` WHERE $where";
    $stmt = getDB()->prepare($sql);
    $stmt->execute($whereParams);

    return $stmt->rowCount();
}

/**
 * 执行SQL语句（INSERT/UPDATE/DELETE等）
 */
function dbExecute($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * 获取最后插入的ID
 */
function dbLastInsertId() {
    return getDB()->lastInsertId();
}
