<?php

include("domein.class.php");

$tabel = [];

//$gemeenten = ['Valkenburg', 'Katwijk', 'Monster'];

//$g = $_GET['gemeente'];

$rest_url = "https://oat.hisgis.nl/oat-ws/rest/tarieven/";
$content=file_get_contents($rest_url);
$data=json_decode($content);


$ggs=[];
//$ggs['duin']      = ["Duin & Woeste Gronden"];
#$ggs['bouwland']  = ["Bouwlanden","Geestlanden","Geestlanden en Duingrond","Geestlanden en duinen","Geestlanden","Geest en Duingronden"];
#$ggs['tuin']      = ["Moestuinen","Tuynen","Tuinen"];
#$ggs['boomgaard'] = ["Boomgaarden","Boomkwekerijen"];
//$ggs['weiland']   = ["Wei en Hooiland","Bleekvelden", "Hooylanden","Weylanden","Weilanden","Hooilanden","Weilanden (dijken)"];
//$ggs['nat']       = ["Moerassen","Rietlanden","Begroeide slikken","Linnen bleekerijen","Laag Weiland","Buitenweiden","Bieslanden"];
//$ggs['bos']       = ["Bosschen",
//                     "Bosschen (hakhout)","Bosch Hakhout","Bosschen (Hakhout)","Bosschen/Hakhout","Bosschenhakhout","Boschen Hakhout","Bosschen hakhout","Hakhout",
//                     "Bosschen (opgaand hout)","Bosch opgaand hout"];
//$ggs['vermaak']   = ["Aanleg tot vermaak","Bloem Tuinen en Bloemisterijen","Gronden van vermaak","Gronden van Vermaak"];

foreach ($data->results as $k => $v) {
  $g = new Gemeente($v);
  $tabelregel = "<tr><td>" . $g->naam . "</td>";

  foreach ($ggs as $kolomnaam => $mogelijkeTariefsoortNamen) {
    $tabelregel .= "<td>";
    foreach ($g->tariefsoorten as $k => $v){
      if($v->type=='ONGEBOUWD' && in_array($v->naam, $mogelijkeTariefsoortNamen)){$tabelregel .= $v->toHTML();}
      }
    $tabelregel .= "</td>";
    }
  $tabelregel .=                "</tr>";
  array_push($tabel, $tabelregel);
  }


