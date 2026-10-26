<?php

declare(strict_types=1);

namespace App\Shared\Support;

use App\Core\Database;
use App\Core\Logger;
use Throwable;

/**
 * Registro de acciones administrativas en audit_logs.
 *
 * No guarda nombres, documentos, correos ni otros datos personales. Si la
 * auditoría falla, la operación de negocio no se interrumpe.
 */
final class AuditLogger
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @param array<string, scalar|null> $metadata
     */
    public function record(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        array $metadata = [],
        ?string $ip = null
    ): void {
        try {
            $encoded = $metadata === []
                ? null
                : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $statement = $this->database->connection()->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata, ip_address)
                 VALUES (:user_id, :action, :entity_type, :entity_id, :metadata, :ip_address)'
            );
            $statement->execute([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metadata' => $encoded === false ? null : $encoded,
                'ip_address' => $ip,
            ]);
        } catch (Throwable $exception) {
            Logger::warning('No se pudo registrar la auditoría.', [
                'type' => $exception::class,
            ]);
        }
    }
}
