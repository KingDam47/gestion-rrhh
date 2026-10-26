<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Conexión PDO perezosa.
 *
 * La conexión se abre en el primer acceso para que las páginas estáticas
 * (por ejemplo, el login) puedan renderizarse aunque la base no esté lista.
 */
final class Database
{
    private ?PDO $connection = null;

    /**
     * @param array{
     *     host: string,
     *     port: int,
     *     database: string,
     *     username: string,
     *     password: string,
     *     charset: string
     * } $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function connection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET time_zone = '+00:00'";
        }

        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );
        } catch (\PDOException $exception) {
            throw new RuntimeException(
                'No se pudo conectar con la base de datos. Revisa la configuración DB_* del archivo .env.',
                0,
                $exception
            );
        }

        return $this->connection;
    }
}
