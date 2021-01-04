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
 * Class Homepage
 * @package ParagonIE\GossamerServer\Handlers
 */
class Homepage implements HandlerInterface
{
    use HandlerTrait;

    /**
     * @param ServerRequestInterface $req
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        return $this->json([
            'gossamer-version' => '0.1.0',
            'routes' => [
                '/gossamer-api/providers' => [
                    'description' => 'List of providers',
                    'parameters' => [],
                ],
                '/gossamer-api/verification-keys/:provider' => [
                    'description' => 'Get all of the currently-trusted verification keys for a given provider',
                    'parameters' => [
                        ':provider' => [
                            'type' => 'string',
                            'format' => '^[A-Za-z0-9\-_]+$'
                        ]
                    ],
                ],
                '/gossamer-api/packages/:provider' => [
                    'description' => 'Get all of the packages owned by a provider',
                    'parameters' => [
                        ':provider' => [
                            'type' => 'string',
                            'format' => '^[A-Za-z0-9\-_]+$'
                        ]
                    ],
                ],
                '/gossamer-api/releases/:provider/:package' => [
                    'description' => 'Get all of the releases for a given package',
                    'parameters' => [
                        ':package' => [
                            'type' => 'string',
                            'format' => '^[A-Za-z0-9\-_]+$'
                        ],
                        ':provider' => [
                            'type' => 'string',
                            'format' => '^[A-Za-z0-9\-_]+$'
                        ]
                    ],
                ],
                '/gossamer-api/attestations/:provider/:package/:version' => [
                    'description' => 'Get all of the third-party attestations for a given package release.',
                    'parameters' => [
                        ':package' => [
                            'type' => 'string',
                            'format' => '^[A-Za-z0-9\-_]+$'
                        ],
                        ':provider' => [
                            'type' => 'string',
                            'format' => '^[A-Za-z0-9\-_]+$'
                        ],
                        ':version' => [
                            'type' => 'string',
                            'format' => '^[A-Za-z0-9\-_\.]+$'
                        ]
                    ],
                ],
                '/gossamer-api' => [
                    'description' => 'Gossamer API index.',
                    'parameters' => [],
                ]
            ]
        ]);
    }
}
