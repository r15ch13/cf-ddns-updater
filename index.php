<?php
require __DIR__ . '/vendor/autoload.php';
use \Symfony\Component\HttpFoundation\Request;
use \Cloudflare\API\Auth\APIKey as CloudflareAPIKey;
use \Cloudflare\API\Adapter\Guzzle as CloudflareAdapter;
use \Cloudflare\API\Endpoints\Zones as CloudflareZones;
use \Cloudflare\API\Endpoints\DNS as CloudflareDNS;
header('Content-Type: text/plain');

function updateOrCreate(CloudflareDNS $dns, string $zoneID, $record, string $type, string $name, string $content, int $ttl = 120, $proxied = false)
{
  if (!empty($record)) {
    return $dns->updateRecordDetails($zoneID, $record->id, ['type' => $type, 'name' => $name, 'content' => $content, 'ttl' => $ttl, 'proxied' => $proxied]);
  } else {
    return $dns->addRecord($zoneID, $type, $name, $content, $ttl, $proxied);
  }
}

$request = Request::createFromGlobals();

$email = (string)$request->get('email');
$key = (string)$request->get('key');
$zone = (string)$request->get('zone');
$domain = (string)$request->get('domain');
$ipv4 = (string)$request->get('ipv4', (string)$request->get('ip', (string)$request->getClientIp()));
$ipv6 = (string)$request->get('ipv6');
$ttl = (int)$request->get('ttl', 120);
$proxied = !is_null($request->get('proxied'));

// Cloudflare API Headers
if (empty($email) || empty($key)) {
  $email = (string)$request->headers->get('x-auth-email');
  $key = (string)$request->headers->get('x-auth-key');
}

// FRITZ!Box Auth Fields
if (empty($email) || empty($key)) {
  $email = (string)$request->headers->get('php_auth_user');
  $key = (string)$request->headers->get('php_auth_pw');
}

if (empty($email) || empty($key) || empty($zone) || empty($domain)) {
  $host = htmlentities($request->getHttpHost());
  echo "Usage:\n\n";
  echo "curl 'https://$host/?zone=example.org&domain=home.example.org&wildcard=true' \\\n";
  echo "  -H 'X-Auth-Email: <cloudflare email> \\\n";
  echo "  -H 'X-Auth-Key: <cloudflare key>\n\n";
  echo "zone = Cloudflare Zone Name (not the ID)\n";
  echo "domain = Domain or Subdomain\n";
  echo "ip|ipv4 = Update ipv4 (no auto detection)\n";
  echo "ipv6 = Update ipv6\n";
  echo "ttl = Update TTL value (default: 120)\n";
  echo "wildcard = Use *.example.org (flag)\n";
  echo "proxied = Set proxy status (flag)\n\n";
  echo "The username and password field of your FRITZ!Box settings will be used.\n\n";
  echo "FRITZ!Box Example: https://$host/?zone=example.org&domain=<domain>&ipv4=<ipaddr>&ipv6=<ip6addr>";
  return;
}

$key = new CloudflareAPIKey($email, $key);
$adapter = new CloudflareAdapter($key);
$zones = new CloudflareZones($adapter);
$dns = new CloudflareDNS($adapter);
$zoneID = $zones->getZoneID($zone);

$record = reset($dns->listRecords($zoneID, 'A', $domain)->result);
$result = updateOrCreate($dns, $zoneID, $record, 'A', $domain, $ipv4, $ttl, $proxied);
echo "Updated $domain to $ipv4" . PHP_EOL;
if(!$result) { print_r($result); }

if (!is_null($request->get('wildcard'))) {
  $wildcard = reset($dns->listRecords($zoneID, 'A', '*.' . $domain)->result);
  $result = updateOrCreate($dns, $zoneID, $wildcard, 'A', '*.' . $domain, $ipv4, $ttl, $proxied);
  echo "Updated *.$domain to $ipv4";
  if(!$result) { print_r($result); }
}

if ($ipv6) {
  $record = reset($dns->listRecords($zoneID, 'AAAA', $domain)->result);
  $result = updateOrCreate($dns, $zoneID, $record, 'AAAA', $domain, $ipv6, $ttl, $proxied);
  echo "Updated $domain to $ipv6" . PHP_EOL;
  if(!$result) { print_r($result); }

  if (!is_null($request->get('wildcard'))) {
    $wildcard = reset($dns->listRecords($zoneID, 'AAAA', '*.' . $domain)->result);
    $result = updateOrCreate($dns, $zoneID, $wildcard, 'AAAA', '*.' . $domain, $ipv6, $ttl, $proxied);
    echo "Updated *.$domain to $ipv6";
    if(!$result) { print_r($result); }
  }
}
