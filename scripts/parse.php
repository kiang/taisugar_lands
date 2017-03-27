<?php
$tmpPath = dirname(__DIR__) . '/tmp/lands';
if(!file_exists($tmpPath)) {
  mkdir($tmpPath, 0777, true);
}

$fc = new stdClass();
$fc->type = 'FeatureCollection';
$fc->features = array();

$fh = fopen(__DIR__ . '/lands.csv', 'r');
$header = fgetcsv($fh, 2048);
$count = 0;
//http://twland.ronny.tw/index/search?lands[]=台中市,新磁磘段,701
while($line = fgetcsv($fh, 2048)) {
  $line = array_combine($header, $line);
  $q = array($line['縣市名稱']);
  if(!empty($line['小段名稱'])) {
    $q[] = "{$line['段號名稱']}段{$line['小段名稱']}小段";
  } else {
    $q[] = "{$line['段號名稱']}段";
  }
  if(!$line['子號'] !== '0000') {
    $q[] = $line['母號'] . '-' . $line['子號'];
  } else {
    $q[] = $line['母號'];
  }
  $qFile = $tmpPath . '/' . implode('_', $q);
  if(!file_exists($qFile)) {
    foreach($q AS $k => $v) {
      $q[$k] = urlencode($v);
    }
    file_put_contents($qFile, file_get_contents('http://twland.ronny.tw/index/search?lands[]=' . implode(',', $q)));
  }
  $json = json_decode(file_get_contents($qFile));

  $matched = false;
  if(!empty($json->features)) {
    foreach($json->features AS $f) {
      $line['鄉鎮名稱'] = str_replace('巿', '市', $line['鄉鎮名稱']);
      if($f->properties->鄉鎮 === $line['鄉鎮名稱'] || $f->properties->鄉鎮 === $line['縣市名稱']) {
        $matched = $f;
      }
    }
    if(false === $matched && count($json->features) === 1) {
      $matched = array_pop($json->features);
    }
    if(false === $matched) {
      $target = '';
      switch("{$line['縣市名稱']}{$line['鄉鎮名稱']}") {
        case '高雄市岡山區':
        $target = '湖內區';
        break;
        case '彰化縣員林市':
        $target = '員林鎮';
        break;
        case '彰化縣田中鎮':
        $target = '溪州鄉';
        break;
        case '雲林縣斗六市':
        $target = '二崙鄉';
        break;
      }
      foreach($json->features AS $f) {
        if($f->properties->鄉鎮 === $target) {
          $matched = $f;
        }
      }
    }
    if(false !== $matched) {
      foreach($line AS $k => $v) {
        $matched->properties->{'s_' . $k} = $v;
      }
      $fc->features[] = $matched;
    }
  }
  if(false === $matched) {
    ++$count;
  }
}
$targetFile = dirname(__DIR__) . '/lands.geo.json';
file_put_contents($targetFile, json_encode($fc));
error_log($count . ' not found');
