<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Trait HandlerTrait
 * @package ParagonIE\GossamerServer
 */
trait HandlerTrait
{
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
