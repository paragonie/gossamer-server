<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer;

use GuzzleHttp\Psr7\Response;

/**
 * Send this HTTP response to the client.
 * Terminates the current PHP script execution.
 *
 * @param Response $response
 * @return void
 */
function send_response(Response $response): void
{
    http_response_code($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $headers) {
        foreach ($headers as $header) {
            header("{$name}: {$header}");
        }
    }
    echo $response->getBody();
    exit(0);
}
