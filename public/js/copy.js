function copyToClipboard(elem) {
  var target = elem;
  var currentFocus = document.activeElement;
  target.focus();
  target.setSelectionRange(0, target.value.length);
  var succeed;
  try {
    succeed = document.execCommand("copy");
  } catch (e) {
    console.warn(e);
    succeed = false;
  }
  if (currentFocus && typeof currentFocus.focus === "function") {
    currentFocus.focus();
  }
  return succeed;
}
