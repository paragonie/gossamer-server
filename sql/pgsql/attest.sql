CREATE TABLE gossamer_package_release_attestations (
    id BIGSERIAL PRIMARY KEY,
    release_id BIGINT REFERENCES gossamer_package_releases (id),
    attestor BIGINT REFERENCES gossamer_providers (id),
    attestation TEXT, -- 'reproduced', 'spot-check', 'code-review', 'sec-audit'
    ledgerhash TEXT,
    metadata TEXT
);
