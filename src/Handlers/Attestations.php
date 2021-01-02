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
 * Class Attestations
 * @package ParagonIE\GossamerServer\Handlers
 */
class Attestations implements HandlerInterface
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
        /** @var int $releaseId */
        $releaseId = $this->db()->cell(
            "SELECT
                r.id
            FROM gossamer_package_releases r
            JOIN gossamer_packages p ON r.package = p.id
            WHERE p.provider = ? AND r.package = ? AND r.version = ?",
            $providerId,
            $packageId,
            $version
        );
        if (empty($releaseId)) {
            return $this->redirect('/gossamer-api/releases/' . $provider . '/' . $package);
        }
        /** @var array<string, mixed> $attestations */
        $attestations = $this->db()->run(
            "SELECT
                u.name AS attestor,
                a.attestation,
                r.ledgerhash
            FROM gossamer_package_release_attestations a
            JOIN gossamer_package_releases r ON a.release_id = r.id
            JOIN gossamer_providers u ON a.attestor = v.id
            WHERE a.release_id = ?
            ORDER BY id DESC",
            $providerId,
            $packageId,
            $releaseId
        );
        if (empty($attestations)) {
            $releases = [];
        }
        return $this->json($attestations);
    }
}
