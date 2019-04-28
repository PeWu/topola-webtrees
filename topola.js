/** Will contain the GEDCOM contents when it's fetched from webtrees. */
var gedcom = null;
/** Set to true when the iframe has signaled it is ready to receive data. */
var ready = false;

/**
 * Send GEDCOM file to iframe if the iframe is ready to reaceive data and
 * the GEDCOM to send is ready.
 */
function maybeSendData() {
  if (!ready || !gedcom) {
    return;
  }
  var frame = document.getElementById('topolaFrame');
  frame.contentWindow.postMessage({message: 'gedcom', gedcom}, '*');
}

function onMessage(message) {
  if (message.data.message === 'ready') {
    ready = true;
    maybeSendData();
  }
}

function handleData(data) {
  gedcom = data;
  maybeSendData();
}

// Initialize in onload to ensure the iframe has been created.
window.onload = function() {
  var frame = document.getElementById('topolaFrame');
  // Listen to messages from the iframe.
  window.addEventListener('message', onMessage);
  // Signal the iframe that we are ready to receive messages.
  frame.contentWindow.postMessage({message: 'parent_ready'}, '*');

  // Fetch GEDCOM data.
  jQuery.get(GEDCOM_URL, handleData);
}
