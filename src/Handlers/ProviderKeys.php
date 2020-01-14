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
 * Class ProviderKeys
 * @package ParagonIE\GossamerServer\Handlers
 */
class ProviderKeys implements HandlerInterface
{
    use HandlerTrait;

    /**
     * @param ServerRequestInterface $req
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        $providerId = $this->db()->cell(
            "SELECT count(id) FROM gossamer_providers WHERE name = ?",
            $this->vars['provider'] ?? ''
        );
        if (empty($providerId)) {
            return $this->redirect('/gossamer-api/providers');
        }
        return $this->json(
            $this->db()->run(
                "SELECT publickey, ledgerhash, metadata 
                 FROM gossamer_provider_publickeys
                 WHERE provider = ? AND NOT revoked",
                $providerId
            )
        );
    }
}
