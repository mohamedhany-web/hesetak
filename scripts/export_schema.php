<?php

declare(strict_types=1);

$databasePath = realpath(__DIR__ . '/../database/database.sqlite');
$outputPath = realpath(__DIR__ . '/../database') . DIRECTORY_SEPARATOR . 'schema.sql';

if ($databasePath === false || !file_exists($databasePath)) {
    fwrite(STDERR, "Database file not found: database/database.sqlite" . PHP_EOL);
    exit(1);
}

$pdo = new PDO('sqlite:' . $databasePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("
    SELECT type, name, tbl_name, sql
    FROM sqlite_master
    WHERE sql IS NOT NULL
      AND name NOT LIKE 'sqlite_%'
      AND type IN ('table', 'index', 'trigger', 'view')
    ORDER BY
        CASE type
            WHEN 'table' THEN 0
            WHEN 'view' THEN 1
            WHEN 'index' THEN 2
            WHEN 'trigger' THEN 3
            ELSE 4
        END,
        name
");

$definitions = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sql = trim((string) $row['sql']);
    if ($sql === '') {
        continue;
    }

    $definitions[] = $sql . ';';
}

$tablesStmt = $pdo->query("
    SELECT name
    FROM sqlite_master
    WHERE type = 'table'
      AND name NOT LIKE 'sqlite_%'
    ORDER BY name
");

$dataSections = [];

while ($table = $tablesStmt->fetchColumn()) {
    $dataRows = $pdo->query(sprintf('SELECT * FROM "%s"', $table))->fetchAll(PDO::FETCH_ASSOC);
    if (empty($dataRows)) {
        continue;
    }

    $columns = array_keys($dataRows[0]);
    $columnList = '"' . implode('","', $columns) . '"';
    $insertStatements = [];

    foreach ($dataRows as $row) {
        $values = [];
        foreach ($columns as $column) {
            $value = $row[$column];
            if ($value === null) {
                $values[] = 'NULL';
                continue;
            }

            if (is_int($value) || is_float($value)) {
                $values[] = (string) $value;
                continue;
            }

            $values[] = $pdo->quote((string) $value);
        }

        $insertStatements[] = sprintf(
            'INSERT INTO "%s" (%s) VALUES (%s);',
            $table,
            $columnList,
            implode(', ', $values)
        );
    }

    $dataSections[] = sprintf(
        "-- Data for table \"%s\"\n%s",
        $table,
        implode("\n", $insertStatements)
    );
}

$header = <<<SQL
-- ============================================
-- حصتك Platform - Complete Database Schema
-- ============================================
-- Auto-generated on: %s
-- ============================================

SET SQL_MODE = 'ANSI_QUOTES';
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

SQL;

$footer = <<<SQL
COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

SQL;

$body = implode("\n\n", $definitions);

if (!empty($dataSections)) {
    $body .= "\n\n-- ============================================\n-- Data Export\n-- ============================================\n\n" . implode("\n\n", $dataSections);
}

$content = sprintf($header, gmdate('Y-m-d H:i:s')) . $body . "\n\n" . $footer;
$content = preg_replace('/autoincrement/i', 'AUTO_INCREMENT', $content);
$content = preg_replace('/integer\\s+primary\\s+key\\s+AUTO_INCREMENT/i', 'integer NOT NULL AUTO_INCREMENT PRIMARY KEY', $content);
$content = preg_replace('/integer\\s+primary\\s+AUTO_INCREMENT\\s+key/i', 'integer NOT NULL AUTO_INCREMENT PRIMARY KEY', $content);
$content = preg_replace('/AUTO_INCREMENT\\s+PRIMARY\\s+KEY\\s+not\\s+null/i', 'AUTO_INCREMENT PRIMARY KEY', $content);

if (file_put_contents($outputPath, $content) === false) {
    fwrite(STDERR, "Failed to write schema to {$outputPath}" . PHP_EOL);
    exit(1);
}

echo "Schema exported successfully to {$outputPath}" . PHP_EOL;

