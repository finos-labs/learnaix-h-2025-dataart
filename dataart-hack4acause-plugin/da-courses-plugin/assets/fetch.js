// local/helloworld/assets/fetch.js
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('helloworld-fetch');
  const out = document.getElementById('helloworld-result');
  if (!btn || !out) return;

  // Load Moodle's AMD modules at runtime (no build needed for YOUR file)
  require(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    function renderResult(data) {
      const isObj = data && typeof data === 'object';
      const body = isObj ? JSON.stringify(data, null, 2) : String(data ?? '');
      out.innerHTML = '<pre>' + body.replace(/[<>&]/g, c =>
        ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c])) + '</pre>';
    }

    btn.addEventListener('click', () => {
      btn.disabled = true;
      out.innerHTML = '<div class="spinner-border" role="status" aria-label="Loading"></div>';

      const endpoint = btn.dataset.endpoint || '';

      const requests = Ajax.call([{
        methodname: 'local_helloworld_fetch', // your external function
        args: { endpoint }
      }]);

      requests[0]
        .then(resp => {
          // resp = { ok, status, json, text }
          renderResult(resp.json ? JSON.parse(resp.json) : resp.text);
        })
        .catch(Notification.exception)
        .finally(() => { btn.disabled = false; });
    });
  });
});
