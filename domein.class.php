<?php

function genereerKlasseTariefHTML($klasse, $tarief){
  $html= "<div class='klasseerdiv'><table class='klassetarieftabel'><tr><td style='text-align:right;width:1.5em;'><span class='klasse'>$klasse</span></td>
          <td style='padding-left:3px; padding-right:3px;'>:</td><td style='text-align:right;width:3.5em;'><span class='bedrag'>$tarief</bedrag></td></tr></table></div>";
  return $html;
  }

$tsg = [];

function kaal($tekenreeks){return preg_replace('/\s+/', ' ',trim(strtolower($tekenreeks)) ); }

$json = json_decode(file_get_contents('https://data.hisgis.nl/w/api.php?action=wbgetentities&format=json&ids=Q101'));
$ess = [];
// ongebouwd
foreach($json->entities->Q101->claims->P33 as $v){
  foreach($v->qualifiers->P36 as $q){array_push($ess, $q->datavalue->value->id);}
  array_push($ess, $v->mainsnak->datavalue->value->id);
  }
// gebouwd
foreach($json->entities->Q101->claims->P34 as $v){
  foreach($v->qualifiers->P36 as $q){array_push($ess, $q->datavalue->value->id);}
  array_push($ess, $v->mainsnak->datavalue->value->id);
  }
$json2 = [];
while(sizeof($ess) > 0){
  $tempids = [];
  // opdelen in bachtes van 50 items vanwege beperking in wikibase-url
  for($i=0;$i<50 && sizeof($ess) > 0; $i++){
    array_push($tempids, $ess[0]);
    array_shift($ess);
    }
  $tempjson = json_decode(file_get_contents('https://data.hisgis.nl/w/api.php?action=wbgetentities&format=json&ids=' . join('|',$tempids)));
  $json2 = array_merge((Array)$tempjson->entities, $json2);
  }
foreach($json2 as $key => $e){
  $gg = $e->labels->nl->value;
  $tsg[$gg] = [];
  $tsg[$gg]['tariefsoortnaam'] = [];
  foreach($e->claims->P30 as $vv){array_push($tsg[$gg]['tariefsoortnaam'], kaal($vv->mainsnak->datavalue->value));}
  $tsg[$gg]['oatGebruik'] = [];
  foreach($e->aliases->nl as $vv){array_push($tsg[$gg]['oatGebruik'], kaal($vv->value));}
  }

  function CheckInTSG($tariefsoort, $oatgebruiknaam){
    $gevonden = false;
    $grondgebruiknaam = [];
    global $tsg;
    foreach($tsg as $tsg_sleutel => $tsg_v) {
      //var_dump($tsg_v['tariefsoortnaam']);
      if(in_array(strtolower($tariefsoort), $tsg_v['tariefsoortnaam']) && in_array(kaal($oatgebruiknaam), $tsg_v['oatGebruik'])){
        $gevonden = true;
        array_push($grondgebruiknaam, $tsg_sleutel);
        }
      }
    //if($gevonden){      echo $grondgebruiknaam . " : " . $oatgebruiknaam . "=>" . $tariefsoort . "\n";}
    return ($gevonden ? join(' + ', $grondgebruiknaam) : false );//$grondgebruiknaam
  }


function zoekTSC($obj){
  $r = null;
  foreach ($tsc as $id => $t) {
    if($t->categorie==$obj->categorie && $t->subcategorie==$obj->subCategorie){$r = $t;}
    }
  return $r;
  }


/*
 * Klassen
 */

class TariefsoortCategorie{
  public $categorie = null;
  public $subcategorie = null;
  public $tariefsoorten = [];

  public function __construct($obj){
    $this->categorie = $obj->categorie;
    $this->subcategorie = $obj->subCategorie;
    foreach($obj->tariefsoorten as $k => $v){
      array_push($this->tariefsoorten, $v);
      }
    return $this;
    }

  public function label(){return "<span class='border border-info text-info text-xl px-1 text-nowrap rounded'>" . $this->categorie . (empty($this->subcategorie) ? '' : ': ' . $this->subcategorie) . "</span>";}
  }

class Gemeente{
  public $naam = null;
  public $uniekeNaam = null;
  public $tariefsoorten = [];

  public function __construct($obj){
    $this->naam = $obj->naam;
    $this->uniekeNaam = $obj->uniekeNaam;
    $this->provincie = $obj->provincie->naam;
    foreach($obj->tariefsoorten as $k => $v){
      $ts = new Tariefsoort($this, $obj->tariefsoorten[$k]);
      array_push($this->tariefsoorten, $ts);
      }
    return $this;
    }
}

class Tariefsoort{
  public $tarieven = [];
  public $type = null;
  public $naam = null;
  public $opmerking = null;
  public $tariefVerwijzingen = [];
  public $gemeente = null;
  public $tsc = null;

