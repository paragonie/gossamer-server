<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;

/**
 * Trait DbTrait
 * @package ParagonIE\GossamerServer
 *
 * @property array<string, string|array> $settings
 */
trait DbTrait
{
    /** @var ?EasyDB $db */
    protected $db;

    /**
     * @return EasyDB
     */
    public function db(): EasyDB
    {
        if (!$this->db) {
            $this->db = Factory::create(
                (string) ($this->settings['database']['dsn'] ?? 'sqlite::memory:'),
                (string) ($this->settings['database']['username'] ?? ''),
                (string) ($this->settings['database']['password'] ?? ''),
                (array) ($this->settings['database']['options'] ?? [])
            );
        }
        return $this->db;
    }
}
