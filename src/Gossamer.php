<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer;

use ParagonIE\Certainty\{
    Exception\CertaintyException,
    RemoteFetch
};
use ParagonIE\Gossamer\{
    Db\PDO,
    Http\Guzzle,
    Synchronizer,
    Util,
    Verifier\Chronicle
};
use ParagonIE\Corner\{
    CornerInterface,
    Exception as CornerException
};

/**
 * Class Gossamer
 * @package ParagonIE\GossamerServer
 */
class Gossamer
{
    use DbTrait;

    /** @var array $settings */
    protected $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return array<array-key, array{url: string, public-key: string, trust: string}>
     * @throws CornerInterface
     * @throws \SodiumException
     */
    protected function getChronicles(): array
    {
        if (empty($this->settings['chronicles'])) {
            throw (new CornerException('No chronicles configured.'))
                ->setSupportLink('https://github.com/paragonie/gossamer-server/issues')
                ->setHelpfulMessage(
                    "If you are seeing this error message, there were no Chronicles configured in\n" .
                    "your local settings. The best way to fix this is to run bin/configure and then\n" .
                    "select the chronicle submenu, then add multiple instances that replicate the\n" .
                    "same network.\n" .
                    "\n" .
                    "As a guideline, you should always be reading from replica instances, rather\n" .
                    "than the source Chronicle that messages are directly published on.\n"
                );
        }
        /** @var array<array-key, array{url: string, public-key: string, trust: string}> $chronicles */
        $chronicles = [];
        /**
         * @var array<array-key, string> $data
         */
        foreach ($this->settings['chronicles'] as $data) {
            $chronicles[] = [
                'url' =>
                    $data['url'],
                'public-key' =>
                    Util::b64uEncode(
                        Util::rawBinary(
                            $data['public-key'],
                            SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                        )
                    ),
                'trust' =>
                    !empty($data['blind-faith'])
                        ? Chronicle::TRUST_ZEALOUS
                        : Chronicle::TRUST_BASIC
            ];
        }
        return $chronicles;
    }

    /**
     * @return string
     */
    protected function getSuperProvider(): string
    {
        return (string) ($this->settings['super-provider'] ?? '');
    }

    /**
     * @return Synchronizer
     * @throws CornerInterface
     * @throws CertaintyException
     * @throws \SodiumException
     */
    public function getSynchronizer(): Synchronizer
    {
        $http = new Guzzle(
            new RemoteFetch(GOSSAMER_SERVER_ROOT . '/local/certs')
        );
        return new Synchronizer(
            $this->getPDOAdapter(),
            $http,
            new Chronicle($http),
            $this->getChronicles(),
            $this->getSuperProvider()
        );
    }

    /**
     * Callback for libgossamer, Action class.
     *
     * Stores attestations and relates them to specific releases.
     *
     * @param string $provider
     * @param string $package
     * @param string $release
     * @param string $attestor (Provider)
     * @param string $attestation (Verb)
     * @param array $meta
     * @param string $hash
     * @return bool
     */
    public function registerAttestation(
        string $provider,
        string $package,
        string $release,
        string $attestor,
        string $attestation,
        array $meta = array(),
        string $hash = ''
    ): bool {
        $db = $this->db();
        $releaseId = $db->cell(
            "SELECT r.id
                FROM gossamer_package_releases r
                JOIN gossamer_packages p ON r.package = p.id
                JOIN gossamer_providers u ON p.provider = u.id
                WHERE u.name = ? AND p.name = ? AND r.version = ?",
            $provider,
            $package,
            $release
        );
        if (empty($releaseId)) {
            return false;
        }
        $attestorId = $db->cell(
            "SELECT id FROM gossamer_providers WHERE name = ?",
            $attestor
        );
        if (empty($attestorId)) {
            return false;
        }

        $db->beginTransaction();
        $db->insert(
            'gossamer_package_release_attestations',
            [
                'release_id' => $releaseId,
                'attestor' => $attestorId,
                'attestation' => $attestation,
                'ledgerhash' => $hash,
                'metadata' => json_encode($meta)
            ]
        );
        return $db->commit();
    }

    public function getPDOAdapter(): PDO
    {
        return (new PDO($this->db()->getPdo()))
            ->setAttestCallback([$this, 'registerAttestation']);
    }
}
