# phpch

PHP Clickhouse client

Receive and process clickhouse server response in a streamed manner (via TCP socket). The main goal of this package is
to reduce memory consumption on PHP side processing huge clickhouse server responses (receive data by chunks, yield
from generators etc.).

This package has dependency: 'phpunit' in dev environment.

For usage example see 'example.php'