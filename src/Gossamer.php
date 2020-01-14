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
     * @return array<array-key, array<string, string>>
     * @return array
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
        /** @var array<array-key, array<string, string>> $chronicles */
        $chronicles = [];
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
        return $this->settings['super-provider'] ?? '';
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
            new PDO($this->db()->getPdo()),
            $http,
            new Chronicle($http),
            $this->getChronicles(),
            $this->getSuperProvider()
        );
    }
}
