# Gossamer Server

[![Build Status](https://github.com/paragonie/gossamer-server/actions/workflows/ci.yml/badge.svg)](https://github.com/paragonie/gossamer-server/actions)
[![Static Analysis](https://github.com/paragonie/gossamer-server/actions/workflows/psalm.yml/badge.svg)](https://github.com/paragonie/gossamer-server/actions)
[![Latest Stable Version](https://poser.pugx.org/paragonie/gossamer-server/v/stable)](https://packagist.org/packages/paragonie/gossamer-server)
[![Latest Unstable Version](https://poser.pugx.org/paragonie/gossamer-server/v/unstable)](https://packagist.org/packages/paragonie/gossamer-server)
[![License](https://poser.pugx.org/paragonie/gossamer-server/license)](https://packagist.org/packages/paragonie/gossamer-server)

Standalone REST API for Gossamer.

## Setup

First, clone with Git then install the dependencies.

```
composer update --no-dev
```

Next, you'll need to configure your instance. You can do this manually, or use
the `bin/configure` script to setup your database and Chronicles.

Once your database is configured, run `bin/make` to create the necessary
database schema (tables, indexes).

Once your database and Chronicles are both configured, run `bin/sync` to do the
initial data synchronization.

Finally, setup a cronjob to run `bin/sync` fairly regularly (at least
once per hour).
