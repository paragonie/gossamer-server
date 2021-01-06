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
        /** @var string $provider */
        $provider = $this->vars['provider'] ?? '';

        /** @var int $providerId */
        $providerId = $this->db()->cell(
            "SELECT count(id) FROM gossamer_providers WHERE name = ?",
            $provider
        );
        if (empty($providerId)) {
            return $this->redirect('/gossamer-api/providers');
        }
        /** @var array{publickey: string, limited: bool, purpose: ?string, ledgerhash: string, metadata: string} $publicKeys */
        $publicKeys = $this->db()->run(
            "SELECT publickey, limited, purpose, ledgerhash, metadata 
             FROM gossamer_provider_publickeys
             WHERE provider = ? AND NOT revoked",
            $providerId
        );
        return $this->json($publicKeys);
    }
}
