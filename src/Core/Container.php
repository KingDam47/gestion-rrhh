<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Contenedor de servicios explícito, sin autowiring ni reflexión.
 *
 * Las fábricas se registran en src/container.php y construyen cada
 * dependencia a mano para que el grafo sea fácil de leer.
 */
final class Container
{
    /** @var array<string, Closure(self): mixed> */
    private array $bindings = [];

    /** @var array<string, bool> */
    private array $singletons = [];

    /** @var array<string, mixed> */
    private array $resolved = [];

    /**
     * @param Closure(self): mixed $factory
     */
    public function set(string $id, Closure $factory): void
    {
        $this->bindings[$id] = $factory;
        $this->singletons[$id] = false;
        unset($this->resolved[$id]);
    }

    /**
     * @param Closure(self): mixed $factory
     */
    public function singleton(string $id, Closure $factory): void
    {
        $this->bindings[$id] = $factory;
        $this->singletons[$id] = true;
        unset($this->resolved[$id]);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        if (!isset($this->bindings[$id])) {
            throw new RuntimeException(sprintf('No hay un servicio registrado para "%s".', $id));
        }

        $service = ($this->bindings[$id])($this);

        if ($this->singletons[$id]) {
            $this->resolved[$id] = $service;
        }

        return $service;
    }
}
