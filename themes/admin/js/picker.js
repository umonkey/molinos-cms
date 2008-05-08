var mcms_picker = {
  init: function () {
  },
  mySubmit: function (URL) {
    var win = tinyMCEPopup.getWindowArg("window");
    win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = URL;

    // for image browsers: update image dimensions
    if (win.ImageDialog.getImageData) win.ImageDialog.getImageData();
    if (win.ImageDialog.showPreviewImage) win.ImageDialog.showPreviewImage(URL);

    tinyMCEPopup.close();
  }
};

tinyMCEPopup.onInit.add(mcms_picker.init, mcms_picker);
