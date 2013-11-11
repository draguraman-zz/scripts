#!/usr/bin/php
<?php
/**
 * File: flushcache.php
 * {File description}
 * 
 * @package ExampleVille
 * @copyright Copyright (&copy;) 2010, Zynga Game Network, Inc.
 */

// create a new cURL resource
$ch = curl_init();

// set URL and other appropriate options
curl_setopt($ch, CURLOPT_URL, "http://localhost:8001/internal/apc-info.php?flush=1");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// grab URL and pass it to the browser
$json = curl_exec($ch);

// close cURL resource, and free up system resources
curl_close($ch);

if (!$json) {
  error_log('Accessing local cache flush URL failed!');
  exit(1);
}

$data = json_decode($json);

if (!$data) {
  error_log('Invalid data returned from apc cache flush URL:');
  error_log($json);
  exit(1);
}

if (count($data->system->cache_list) > 1) {
  error_log('More than 1 file still in the apc cache! Cache may not have flushed!');
  exit(1);
}

echo "Cache flushed OK\n";
exit(0);