  public function __construct($gemeente, $obj){
    $this->naam = $obj->naam;
    $this->type = $obj->type;
    $this->opmerking = $obj->opmerking;
    $this->gemeente = $gemeente;

    $zoekTSC = zoekTSC($obj->categorie);
    $this->tsc = ($zoekTSC==null ? new TariefsoortCategorie($obj->categorie) : $zoekTSC );
    foreach($obj->tarieven as $k => $v){
      $t = new Tarief($this, $v);
      array_push($this->tarieven, $t);
      }
    foreach($obj->tariefVerwijzingen as $k => $v){
      if(isset($v->doelTarief)){
        $sleutel = $v->tariefsoortNaar . ', klasse ' . $v->doelTarief->klasse;
        $this->tariefVerwijzingen[$sleutel] = new TariefVerwijzing($this, $v);
//        foreach ($this->tariefVerwijzingen[$sleutel]->oatGebruik as $r => $s) {
//          $this->oatGebruik .= "<span class='oatGebruik'>[" . $s . "x " . $r . "]</span>";
//          }
        }
      }
    return $this;
    }

  public function toHTML(){
    $html = "";
    foreach ($this->tarieven as $k => $v) {$html .= $v->toHTML();}
    $html .= "<span class='tariefsoort'>" . $this->naam . "</span>";
    return $html;
    }

  public function toShortHTML(){
    $html = "";
    foreach ($this->tarieven as $k => $v) {$html .= $v->toHTML();}
    return $html;
    }

  public function printOnbelast(){
    $html = "<span class='tariefsoort'>" . $this->naam . "</span>";
    foreach ($this->tarieven as $k => $v) {$html .= $v->toHTML();}
    return $html;
    }

  public function toLongHTML(){
    $html = "<span class='tariefsoort text-xl'>" . $this->naam . "</span>" . (empty($this->tsc->categorie) ? '' : "&thinsp;" . $this->tsc->label() ) ;
    $html .= $this->opmerking;
    if(sizeof($this->tarieven) >= 1){
      $html .= "<ul class='mb-3'>";
      foreach ($this->tarieven as $k => $v) {$html .= "<li style='word-break: break-all;word-wrap: break-word!important;white-space:normal;' class='text-break'>" . $v->toHTML() . "</li>";}
      $html .= "</ul>";
      }
    elseif(sizeof($this->tarieven) == 1){foreach ($this->tarieven as $k => $v) {$html .= "<span class='tarieftabelentry'>" . $v->toHTML() . "</span>" ;}}

    if(sizeof($this->tariefVerwijzingen) >= 1){
      $html .= "<ul>";
      foreach ($this->tariefVerwijzingen as $k => $v) {$html .= "<li>" . $v->toHTML() . "</li>";}
      $html .= "</ul>";
      }
  /*  elseif(sizeof($this->tariefVerwijzingen) == 1){
      foreach ($this->tariefVerwijzingen as $k => $v) {
        $html .= " <span class='tariefverwijzing'>&#8614; [" . $k . "]</span>:  <span class='tarieftabelentry'>" .
//        $v->toHTML() .
//var_dump($v) .
        "</span>" ;
        }
      }*/

    return $html;
    }
}

class TariefVerwijzing{
  public $klasse = null;
  public $tariefsoortNaar = null;
  public $doelTarief = null;
  public $oatGebruik = null;
  public $grondgebruik = null;
  public $tariefsoort = null;

  public function __construct($tariefsoort, $obj){
    $this->klasse = $obj->klasse;
    $this->tariefsoortNaar = $obj->tariefsoortNaar;
    $this->doelTarief = new Tarief($tariefsoortNaar, $obj->doelTarief);
    $this->tariefsoort = $tariefsoort;
    $og = (array) $obj->oatGebruik;
    arsort($og);
    foreach ($og as $r => $s) {
      $ogNorm = CheckInTSG($tariefsoort, $r);
      $this->oatGebruik .= "<span class='oatGebruik'>" . $s . "&thinsp;&times;&thinsp;" . ($ogNorm ? $ogNorm : $r ) . "</span>";
      }
    if(isset($obj->grondgebruik)){
      $this->grondgebruik = ($obj->grondgebruik->categorie != null ? "<span class='categorie'>" . $obj->grondgebruik->categorie . " " . "<span class='subcategorie'>&lt;" . $obj->grondgebruik->subCategorie . "&gt;</span></span>" : null);
      }
    }

  public function toHTML(){
    return $this->doelTarief->toHTML() . "<span class='tariefverwijzing'>&#8614; <span class='tariefverwijzingbox'>" . $this->tariefsoortNaar . ", als klasse " . $this->klasse . "</span></span> " . $this->oatGebruiklabel;
    }
  }

class Tarief{
  public $klasse = null;
  public $tarief = null;
  public $grondgebruik = null;
  public $oatGebruik = [];
  public $oatGebruiklabel = '';


  public $opmerking = null;
  public $tariefsoort = null;
  public $normNaam = null;

