<?php

require_once __DIR__ . '/../php/shoe_distances.php';

class CountingShoeConnection extends PDO {
    public $queryCount = 0;

    public function query($query, ?int $fetchMode = null, ...$fetchModeArgs): PDOStatement|false {
        $this->queryCount++;
        return parent::query($query);
    }
}

$conn = new CountingShoeConnection('sqlite::memory:');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec('CREATE TABLE tl_events (shoe_id INTEGER, extra_shoe_id INTEGER, run_distance REAL, warmup_distance REAL, cooldown_distance REAL)');
$conn->exec('INSERT INTO tl_events VALUES
    (1, NULL, 5.14, 0.24, 0.31),
    (1, 0, 3.12, 0.4, 0.4),
    (1, 2, 4.12, 0.5, 0.2),
    (2, 2, 6.14, 1.0, 0.5),
    (2, 3, 2.0, 0.1, 0.2),
    (99, 99, 100, 1, 1)');

if (getShoesDistances($conn, array()) !== array() || $conn->queryCount !== 0) {
    throw new RuntimeException('An empty shoe list must not query the database.');
}

$distances = getShoesDistances($conn, array('1', '2', '3', '4'));
$expected = array(1 => 13.7, 2 => 10.3, 3 => 0.3);
if ($conn->queryCount !== 1 || $distances !== $expected) {
    throw new RuntimeException('Unexpected shoe distances or query count: ' . json_encode($distances));
}

$conn->exec('DROP TABLE tl_events');
try {
    getShoesDistances($conn, array(1));
    throw new RuntimeException('Database errors must not be suppressed.');
} catch (PDOException $e) {
    // The endpoint reports database errors through its existing error handler.
}

echo "Shoe distance tests passed.\n";
