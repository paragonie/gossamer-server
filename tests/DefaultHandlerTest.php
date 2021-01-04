<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer\Tests;

use GuzzleHttp\Psr7\ServerRequest;
use ParagonIE\GossamerServer\Handlers\DefaultHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Class DefaultHandlerTest
 * @package ParagonIE\GossamerServer\Tests
 */
class DefaultHandlerTest extends TestCase
{
    /**
     * @covers \ParagonIE\GossamerServer\Handlers\DefaultHandler
     */
    public function testResponse()
    {
        $handler = new DefaultHandler([]);
        $request = new ServerRequest('GET', '/', []);
        $response = $handler($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $body = (string) $response->getBody()->getContents();
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded);
    }
}
