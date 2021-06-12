<?php

include("domein.class.php");

$rest_url = "https://oat.hisgis.nl/oat-ws/rest/categorieen" ; //. (isset($_GET['gemeente']) ? '?gemeente=' . $_GET['gemeente'] : '')
$content=file_get_contents($rest_url);
$data=json_decode($content);

foreach ($data->results as $k => $v) {
  $tsci = new TariefsoortCategorie($v);
  }

  ?>
