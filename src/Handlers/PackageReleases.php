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
 * Class PackageReleases
 * @package ParagonIE\GossamerServer\Handlers
 */
class PackageReleases implements HandlerInterface
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
        $packageId = $this->db()->cell(
            "SELECT name FROM gossamer_packages WHERE provider = ? AND name = ?",
            $providerId,
            $this->vars['package']
        );
        if (empty($packageId)) {
            return $this->redirect('/gossamer-api/packages/' . $this->vars['provider']);
        }
        $releases = $this->db()->run(
            "SELECT
                r.version,
                r.signature,
                r.ledgerhash,
                k.publickey
            FROM gossamer_package_releases r
            JOIN gossamer_packages p ON r.package = p.id
            JOIN gossamer_providers v ON r.provider = v.id
            JOIN gossamer_provider_publickeys k ON r.publickey = k.id
            WHERE p.provider = ? AND r.package = ?   
                  AND NOT r.revoked
                  AND NOT k.revoked
            ORDER BY id DESC
            ",
            $providerId,
            $packageId
        );
        if (empty($releases)) {
            $releases = [];
        }
        return $this->json($releases);
    }
}
