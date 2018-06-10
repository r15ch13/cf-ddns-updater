<?php
require 'vendor/autoload.php';
use Illuminate\Http\Request;
header('Content-Type: text/plain');

function updateOrCreate($dns, $zone_identifier, $record, $type, $name, $content)
{
  if (!empty($record)) {
    return $dns->update($zone_identifier, $record->id, $type, $name, $content, 120);
  } else {
    return $dns->create($zone_identifier, $type, $name, $content, 120);
  }
}

$request = Request::capture();

$email = $request->get('email');
$key = $request->get('key');
$zone = $request->get('zone');
$domain = $request->get('domain');
$ipv4 = $request->get('ipv4', $request->get('ip', $request->ip()));
$ipv6 = $request->get('ipv6');

// Cloudflare API Headers
if (empty($email) || empty($key)) {
  $email = $request->headers->get('x-auth-email');
  $key = $request->headers->get('x-auth-key');
}

// Fritz!Box Auth Fields
if (empty($email) || empty($key)) {
  $email = $request->headers->get('php_auth_user');
  $key = $request->headers->get('php_auth_pw');
}

if (empty($email) || empty($key) || empty($zone) || empty($domain)) {
  $host = htmlentities($_SERVER['HTTP_HOST']);
  echo 'Usage:' . PHP_EOL . PHP_EOL;
  echo 'curl \'https://'. $host .'/?zone=ZONE_ID&domain=home.example.org&wildcard=true\' \\' . PHP_EOL;
  echo '  -H \'X-Auth-Email: <cloudflare email>\' \\' . PHP_EOL;
  echo '  -H \'X-Auth-Key: <cloudflare key>' . PHP_EOL . PHP_EOL;
  echo 'zone = Cloudflare Zone' . PHP_EOL;
  echo 'domain = Domain or Subdomain' . PHP_EOL;
  echo 'wildcard = Use *.example.org' . PHP_EOL;
  echo 'ip|ipv4 = Update ipv4 (no auto detection)' . PHP_EOL;
  echo 'ipv6 = Update ipv6' . PHP_EOL . PHP_EOL;
  echo 'The username and password field of your FRITZ!Box settings will be used.' . PHP_EOL . PHP_EOL;
  echo 'FRITZ!Box Example: https://' . $host . '/?zone=ZONE_ID&domain=<domain>&ipv4=<ipaddr>&ipv6=<ip6addr>';
  return;
}

$client = new Cloudflare\Api($email, $key);
$zones = new Cloudflare\Zone($client);
$zone = reset($zones->zones($zone)->result);
$dns = new Cloudflare\Zone\Dns($client);

$record = reset($dns->list_records($zone->id, 'A', $domain)->result);
updateOrCreate($dns, $zone->id, $record, 'A', $domain, $ipv4);
echo "Updated $domain to $ipv4" . PHP_EOL;

if ($request->has('wildcard')) {
  $wildcard = reset($dns->list_records($zone->id, 'A', '*.' . $domain)->result);
  updateOrCreate($dns, $zone->id, $wildcard, 'A', '*.' . $domain, $ipv4);
  echo "Updated *.$domain to $ipv4";
}

if ($ipv6) {
  $record = reset($dns->list_records($zone->id, 'AAAA', $domain)->result);
  updateOrCreate($dns, $zone->id, $record, 'AAAA', $domain, $ipv6);
  echo "Updated $domain to $ipv6" . PHP_EOL;

  if ($request->has('wildcard')) {
    $wildcard = reset($dns->list_records($zone->id, 'AAAA', '*.' . $domain)->result);
    updateOrCreate($dns, $zone->id, $wildcard, 'AAAA', '*.' . $domain, $ipv6);
    echo "Updated *.$domain to $ipv6";
  }
}
