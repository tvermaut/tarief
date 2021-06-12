class Tariefsoort{
  constructor(naam, gebouwd){
    this.naam = naam;
    this.gebouwd = null;
    this.gg = {};
  }
}

class Provincie{
  constructor(code, naam){
    this.code = code;
    this.naam = naam;
  }
}

class Gemeente{
  constructor(naam, code, provincie, status){
    this.naam = naam;
    this.code = code;
    this.provincie = new Provincie(provincie.code, provincie.naam);
    this.status = status;
    this.tariefsoorten = [];
  }
}

ats = {};

oat = null;
var xhttp = new XMLHttpRequest();
xhttp.onreadystatechange = function() {
  if (this.readyState == 4 && this.status == 200){
    oat = JSON.parse(this.responseText);
    alert(oat.keys());
    for g in oat['results']{
      gemeente = new Gemeente(g.naam, g.status);
      for t in g.tariefsoorten{
        if(t.naam not in ats){ats[t.naam] = new Tariefsoort(t.naam, t.gebouwd);}
        ts = ats[t.naam];
        for tarief in t.tarieven{
          for gg in tarief.oatGebruik.keys(){
            if(not(gg in ts)){
              ts[gg] = tarief.oatGebruik[gg];
            }
          }
        }
      }
    }
  }
};
xhttp.open("GET", "https://oat.hisgis.nl/oat-ws/rest/tarieven");
xhttp.setRequestHeader("Content-type", "application/json");
xhttp.send(null);
