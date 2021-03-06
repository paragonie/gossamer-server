<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer;

use GuzzleHttp\Psr7\Response;

/**
 * Trait HandlerTrait
 * @package ParagonIE\GossamerServer
 */
trait HandlerTrait
{
    use DbTrait;

    /** @var array<array-key, string|array|bool> $settings */
    protected $settings = [];

    /** @var array<array-key, string|array> $vars */
    protected $vars;

    /**
     * HandlerTrait constructor.
     * @param array<array-key, string|array> $vars
     */
    public function __construct(array $vars)
    {
        $this->vars = $vars;
    }

    /**
     * Construct this Handler from the router.
     *
     * @param array<array-key, string|array|bool> $settings
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
     * @param string $name
     * @return array<array-key, mixed>|string
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

    /**
     * @param string $uri
     * @return Response
     */
    public function redirect(string $uri): Response
    {
        return new Response(302, ['Location' => $uri], $uri);
    }
}
