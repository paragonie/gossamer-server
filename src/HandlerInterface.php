<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer;

use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface
};

/**
 * Interface HandlerInterface
 * @package ParagonIE\GossamerServer
 */
interface HandlerInterface
{
    /**
     * @param ServerRequestInterface $req
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $req): ResponseInterface;

    /**
     * Construct this Handler from the router.
     *
     * @param array<array-key, string|array|bool> $settings
     * @return HandlerInterface
     */
    public function init(array $settings): HandlerInterface;
}
