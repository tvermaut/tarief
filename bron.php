<?php

include("domein.class.php");

//$gemeenten = ['Valkenburg', 'Katwijk', 'Monster'];

function sorteer($a, $b){
  if($a->klasse == $b->klasse){return 0;}
  elseif($a->klasse < $b->klasse){return -1;}
  else{return 1;}
  }

  function sorteerTariefsoorten($a, $b){
    if($a->type == $b->type){return 0;}
    elseif($a->type < $b->type){return 1;}
    else{return -1;}
    }

function valuta($nr){return '&fnof;' . number_format($nr, 2, ",", ".");}

$tsc = (isset($_GET['tsc']) ? $_GET['tsc'] : null);

$rest_url = "https://oat.hisgis.nl/oat-ws/rest/tarieven" . (isset($_GET['gemeente']) ? '?gemeente=' . urlencode($_GET['gemeente']) : '') ;
$content=file_get_contents($rest_url);
$data=json_decode($content);


//$ggs=[];
//$ggs['duin']      = ["Duin & Woeste Gronden"];
//$ggs['bouwland']  = ["Bouwlanden","Geestlanden","Geestlanden en Duingrond","Geestlanden en duinen","Geestlanden","Geest en Duingronden"];
//$ggs['tuin']      = ["Moestuinen","Tuynen","Tuinen"];
//$ggs['boomgaard'] = ["tariefsoortnamen" => "Boomgaarden","Boomkwekerijen"];
//$ggs['weiland']   = ["Wei en Hooiland","Bleekvelden", "Hooylanden","Weylanden","Weilanden","Hooilanden","Weilanden (dijken)"];
//$ggs['nat']       = ["Moerassen","Rietlanden","Begroeide slikken","Linnen bleekerijen","Laag Weiland","Buitenweiden","Bieslanden"];
//$ggs['bos']       = ["Bosschen",
//                     "Bosschen (hakhout)","Bosch Hakhout","Bosschen (Hakhout)","Bosschen/Hakhout","Bosschenhakhout","Boschen Hakhout","Bosschen hakhout","Hakhout",
//                     "Bosschen (opgaand hout)","Bosch opgaand hout"];
//$ggs['vermaak']   = ["Aanleg tot vermaak","Bloem Tuinen en Bloemisterijen","Gronden van vermaak","Gronden van Vermaak"];


$aantal_kolommen = (isset($_GET['c']) ? $_GET['c'] : 3 );


$totaaltekst = "";
foreach ($data->results as $k => $v) {
  $g = new Gemeente($v);

  usort($g->tariefsoorten, "sorteerTariefsoorten");
  $gebouwd = [];
  $ongebouwd = [];
  $polders = [];
  $onbelast = [];
  $vaarten = [];
  $rechten=[];
  $opp_der_geb=[];
  foreach ($g->tariefsoorten as $k => $v){
    usort($v->tarieven, "sorteer");
    if($v->tsc && $v->tsc->categorie=="Oppervlakte der Gebouwen"){
      array_push($opp_der_geb, $v);
      }
    elseif($v->opmerking == "polder"){
      if($tsc==null){array_push($polders, $v);}
      }
    elseif($v->opmerking == "onbelast"){
      if($tsc==null){array_push($onbelast, $v);}
      }
    elseif($v->opmerking == "recht"){
      if($tsc==null){array_push($rechten, $v);}
      }
    elseif($v->opmerking == "vaart"){
      if($tsc==null){array_push($vaarten, $v);}
      }
    else{
      if($tsc==null or ($tsc == $v->tsc->categorie)){
        if ($v->type == 'ONGEBOUWD'){array_push($ongebouwd, $v);}
        else                        {array_push($gebouwd,   $v);}
      }
      }
    }

  $tekst = (count($ongebouwd) + count($gebouwd) > 0 ? "<h2 href='#$g->naam'><a id='$g->naam'>$g->naam</a></h2><div class='meerkoloms'>" : '');

  if(count($ongebouwd)>0){
    $tekst .= "<h3>Ongebouwd</h3>";
    foreach ($ongebouwd as $k => $v){$tekst .= $v->toLongHTML();}
    }
  if(count($gebouwd)>0){
    $tekst .= "<h3>Gebouwd</h3>";
    foreach($gebouwd as $k => $v){$tekst .= $v->toLongHTML();}
    }
  if(count($opp_der_geb)>0){
    $tekst .= "<h3>Oppervlakte <small>der gebouwde eigendommen</small></h3>";
    foreach($opp_der_geb as $k => $v){$tekst .= $v->toShortHTML();}
    }
  if(count($rechten)>0){
    $tekst .= "<h3>Grondgebonden rechten</h3><ul>";
    foreach($rechten as $k => $v){$tekst .= '<li>' . $v->toHTML() . '</li>';}
    $tekst .= "</ul>";
    }
  if(count($vaarten)>0){
    $tekst .= "<h3>Vaarten en kanalen</h3><ul>";
    foreach($vaarten as $k => $v){$tekst .= '<li>' . $v->toHTML() . '</li>';}
    $tekst .= "</ul>";
    }
  if(count($onbelast)>0){
    $tekst .= "<h3>Onbelast</h3><ul>";
    foreach($onbelast as $k => $v){$tekst .= '<li>' . $v->printOnbelast() . '</li>';}
    $tekst .= "</ul>";
    }
  if(count($polders)>0){
    $tekst .= "<h3>Polderlasten</h3><ol>";
    foreach($polders as $k => $v){$tekst .= '<li>' . $v->toHTML() . '</li>';}
    $tekst .= "</ol>";
    }

  $tekst = (count($ongebouwd) + count($gebouwd) > 0 ?  $tekst .= "</div><hr>" : '');
  $totaaltekst .= $tekst;
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
  <meta http-equiv="Cache-control" content="no-store">
  <link rel="stylesheet" type="text/css" href="bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="bootstrap-table.min.css">
  <script src="jquery-3.5.1.slim.min.js"></script>
  <script src="bootstrap.bundle.min.js"></script>
  <script src="bootstrap-table.min.js"></script>
  <title>Tarieven <?php echo (empty($_GET['gemeente']) ? "" : " " . $_GET['gemeente']); ?></title>
  <link rel="stylesheet" type="text/css" href="stijl.css?v=211111111111111">
  <style>
  .meerkoloms {
    column-count: <?php echo $aantal_kolommen;?>;
  }

.pastel_groen{
  background-color:#c5e1a5;
}

  </style>
</head>

<body lang="nl-nl">

<?php echo $totaaltekst ; ?>
<script>
window.onload=function(){
var bedragen = document.getElementsByClassName("bedrag");
var NummerFormaat = new Intl.NumberFormat('nl-NL');
for(var i=0; i<bedragen.length; i++){
  bedragen[i].innerHTML = "&fnof; " + NummerFormaat.format(bedragen[i].innerHTML);
  }
}

$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
</script>
</body>
</html>
