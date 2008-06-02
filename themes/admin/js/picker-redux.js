var mcms_picker = {
  mySubmit: function (URL) {
    if (window.mcms_picker_return === undefined) {
      alert('Please load /themes/admin/js/bebop.js');
      return false;
    }
    mcms_picker_return(URL);
  }
};
