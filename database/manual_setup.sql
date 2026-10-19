-- Solo para una base nueva donde ya se importaron 001 y 002 manualmente.
CREATE TABLE schema_migrations(version VARCHAR(100) PRIMARY KEY,checksum CHAR(64) NOT NULL,applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO schema_migrations(version,checksum) VALUES('001_northwind.sql','c91843c6c847b82ea7c43064f90f62eb259cefd192409aa2236a005bc985c91f');
INSERT INTO schema_migrations(version,checksum) VALUES('002_application.sql','5c15f9327873cf30aed08ac20e2abc938da6af364d47292b836daa6d443fe254');
