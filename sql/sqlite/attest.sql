CREATE TABLE gossamer_package_release_attestations (
    id INTEGER NOT NULL PRIMARY KEY,
    release_id INTEGER REFERENCES gossamer_package_releases (id),
    attestor INTEGER REFERENCES gossamer_providers (id),
    attestation TEXT, -- 'reproduced', 'spot-check', 'code-review', 'sec-audit'
    ledgerhash TEXT,
    metadata TEXT
);
