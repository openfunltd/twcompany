<?php

putenv('DATABASE_URL=mysql://user:pass@ip/database');

// for upload to s3
putenv('S3_KEY=');
putenv('S3_SECRET=');

putenv('MYSQL_DATABASE_URL=' . getenv('DATABASE_URL'));

putenv('ELASTIC_USER=');
putenv('ELASTIC_PASSWORD=');
putenv('ELASTIC_PREFIX=');
putenv('ELASTIC_URL=https://localhost:9200');
