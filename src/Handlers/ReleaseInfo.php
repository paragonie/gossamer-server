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
 * Class ReleaseInfo
 * @package ParagonIE\GossamerServer\Handlers
 */
class ReleaseInfo implements HandlerInterface
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

        /** @var string $package */
        $package = $this->vars['package'] ?? '';

        /** @var string $version */
        $version = $this->vars['version'] ?? '';

        /** @var int $providerId */
        $providerId = $this->db()->cell(
            "SELECT count(id) FROM gossamer_providers WHERE name = ?",
            $provider
        );
        if (empty($providerId)) {
            return $this->redirect('/gossamer-api/providers');
        }
        /** @var int $packageId */
        $packageId = $this->db()->cell(
            "SELECT name FROM gossamer_packages WHERE provider = ? AND name = ?",
            $providerId,
            $package
        );
        if (empty($packageId)) {
            return $this->redirect('/gossamer-api/packages/' . $provider);
        }

        $releaseInfo = $this->db->run(
            "SELECT
                v.name AS provider,
                p.name AS package,
                r.version,
                k.publickey,
                r.signature,
                r.revoked,
                r.ledgerhash,
                r.revokehash,
                r.metadata
            FROM gossamer_package_releases r
            JOIN gossamer_packages p ON r.package = p.id
            JOIN gossamer_providers v ON p.provider = v.id
            WHERE v.id = ? AND p.id = ? AND r.version = ?
            ORDER BY r.revoked ASC
            ",
            $providerId,
            $packageId,
            $version
        );
        if (empty($releaseInfo)) {
            return $this->redirect('/gossamer-api/releases/' . $provider . '/' . $package);
        }
        return $this->json($releaseInfo);
    }
}