  public function __construct($tariefsoort, $obj){
    $this->klasse       = $obj->klasse;
    $this->tarief       = $obj->tarief/100;
    $this->tariefsoort  = $tariefsoort;
    $this->grondgebruik = ($obj->grondgebruik->categorie != null ? "<span class='categorie'>" . $obj->grondgebruik->categorie . " " . "<span class='subcategorie'>&lt;" . $obj->grondgebruik->subCategorie . "&gt;</span></span>" : null);
    $this->opmerking    = $obj->opmerking;
    $this->oatGebruiklabel = '';

    foreach ($obj->oatGebruik as $k => $v) {
      $oatGebruikObject = new OatGebruik($this->tariefsoort->naam, $k, $v);
      if(array_key_exists($oatGebruikObject->getNaam(), $this->oatGebruik)){$this->oatGebruik[$oatGebruikObject->getNaam()]->aantal += $oatGebruikObject->aantal;}
      else{$this->oatGebruik[$oatGebruikObject->getNaam()] = $oatGebruikObject;}
      }
    usort($this->oatGebruik, function($a, $b){return $a->aantal < $b->aantal;});

    $tmp = bin2hex(random_bytes(16));
    if(count($this->oatGebruik)>20){
      $waa      = $this->oatGebruik[0]->aantal;
      $sleu     = ($this->oatGebruik[0]->isStandaardBinnenTariefsoort ? '' : $this->oatGebruik[0]->getNaam());
      $kl = ($this->oatGebruik[0]->isStandaardBinnenTariefsoort ? ' standaardOatGebruik' : '');
      $waa_bis  = $this->oatGebruik[1]->aantal;
      $sleu_bis = ($this->oatGebruik[1]->isStandaardBinnenTariefsoort ? '' : $this->oatGebruik[1]->getNaam());
      $kl_bis = ($this->oatGebruik[1]->isStandaardBinnenTariefsoort ? ' standaardOatGebruik' : '');
      $this->oatGebruiklabel .= <<<EOT
        <span id="e-$tmp" class='oatGebruik$kl'>$waa&thinsp;&times;&thinsp;$sleu</span>
        <span id="f-$tmp" class='oatGebruik$kl_bis'>$waa_bis&thinsp;&times;&thinsp;$sleu_bis</span>
        <span id="p-$tmp" class='oatGebruik' onClick='document.getElementById("p-$tmp").style.display="none"; document.getElementById("e-$tmp").style.display="none"; document.getElementById("f-$tmp").style.display="none"; document.getElementById("$tmp").style.display="block";' style='cursor:pointer;'> ... </span><ol class="customnummerlijst" id="$tmp">
EOT;
      foreach ($this->oatGebruik as $k => $v) {$this->oatGebruiklabel .= "<li value='$v->aantal'>" . $v->getNaam() . "</li>";}
      $this->oatGebruiklabel .= "</ol>";
      }
    else{
      foreach ($this->oatGebruik as $k => $v) {$this->oatGebruiklabel .= $v->getLabel();}
      }
  return $this;
  }

  public function toHTML(){



    //$ogNorm = CheckInTSG($this->tariefsoort, $k);
    //$html = "";
    //$html .= "<span class='klasse'>" . $this->klasse . "</span>: <span class='bedrag'>" . $this->tarief . "</span>" . ' ' . $this->opmerking . $this->oatGebruik;
    return genereerKlasseTariefHTML($this->klasse,$this->tarief) . " " . $this->opmerking . ' ' . $this->oatGebruiklabel . ' ' .  $this->grondgebruik; //($this->normNaam ? ">>" . $this->normNaam . "<<" : '') .
    }
}

class OatGebruik{
  public $aantal = null;
  public $naam = null;
  public $tariefsoortnaam = null;
  public $isStandaardBinnenTariefsoort = false;
  public $normNaam = null;

  public function getNaam(){return (empty($this->normNaam) ? $this->naam : $this->normNaam);}
  public function getLabel(){
    $sbt = $this->isStandaardBinnenTariefsoort;
    $naam = $this->getNaam();
    return "<wbr><span style='white-space:nowrap; color:brown;' class='align-middle border border-dark px-1 mx-1 rounded " . ($sbt ? "pastel_groen text-secondary' data-toggle='tooltip' data-placement='top' title='$naam' " : " text-color--red'") . ">" . $this->aantal . "&thinsp;<b>&times;</b>" . ($sbt ? '' : "&thinsp;<span style='user-select:all;'>" . $naam) . "</span></span>";
    }

  public function __construct($tariefsoortnaam, $naam, $aantal){
    $this->aantal = $aantal;
    $this->naam = $naam;
    $this->tariefsoortnaam = $tariefsoortnaam;
    $tmp = CheckInTSG($this->tariefsoortnaam, $this->naam);
    if($tmp){
      $this->isStandaardBinnenTariefsoort = true;
      $this->normNaam = $tmp;
      //echo $normNaam . " : " . $naam . "\n";
      }
    }
  }

 ?>
