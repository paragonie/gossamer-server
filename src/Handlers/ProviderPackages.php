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
 * Class ProviderPackages
 * @package ParagonIE\GossamerServer\Handlers
 */
class ProviderPackages implements HandlerInterface
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
        /** @var array<array-key, array<string, string>> $packages */
        $packages = $this->db()->run(
            "SELECT name FROM gossamer_packages WHERE provider = ?",
            $providerId
        );
        foreach ($packages as $i => $pk) {
            $packages[$i]['releases-url'] = '/gossamer-api/releases/' .
                urlencode($provider) . '/' .
                urlencode($pk['name']);
        }

        return $this->json($packages);
    }
}
