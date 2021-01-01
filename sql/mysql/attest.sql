CREATE TABLE gossamer_package_release_attestations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    release_id BIGINT REFERENCES gossamer_package_releases (id),
    attestor BIGINT REFERENCES gossamer_providers (id),
    attestation TEXT, -- 'reproduced', 'spot-check', 'code-review', 'sec-audit'
    ledgerhash TEXT,
    metadata TEXT,
    PRIMARY KEY (id)
);
