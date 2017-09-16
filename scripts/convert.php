<?php
$fh = fopen(__DIR__ . '/meta.csv', 'w');
$headerOut = false;
$json = json_decode(file_get_contents(__DIR__ . '/tmp/all.json'), true);
foreach($json['features'] AS $k => $v) {
  if(false === $headerOut) {
    $headerOut = true;
    fputcsv($fh, array_keys($json['features'][$k]['properties']));
  }
  fputcsv($fh, $json['features'][$k]['properties']);
  $objId = $json['features'][$k]['properties']['OBJECTID'];
  $json['features'][$k]['properties'] = array(
    'OBJECTID' => $objId,
  );
}
file_put_contents(__DIR__ . '/tmp/all.geo.json', json_encode($json));
exec("/usr/local/bin/geo2topo -o " . __DIR__ . "/all.topo.json " . __DIR__ . "/tmp/all.geo.json");
