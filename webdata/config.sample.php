<?php

putenv('DATABASE_URL=mysql://user:pass@ip/database');
putenv('SEARCH_URL=https://elastic-url/foo/bar');

// for upload to s3
putenv('S3_KEY=');
putenv('S3_SECRET=');

putenv('MYSQL_DATABASE_URL=' . getenv('DATABASE_URL'));
