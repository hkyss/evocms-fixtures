(function () {
  var root = document.getElementById('evofx');
  if (!root) { return; }

  var token = root.dataset.token;
  var ceiling = parseInt(root.dataset.ceiling, 10) || 1;
  var panel = document.getElementById('evofx-panel');
  var body = root.querySelector('.body');
  var busy = false;

  function ask(action, payload) {
    busy = true;
    paint();
    var form = new FormData();
    form.append('__fixtures', action);
    form.append('__fixtures_token', token);
    Object.keys(payload || {}).forEach(function (key) { form.append(key, payload[key]); });

    return fetch(window.location.href, { method: 'POST', body: form, credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .catch(function (error) { return { ok: false, why: String(error) }; })
      .then(function (answer) { busy = false; return answer; });
  }

  var state = { tab: 'batches', batches: [], bench: null, message: null, failed: false };

  function esc(value) {
    return String(value).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function field(name, label, value) {
    return '<label>' + esc(label) + '<input name="' + name + '" type="number" min="0" value="' + esc(value) + '"></label>';
  }

  function batches() {
    if (!state.batches.length) { return '<p class="empty">Nothing generated.</p>'; }

    return state.batches.map(function (batch) {
      return '<div class="row"><div><strong>Batch ' + batch.id + '</strong>'
        + '<span>' + esc(batch.describe) + ' &middot; ' + batch.rows + ' rows in ' + batch.tables + ' tables</span></div>'
        + '<button class="thin danger" data-drop="' + batch.id + '">Drop</button></div>';
    }).join('') + '<div class="act"><button class="thin danger" data-drop="0">Drop every batch</button></div>';
  }

  function generate() {
    return '<form id="evofx-make"><div class="grid">'
      + field('documents', 'Documents', 5000)
      + field('folders', 'Folders (0 derives)', 0)
      + field('depth', 'Depth cap (0 is free)', 0)
      + field('templates', 'Templates', 4)
      + field('tmplvars', 'Template variables', 10)
      + field('values', 'Values per document', 4)
      + field('users', 'Users', 0)
      + field('member_groups', 'Member groups', 0)
      + field('document_groups', 'Document groups', 0)
      + '</div><div class="act"><button class="go" type="submit">Generate</button></div>'
      + '<p class="note">At most ' + ceiling + ' documents from here; larger batches belong on the console, '
      + 'where nothing times out. Everything written is recorded and can be dropped again.</p></form>';
  }

  function bench() {
    if (!state.bench) {
      return '<p class="empty">Reads the queries Evolution leans on and reports the median of each.</p>'
        + '<div class="act"><button class="go" data-bench="1">Run benchmark</button></div>';
    }

    if (!state.bench.ready) {
      return '<p class="empty">' + esc(state.bench.why) + '</p>'
        + '<div class="act"><button class="go" data-bench="1">Run benchmark</button></div>';
    }

    var counts = Object.keys(state.bench.counts).map(function (table) {
      return esc(table) + ' ' + state.bench.counts[table];
    }).join(' &middot; ');

    var rows = state.bench.probes.map(function (probe) {
      return '<tr><td class="q">' + esc(probe.name) + '<small>' + esc(probe.reads) + '</small></td>'
        + '<td class="n">' + probe.median.toFixed(2) + ' ms</td></tr>';
    }).join('');

    return '<table>' + rows + '</table>'
      + '<p class="note">Median of ' + state.bench.runs + ' runs, read-only. ' + counts + '</p>'
      + '<div class="act"><button class="go" data-bench="1">Run again</button></div>';
  }

  function paint() {
    var tabs = [['batches', 'Batches'], ['generate', 'Generate'], ['bench', 'Benchmark']];
    root.querySelectorAll('nav button').forEach(function (button, index) {
      button.setAttribute('aria-selected', tabs[index][0] === state.tab ? 'true' : 'false');
    });

    var message = state.message
      ? '<p class="note' + (state.failed ? ' bad' : '') + '">' + esc(state.message) + '</p>'
      : '';

    body.innerHTML = busy
      ? '<p class="empty">Working&hellip;</p>'
      : (state.tab === 'batches' ? batches() : state.tab === 'generate' ? generate() : bench()) + message;
  }

  function absorb(answer) {
    state.failed = !answer.ok;
    state.message = answer.ok ? state.message : answer.why;
    if (answer.batches) { state.batches = answer.batches; }
    if (answer.bench) { state.bench = answer.bench; }
    paint();
  }

  root.querySelector('#evofx-pill').addEventListener('click', function () {
    panel.hidden = !panel.hidden;
    if (!panel.hidden) { ask('state', {}).then(absorb); }
  });

  root.querySelector('header button').addEventListener('click', function () { panel.hidden = true; });

  root.querySelectorAll('nav button').forEach(function (button) {
    button.addEventListener('click', function () {
      state.tab = button.dataset.tab;
      state.message = null;
      paint();
    });
  });

  body.addEventListener('click', function (event) {
    var drop = event.target.closest('[data-drop]');
    if (drop) {
      if (!window.confirm('Remove what these batches wrote?')) { return; }
      ask('drop', { batch: drop.dataset.drop }).then(function (answer) {
        state.message = answer.ok ? answer.removed + ' rows removed.' : null;
        absorb(answer);
      });
      return;
    }

    if (event.target.closest('[data-bench]')) {
      ask('bench', {}).then(function (answer) {
        state.message = null;
        absorb(answer);
      });
    }
  });

  body.addEventListener('submit', function (event) {
    event.preventDefault();
    var payload = {};
    new FormData(event.target).forEach(function (value, key) { payload[key] = value; });
    ask('make', payload).then(function (answer) {
      state.message = answer.ok ? 'Batch ' + answer.batch + ' written, ' + answer.rows + ' rows.' : null;
      if (answer.ok) { state.tab = 'batches'; }
      absorb(answer);
    });
  });
})();
