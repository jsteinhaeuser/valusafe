<?php

namespace Tests\Fakes;

/**
 * Test-Double für die Database-Klasse (db.php), beschränkt auf die
 * exakten Query-Formen, die RateLimiter.php verwendet. Führt kein echtes
 * SQL aus, sondern hält den Zustand pro (ip_address, action) in einem
 * PHP-Array — schnell, ohne DB, aber verhaltenstreu zur echten Logik.
 */
class FakeRateLimitDatabase
{
    /** @var array<string, array{attempts:int, first_attempt:string, last_attempt:string, locked_until:?string}> */
    private array $rows = [];

    private function key(string $ip, string $action): string
    {
        return $ip . '|' . $action;
    }

    /** Erlaubt Tests, einen Ausgangszustand direkt zu setzen (z. B. "vor 10 Minuten"). */
    public function seedRow(string $ip, string $action, int $attempts, string $firstAttempt, ?string $lastAttempt = null, ?string $lockedUntil = null): void
    {
        $this->rows[$this->key($ip, $action)] = [
            'attempts'      => $attempts,
            'first_attempt' => $firstAttempt,
            'last_attempt'  => $lastAttempt ?? $firstAttempt,
            'locked_until'  => $lockedUntil,
        ];
    }

    public function execute(string $sql, array $params = []): bool
    {
        if (str_contains($sql, 'CREATE TABLE')) {
            return true;
        }

        if (str_contains($sql, 'INSERT INTO rate_limit')) {
            [$ip, $action] = $params;
            $this->rows[$this->key($ip, $action)] = [
                'attempts'      => 1,
                'first_attempt' => date('Y-m-d H:i:s'),
                'last_attempt'  => date('Y-m-d H:i:s'),
                'locked_until'  => null,
            ];
            return true;
        }

        if (str_contains($sql, 'UPDATE rate_limit')) {
            if (str_contains($sql, 'SET attempts = 1, first_attempt = NOW()')) {
                [$ip, $action] = $params;
                $this->rows[$this->key($ip, $action)] = [
                    'attempts'      => 1,
                    'first_attempt' => date('Y-m-d H:i:s'),
                    'last_attempt'  => date('Y-m-d H:i:s'),
                    'locked_until'  => null,
                ];
                return true;
            }

            if (str_contains($sql, 'locked_until = DATE_ADD')) {
                [$newAttempts, $lockoutTime, $ip, $action] = $params;
                $key = $this->key($ip, $action);
                $this->rows[$key]['attempts']     = $newAttempts;
                $this->rows[$key]['last_attempt'] = date('Y-m-d H:i:s');
                $this->rows[$key]['locked_until'] = date('Y-m-d H:i:s', time() + $lockoutTime);
                return true;
            }

            // Einfaches Increment ohne Sperre
            [$newAttempts, $ip, $action] = $params;
            $key = $this->key($ip, $action);
            $this->rows[$key]['attempts']     = $newAttempts;
            $this->rows[$key]['last_attempt'] = date('Y-m-d H:i:s');
            return true;
        }

        if (str_contains($sql, 'DELETE FROM rate_limit')) {
            [$ip, $action] = $params;
            unset($this->rows[$this->key($ip, $action)]);
            return true;
        }

        return true;
    }

    /** @return array|false */
    public function selectOne(string $sql, array $params = [])
    {
        [$ip, $action] = array_pad($params, 2, null);
        $row = $this->rows[$this->key((string)$ip, (string)$action)] ?? null;

        if (str_contains($sql, 'SELECT locked_until FROM rate_limit')) {
            if ($row && $row['locked_until'] && strtotime($row['locked_until']) > time()) {
                return ['locked_until' => $row['locked_until']];
            }
            return false;
        }

        if (str_contains($sql, 'TIMESTAMPDIFF')) {
            if ($row && $row['locked_until'] && strtotime($row['locked_until']) > time()) {
                return ['remaining' => strtotime($row['locked_until']) - time()];
            }
            return false;
        }

        if (str_contains($sql, 'SELECT attempts, first_attempt FROM rate_limit')) {
            return $row ?: false;
        }

        if (str_contains($sql, 'SELECT * FROM rate_limit')) {
            return $row ?: false;
        }

        return false;
    }
}
