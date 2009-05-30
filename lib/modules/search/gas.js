google.load("search", "1");

function OnLoad() {
  var dc = new google.search.WebSearch();
  var gres = document.getElementById('gasr');
  var gctl = document.getElementById('gasf');

  if (gres == null)
    alert('Google Search disabled: element #gasr not found.');
  else if (gctl == null)
    alert('Google Search disabled: element #gasf not found.');
  else {
    dc.setUserDefinedLabel('HOSTNAME');
    dc.setSiteRestriction('HOSTNAME/node/');

    var options = new GsearcherOptions();
    options.setExpandMode(GSearchControl.EXPAND_MODE_OPEN);
    options.setRoot(gres);

    var searchControl = new google.search.SearchControl();
    searchControl.addSearcher(dc, options);

    // Tell the searcher to draw itself and tell it where to attach
    searchControl.draw(gctl);

    // Execute an inital search
    searchControl.execute("QUERY");
  }
}

google.setOnLoadCallback(OnLoad);
