<?php
/**
 * 阿里云 OSS 配置文件
 */

return [
    'accessKeyId' => getenv('alioss_accesskeyid'),
    'accessKeySecret' => getenv('alioss_accesskeysecret'),
    'endpoint' => getenv('alioss_endpoint'),
    'upload_bucket' => getenv('alioss_uploadbucket'),
];