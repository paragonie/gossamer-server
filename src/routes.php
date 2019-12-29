<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer;

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use ParagonIE\GossamerServer\Handlers\{
    GossamerHome,
    Homepage,
    PackageReleases,
    ProviderKeys,
    ProviderList,
    ProviderPackages
};

return simpleDispatcher(function (RouteCollector $r) {
    $r->addGroup('/gossamer-api', function (RouteCollector $r) {
        $r->get('/releases/{provider:[A-Za-z0-9\-_]+}/{package:[A-Za-z0-9\-_]+}', PackageReleases::class);
        $r->get('/packages/{provider:[A-Za-z0-9\-_]+}', ProviderPackages::class);
        $r->get('/providers', ProviderList::class);
        $r->get('/verification-keys/{provider:[A-Za-z0-9\-_]+}', ProviderKeys::class);
        $r->get('/', GossamerHome::class);
    });
    $r->get('/', Homepage::class);
});
