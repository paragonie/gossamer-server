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
 * Class DefaultHandler
 * @package ParagonIE\GossamerServer\Handlers
 */
class DefaultHandler implements HandlerInterface
{
    use HandlerTrait;

    protected $statusCode = 200;

    /**
     * @param int $status
     * @return self
     */
    public function setStatusCode(int $status = 200): self
    {
        $this->statusCode = $status;
        return $this;
    }

    /**
     * @param ServerRequestInterface $req
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $req): ResponseInterface
    {
        return $this->json([
            'error' => 'not implemented'
        ], $this->statusCode);
    }
}
