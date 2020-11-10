# doctrine.yaml

doctrine:
    dbal:
        connections:
            default:
                url: '%env(resolve:DATABASE_URL)%'

                # IMPORTANT: You MUST configure your server version,
                # either here or in the DATABASE_URL env var (see .env file)
                server_version: '5.7'

                default_table_options:
                    charset: utf8mb4
                    collate: utf8mb4_unicode_ci



... make projectors public (could be later done with compiler pass)