<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer\Handlers;

use ParagonIE\GossamerServer\{
    HandlerInterface,
    HandlerTrait
};
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface
};

/**
 * Class ProviderList
 * @package ParagonIE\GossamerServer\Handlers
 */
class ProviderList implements HandlerInterface
{
    use HandlerTrait;

    /**
     * @param ServerRequestInterface $req
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        $providers = $this->db()->run(
            "SELECT * FROM gossamer_providers ORDER BY name ASC"
        );
        return $this->json($providers);
    }
}
