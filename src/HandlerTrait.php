<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer;

use GuzzleHttp\Psr7\Response;
use ParagonIE\EasyDB\{
    EasyDB,
    Factory
};

/**
 * Trait HandlerTrait
 * @package ParagonIE\GossamerServer
 */
trait HandlerTrait
{
    /** @var ?EasyDB $db */
    protected $db;

    /** @var array $settings */
    protected $settings = [];

    /**
     * Construct this Handler from the router.
     *
     * @param array $settings
     * @return HandlerInterface
     */
    public function init(array $settings): HandlerInterface
    {
        $this->settings = $settings;

        /** @var HandlerInterface $self */
        $self = $this;
        return $self;
    }

    /**
     * @return EasyDB
     */
    public function db(): EasyDB
    {
        if (!$this->db) {
            $this->db = Factory::create(
                $this->settings['database']['dsn'] ?? 'sqlite::memory:',
                $this->settings['database']['username'] ?? '',
                $this->settings['database']['password'] ?? '',
                $this->settings['database']['options'] ?? []
            );
        }
        return $this->db;
    }

    /**
     * @param array-key $name
     * @return mixed
     * @throws \Exception
     */
    public function __get($name)
    {
        if (isset($this->settings[$name])) {
            return $this->settings[$name];
        }
        throw new \Exception("Property {$name} not defined!");
    }

    /**
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return Response
     */
    public function json(array $data, int $status = 200, array $headers = []): Response
    {
        $headers['content-type'] = 'application/json';
        return new Response($status, $headers, json_encode($data, JSON_PRETTY_PRINT));
    }
}
