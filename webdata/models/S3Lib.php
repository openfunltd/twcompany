<?php

class S3Lib
{
    protected static $_obj = null;
    protected static function getS3Obj()
    {
        if (!class_exists('AmazonS3')) {
            define('AWS_DISABLE_CONFIG_AUTO_DISCOVERY', true);
            include(__DIR__ . '/../stdlibs/sdk-1.6.0/sdk.class.php');
            if (!getenv('S3_KEY') or !getenv('S3_SECRET')) {
                throw new Exception('env S3_KEY & S3_SECRET not found');
            }
            CFCredentials::set(array(
                'development' => array(
                    'key' => getenv('S3_KEY'),
                    'secret' => getenv('S3_SECRET'),
                ),
            ));
        }
        if (is_null(self::$_obj)) {
            self::$_obj = new AmazonS3();
        }

        return self::$_obj;
    }

    public function buildIndex($target)
    {
        error_log("build index {$target}");
        $s3 = self::getS3Obj();

        if (!preg_match('#s3://([^/]*)/(.*)$#', $target, $matches)) {
            throw new Exception('must be s3://xxx');
        }
        $bucket = $matches[1];
        $prefix = $matches[2];

        $res = $s3->list_objects($bucket, array(
            'prefix' => $prefix,
            'delimiter' => '/',
        ));

        $total_size = 0;
        $all_last_modified = 0;

        foreach($res->body->CommonPrefixes as $dir) {
            $dir = $dir->Prefix;
            if ($dir == $prefix) {
                continue;
            }
            $ret = self::buildIndex("s3://{$bucket}/{$dir}");
            $all_last_modified = max($all_last_modified, $ret['last_modified']);
            $total_size += $ret['total'];
            $last_modified = $ret['last_modified'];
            $name = basename(rtrim($dir, '/'));
            $size = $ret['total'];

            $table_tr[] = "<tr><td><a href=\"" . htmlspecialchars($name) . "\">" . htmlspecialchars($name) . "/</a></td><td>" . date('Y/m/d H:i:s', $last_modified) . "</td><td>{$size}</td></tr>";
        }

        foreach($res->body->Contents as $file) {
            if (basename($file->Key) == 'index.html') {
                continue;
            }
            if ($file->Key == $prefix) {
                continue;
            }
            $last_modified = strtotime($file->LastModified);
            $all_last_modified = max($last_modified, $all_last_modified);
            $total_size += $file->Size;
            $name = basename($file->Key);

            $table_tr[] = "<tr><td><a href=\"" . htmlspecialchars($name) . "\">" . htmlspecialchars($name) . "</a></td><td>" . date('Y/m/d H:i:s', $last_modified) . "</td><td>{$file->Size}</td></tr>";
        }
        $body = '<html><html><meta http-equiv="last-modified" content="' . date('Y-m-d@H:i:s O', $all_last_modified) . '"><body>';
        $body .= "Last Modified: " . date('Y/m/d H:i:s', $all_last_modified) . "<br>";
        $body .= "Total Size: " . $total_size . "<br>";
        $body .= "<table border=\"1\"><tr><td>File</td><td>Modified Time</td><td>Size</td></tr>";
        $body .= implode('', $table_tr);
        $body .= "</table></body></html>";

        $res = $s3->create_object($bucket, ltrim($prefix . 'index.html', '/'), array(
            'body' => $body,
            'acl' => AmazonS3::ACL_PUBLIC,
            'contentType' => 'text/html',
        ));

        return array(
            'total' => $total_size,
            'last_modified' => $all_last_modified,
        );
    }

    public function putFile($file, $target, $content_type = null)
    {
        $s3 = self::getS3Obj();
        if (!preg_match('#s3://([^/]*)/(.*)#', $target, $matches)) {
            throw new Exception('must be s3://xxx');
        }
        $bucket = $matches[1];
        $prefix = $matches[2];

        $options = array(
            'fileUpload' => $file,
            'acl' => AmazonS3::ACL_PUBLIC,
        );
        if (!is_null($content_type)) {
            $options['contentType'] = $content_type;
        }

        $res = $s3->create_object($bucket, $prefix, $options);
    }
}