/*
$verschilkleuring = [
  array('min'=> -9999, 'max' => -25,'kleur'=> '#2c7bb6'),
  array('min'=>   -25, 'max' => -15,'kleur'=> '#81bad8'),
  array('min'=>   -15, 'max' => -5, 'kleur'=> '#c7e6db'),
  array('min'=>    -5, 'max' => 5,  'kleur'=> '#ffffbf'),
  array('min'=>     5, 'max' => 15, 'kleur'=> '#fec980'),
  array('min'=>    15, 'max' => 25, 'kleur'=> '#f17c4a'),
  array('min'=>    25, 'max' => 9999,'kleur'=> '#d7191c')
];

foreach($results as $perceel){
  $p_id = $perceel['sectie'] . $perceel['perceelnr'] . $perceel['perceelnrtvg'];
  $percelenOSM[$p_id] = array(
    'sectie'       => strtoupper($perceel['sectie']),
    'perceelnr'    => $perceel['perceelnr'],
    'perceelnrtvg' => strtolower($perceel['perceelnrtvg']),
    'inOAT'        => false,
    'opp'          => $perceel['opp']
    );
  }
//usort($percelenOSM, "sorteer");


//setlocale(LC_ALL, 'nld_nld');
function valuta($nr){return '&fnof;' . number_format($nr, 2, ",", ".");}

foreach($data->artikelen as $artikel){
  $a = new Artikel($artikel);
  $sys->artikelen[$a->sleutel()] = $a;
  }

foreach($data->results as $k => $v){
  $t =& $tabel[$k];
  $t['gemeente'] = $g;
  $t['sectie'] = strtoupper($v->sectie->letter);
  $t['perceelnr'] = $v->perceelnr;
  $t['perceelnrtvg'] = strtolower($v->perceelnrtvg);
  $t['oat'] = $v->oatScan->code;
  $t['oppervlak'] = $v->oppervlak;
  $t['aftrek'] = $v->aftrek/100;
  $t['belink'] = $v->belastbaar_inkomen/100;
  $t['soort'] = $v->grondGebruik;

  $tarieven = new Tarieven($v->perceelTarieven);
  $t['tarief'] = $tarieven->getTekst();
  $t['osm'] = false;
  }

foreach ($tabel as $k => $j) {
  $t =& $tabel[$k];
  $p_id = $t['sectie'] . $t['perceelnr'] . $t['perceelnrtvg'];

  foreach ($percelenOSM as $key => & $v) {
    if( strcasecmp($v['sectie'] , $t['sectie'])==0 && $v['perceelnr'] == $t['perceelnr'] && $v['perceelnrtvg'] == $t['perceelnrtvg']) {
          $t['osm'] = true;
          $v['inOAT'] = true;
          $t['opp'] = $v['opp'];
          $t['verschil'] = 100-round(($t['oppervlak']/$t['opp']) * 100.00);

          $kleur = null;
          foreach ($verschilkleuring as $n) {
            if($t['verschil'] >= $n['min'] && $t['verschil'] < $n['max']) {$kleur = $n['kleur'];}
            }
          $t['kleur'] = $kleur;
          $percelenOSM[$key]['kleur'] = $kleur;
          $oud =  intval($statistiek['totaal_aantal_gekoppelde_percelen']);
          $statistiek['totaal_aantal_gekoppelde_percelen'] = $oud + 1;
          }
        }
    }

  foreach($percelenOSM as $m => $n){
    if(!$n['inOAT']){
      $q = [];
      $q['gemeente'] = $g;
      $q['sectie'] = $n['sectie'];
      $q['perceelnr'] = $n['perceelnr'];
      $q['perceelnrtvg'] = $n['perceelnrtvg'];
      $q['oat'] = null;
      $q['oppervlak'] = $n['opp'];
      $q['aftrek'] = null;
      $q['belink'] = null;
      $q['soort'] = null;
      $q['artikel'] = 0;
      $q['artikelnr'] = 0;
      $q['osm'] = true;
      $q['tarief'] = null;
      array_push($tabel, $q);
      }
    }

  usort($tabel, "sorteer");

foreach($data->results as $k => $v){
    $t =& $tabel[$k];
    $t['artikelnr'] = Artikel::genereerSleutel($v->artikelLink->reeks, $v->artikelLink->artikelnr, $v->artikelLink->artikelnrtvg);
    $a = $sys->artikelen[$t['artikelnr']];
    if($vorigartikelnr == $t['artikelnr']){$aantalOpeenvolgendeArtikelnrs++;}
    else{$aantalOpeenvolgendeArtikelnrs = 0;}
    $t['artikel'] = ($a ? $a->getTekst() : null );
    $t['aantalOpeenvolgendeArtikelnrs'] = $aantalOpeenvolgendeArtikelnrs;
    $vorigartikelnr = $t['artikelnr'];
    }

    foreach($data->results as $k => $v){
      $t =& $tabel[$k];
      $i = 0;
      while($tabel[$k+$i]['aantalOpeenvolgendeArtikelnrs'] >= $i ){
        $t['rowspanArtikel'] = $i+1;
        $i++;
        }
      }
*/
?>
<!DOCTYPE html>
<html lang="nl" xml:lang="nl" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Tarieven</title>
  <link rel="stylesheet" type="text/css" href="stijl.css?v=1111111111">
</head>

<body lang="nl-nl">

<table>
  <tr><th>naam</th><th>aantal tariefsoorten</th></tr>
  <?php
  foreach($tabel as $k => $v){
    echo $v;
    }
   ?>
</table>
</body>
</html>
