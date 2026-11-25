<style>
#fm30 { --on:#d62828; --ok:#2a9d43; --warn:#e09f00; --mute:#777; max-width:1100px; }
#fm30 .tabs { display:flex; flex-wrap:wrap; gap:4px; margin:8px 0 12px; }
#fm30 .tabs button { border:1px solid #bbb; background:transparent; padding:6px 12px; border-radius:6px; cursor:pointer; color:inherit; }
#fm30 .tabs button.active { background:#1d4e89; color:#fff; border-color:#1d4e89; }
#fm30 .tab { display:none; }
#fm30 .tab.active { display:block; }
#fm30 .card { border:1px solid rgba(128,128,128,.35); border-radius:8px; padding:12px; margin-bottom:12px; }
#fm30 .card h3 { font-size:1.05rem; margin:0 0 8px; }
#fm30 .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:8px; }
#fm30 .kv { font-size:.85rem; color:var(--mute); }
#fm30 .kv b { display:block; font-size:1.05rem; color:inherit; }
#fm30 .row { display:flex; flex-wrap:wrap; align-items:center; gap:6px; margin:6px 0; }
#fm30 .row label { min-width:150px; }
#fm30 .row input[type=text], #fm30 .row input[type=number], #fm30 .row select { padding:3px 6px; }
#fm30 .help { font-size:.8rem; color:var(--mute); margin:0 0 6px 156px; }
#fm30 #txbtn { font-size:1.3rem; font-weight:bold; padding:10px 22px; border-radius:10px; border:none; color:#fff; background:var(--mute); cursor:pointer; }
#fm30 #txbtn.on { background:var(--on); }
#fm30 #txbtn.fault { background:var(--warn); }
#fm30 .mono { font-family:Consolas,monospace; }
#fm30 .page { display:inline-block; font-family:Consolas,monospace; border:1px solid #999; border-radius:4px; padding:1px 6px; margin:2px; white-space:pre; }
#fm30 .page.now { border-color:var(--on); box-shadow:0 0 0 1px var(--on); }
#fm30 .cnt { font-size:.75rem; color:var(--mute); margin-left:2px; }
#fm30 .cnt.over { color:var(--on); font-weight:bold; }
#fm30 table { width:100%; border-collapse:collapse; font-size:.88rem; }
#fm30 th, #fm30 td { text-align:left; padding:4px 6px; border-bottom:1px solid rgba(128,128,128,.25); vertical-align:top; }
#fm30 tr.sel { background:rgba(29,78,137,.15); }
#fm30 tr.piece { cursor:pointer; }
#fm30 .badge { font-size:.72rem; padding:1px 6px; border-radius:8px; background:#ddd; color:#333; }
#fm30 .badge.show { background:#cfe8d5; }
#fm30 .badge.inter { background:#f1e3c6; }
#fm30 .badge.need { background:#f6c8c8; }
#fm30 #msg { position:fixed; right:16px; bottom:16px; padding:8px 14px; border-radius:6px; color:#fff; display:none; z-index:1000; max-width:60vw; }
#fm30 pre { max-height:340px; overflow:auto; font-size:.8rem; background:rgba(128,128,128,.12); padding:8px; border-radius:6px; }
#fm30 .lock { color:var(--warn); }
</style>

<div id="fm30">
  <div class="row" style="justify-content:space-between">
    <h2 style="margin:0">FM30 Transmitter</h2>
    <div class="row">
      <button id="txbtn" onclick="FM.txToggle()">TX ?</button>
      <button class="buttons" onclick="FM.save()" title="Write the STM32's current settings to its flash">Save to FM30</button>
    </div>
  </div>
  <div id="linkline" class="kv"></div>

  <div class="tabs">
    <button data-t="status" class="active">Status</button>
    <button data-t="show">Show &amp; RDS</button>
    <button data-t="rds">RDS fields</button>
    <button data-t="display">Display</button>
    <button data-t="adv">Advanced</button>
    <button data-t="settings">Settings</button>
  </div>

  <!-- ============================== STATUS -->
  <div class="tab active" id="t-status">
    <div class="card"><h3>Now</h3>
      <div class="grid" id="nowgrid"></div>
      <div style="margin-top:8px"><span class="kv">PS pages:</span> <span id="pspages"></span></div>
      <div><span class="kv">RT pages:</span> <span id="rtpages"></span></div>
    </div>
    <div class="card"><h3>Transmitter</h3>
      <div class="grid" id="txgrid"></div>
    </div>
    <div class="card"><h3>Controls</h3>
      <div class="row"><label>Frequency (MHz) <span class="lock">&#128274;</span></label>
        <input type="number" id="c-freq" step="0.1" min="87.5" max="108" style="width:90px">
        <button class="buttons" onclick="FM.cmd('FREQ '+val('c-freq'))">Set</button></div>
      <div class="row"><label>Power % <span class="lock">&#128274;</span></label>
        <input type="range" id="c-pow" min="0" max="100" oninput="txt('c-powv',this.value+'%')"><span id="c-powv" class="mono"></span>
        <button class="buttons" onclick="FM.cmd('POWER '+val('c-pow')+'%')">Set</button></div>
      <div class="help">Percent of the STM32's maximum. Test at minimum power.</div>
      <div class="row"><label>Volume (0-255)</label>
        <input type="range" id="c-vol" min="0" max="255" oninput="txt('c-volv',this.value)"><span id="c-volv" class="mono"></span>
        <button class="buttons" onclick="FM.cmd('VOL '+val('c-vol'))">Set</button></div>
      <div class="row"><label>Balance (-127..127)</label>
        <input type="range" id="c-bal" min="-127" max="127" oninput="txt('c-balv',this.value)"><span id="c-balv" class="mono"></span>
        <button class="buttons" onclick="FM.cmd('BAL '+val('c-bal'))">Set</button></div>
      <div class="help">&#128274; = asks for confirmation (changes what goes on air). Changes are live; use <b>Save to FM30</b> to keep them after the FM30 restarts.</div>
    </div>
  </div>

  <!-- ============================== SHOW & RDS -->
  <div class="tab" id="t-show">
    <div class="card"><h3>Pieces (all playlists on the show master)</h3>
      <div class="help" style="margin-left:0">Names belong to the media file, so a piece used in several playlists has one set of names.
        <b>Show piece</b> = counted in x/N. <b>Interstitial</b> = not counted (Station ID, intros). Lead-in/lead-out are never counted.
        Empty fields fall back to the song's tags, then the file name. Click a piece to edit its names and RDS text.</div>
      <div class="row"><button class="buttons" onclick="FM.loadPieces()">Reload</button><span id="piecehost" class="kv"></span></div>
      <table><thead><tr><th>Piece</th><th>Type</th><th>Title</th><th>Artist</th><th>In playlists</th></tr></thead>
        <tbody id="piecerows"></tbody></table>
    </div>
    <div class="card" id="editor" style="display:none"><h3>Edit: <span id="e-key" class="mono"></span></h3>
      <div class="row"><label>Type</label><select id="e-type"><option value="">auto</option><option value="show">Show piece (counted)</option><option value="interstitial">Interstitial (not counted)</option></select><span id="e-typeauto" class="kv"></span></div>
      <div class="row"><label>Title</label><input type="text" id="e-title" size="40"><span class="kv" id="e-ttag"></span></div>
      <div class="row"><label>Artist</label><input type="text" id="e-artist" size="40"><span class="kv" id="e-atag"></span></div>
      <div class="row"><label>Short name (PS)</label><input type="text" id="e-short" size="24"></div>
      <div class="help">What the 8-character PS display shows; long names are split into word pages. Defaults to the title.</div>
      <div class="row"><label>Extra text (RT)</label><input type="text" id="e-extra" size="50"></div>
      <div class="help">Used by <span class="mono">{extra}</span> in the RT template, e.g. a greeting.</div>
      <div class="row"><label>PS template</label><input type="text" id="e-ps_template" size="30" placeholder="(global)"></div>
      <div class="row"><label>RT template</label><input type="text" id="e-rt_template" size="50" placeholder="(global)"></div>
      <div class="help">Tokens: {count} {n} {N} {title} {artist} {short} {extra} {station} {next}. Leave empty to use the Settings templates.</div>
      <div class="row"><label>PS page time (s)</label><input type="number" id="e-ps_s" step="0.5" min="1" max="30" style="width:80px" placeholder="global">
        <label style="min-width:0">RT page time (s)</label><input type="number" id="e-rt_s" step="1" min="4" max="120" style="width:80px" placeholder="global"></div>
      <h4 style="margin:12px 0 4px">RDS text</h4>
      <div class="row"><label>Custom text</label><input type="checkbox" id="e-custom" onchange="FM.customToggle()">
        <span class="kv">off = built from the names and templates above (shown greyed). On = these lines are sent exactly as written.</span></div>
      <div class="row" style="align-items:flex-start"><label>PS lines<br><span class="kv">8 chars, one per line</span></label>
        <textarea id="e-ps_lines" rows="6" cols="14" class="mono" spellcheck="false"></textarea>
        <label style="min-width:0">RT lines<br><span class="kv">64 chars, one per line</span></label>
        <textarea id="e-rt_lines" rows="6" cols="70" class="mono" spellcheck="false"></textarea></div>
      <div class="help">Each line rotates in turn while this piece plays; add your own lines anywhere (e.g. <span class="mono">MERRY</span> / <span class="mono">XMAS</span>, or
        <span class="mono">{count} Merry Christmas from our house</span>). Longer lines are split automatically.
        Tokens stay live: <span class="mono">{count}</span> (x/N, repeated on every page when it starts the line), <span class="mono">{station}</span>, <span class="mono">{title}</span>, <span class="mono">{artist}</span>, <span class="mono">{next}</span>...
        <button class="buttons" onclick="FM.refill()">Refill from names</button></div>
      <div class="row"><label>Preview in playlist</label><select id="e-pl"></select></div>
      <div><span class="kv">PS:</span> <span id="pv-ps"></span></div>
      <div><span class="kv">RT:</span> <span id="pv-rt"></span></div>
      <div class="row" style="margin-top:8px"><button class="buttons" onclick="FM.savePiece()">Save names</button>
        <button class="buttons" onclick="FM.clearPiece()">Clear (use tags)</button></div>
    </div>
    <div class="card"><h3>RDS automation</h3>
      <div id="rdsset"></div>
      <button class="buttons" onclick="FM.saveSettings('rdsset')">Save RDS settings</button>
    </div>
  </div>

  <!-- ============================== RDS FIELDS -->
  <div class="tab" id="t-rds">
    <div class="card"><h3>Manual PS / RT</h3>
      <div class="help" style="margin-left:0" id="manualnote"></div>
      <div class="row"><label>PS (8)</label><input type="text" id="m-ps" maxlength="8" size="10" oninput="cnt(this,8)"><span class="cnt"></span>
        <button class="buttons" onclick="FM.cmd('PS '+val('m-ps'))">Set</button></div>
      <div class="row"><label>RT (64)</label><input type="text" id="m-rt" maxlength="64" size="60" oninput="cnt(this,64)"><span class="cnt"></span>
        <button class="buttons" onclick="FM.cmd('RT '+val('m-rt'))">Set</button></div>
    </div>
    <div class="card"><h3>RDS</h3>
      <div class="row"><label>RDS</label><button class="buttons" onclick="FM.cmd('RDS ON')">On</button><button class="buttons" onclick="FM.cmd('RDS OFF')">Off (mute)</button></div>
      <div class="row"><label>PI (hex)</label><input type="text" id="r-pi" size="6"><button class="buttons" onclick="FM.cmd('PI '+val('r-pi'))">Set</button></div>
      <div class="help">Station identifier. US stations derive it from call letters; any value works for a light show.</div>
      <div class="row"><label>PTY (RBDS)</label><select id="r-pty"></select><button class="buttons" onclick="FM.cmd('PTY '+val('r-pty'))">Set</button></div>
      <div class="row"><label>TP / TA / MS / STEREO</label>
        <span id="r-flags"></span></div>
      <div class="help">TP = traffic programme, TA = traffic announcement now (leave off), MS = music (on), STEREO = stereo flag.</div>
    </div>
  </div>

  <!-- ============================== DISPLAY -->
  <div class="tab" id="t-display">
    <div class="card"><h3>LCD messages</h3>
      <div class="help" style="margin-left:0">Slot 1 (page 5) shows the show state when enabled in Settings. Slots 2-4 (pages 6-8) are yours; they are re-sent if the FM30 restarts. Top 8 chars, bottom 48 (scrolls).</div>
      <div id="lcdslots"></div>
    </div>
    <div class="card"><h3>LCD</h3>
      <div class="row"><label>Backlight/display</label><button class="buttons" onclick="FM.cmd('LCD ON')">On</button><button class="buttons" onclick="FM.cmd('LCD OFF')">Off</button></div>
      <div class="row"><label>Pin page (0 = rotate)</label><input type="number" id="l-page" min="0" max="8" style="width:60px"><button class="buttons" onclick="FM.cmd('LCD PAGE '+val('l-page'))">Set</button></div>
      <div class="row"><label>Rotation pages</label><input type="text" id="l-rot" size="20" placeholder="e.g. 1 5 6 or ALL"><button class="buttons" onclick="FM.cmd('LCD ROTATE '+val('l-rot'))">Set</button></div>
    </div>
  </div>

  <!-- ============================== ADVANCED -->
  <div class="tab" id="t-adv">
    <div class="card"><h3>Tuning internals</h3>
      <div class="row"><label>U6TRIM (-1000..1000) <span class="lock">&#128274;</span></label><input type="number" id="a-u6t" style="width:90px"><button class="buttons" onclick="FM.cmd('U6TRIM '+val('a-u6t'))">Set</button></div>
      <div class="row"><label>U6 (0-4095|AUTO) <span class="lock">&#128274;</span></label><input type="text" id="a-u6" size="6"><button class="buttons" onclick="FM.cmd('U6 '+val('a-u6'))">Set</button></div>
      <div class="help">Deviation compensation. Leave on AUTO unless calibrating.</div>
      <div class="row"><label>VTSCALE <span class="lock">&#128274;</span></label><input type="text" id="a-vts" size="6"><button class="buttons" onclick="FM.cmd('VTSCALE '+val('a-vts'))">Set</button></div>
      <div class="help">Leave at 3.0 (the Vtune divider is fitted).</div>
      <div class="row"><label>RDSLEVEL (0-2047) <span class="lock">&#128274;</span></label><input type="number" id="a-rl" style="width:90px"><button class="buttons" onclick="FM.cmd('RDSLEVEL '+val('a-rl'))">Set</button></div>
      <div class="row"><label>RDSPHASE (-180..180)</label><input type="number" id="a-rp" style="width:90px"><button class="buttons" onclick="FM.cmd('RDSPHASE '+val('a-rp'))">Set</button></div>
      <div class="row"><label>RDSFREE</label><button class="buttons" onclick="FM.cmd('RDSFREE ON')">On</button><button class="buttons" onclick="FM.cmd('RDSFREE OFF')">Off</button></div>
      <div class="row"><label>DACTEST <span class="lock">&#128274;</span></label><select id="a-dt"><option value="0">0 normal</option><option value="1">1 DC 2048</option><option value="2">2 1 kHz sine</option></select><button class="buttons" onclick="FM.cmd('DACTEST '+val('a-dt'))">Set</button>
        <button class="buttons" onclick="FM.cmd('DACDBG')">DACDBG</button></div>
      <div class="row"><label>Defaults <span class="lock">&#128274;</span></label><button class="buttons" onclick="FM.cmd('DEFAULTS')">Load STM32 defaults</button></div>
    </div>
    <div class="card"><h3>Console</h3>
      <div class="row"><input type="text" id="con" size="60" placeholder="e.g. STATUS, HELP, FREQ 98.1" onkeydown="if(event.key==='Enter')FM.console()">
        <button class="buttons" onclick="FM.console()">Send</button><button class="buttons" onclick="FM.cmd('HELP')">HELP</button></div>
      <pre id="conout"></pre>
    </div>
    <div class="card"><h3>Raw status</h3><pre id="rawstatus"></pre></div>
  </div>

  <!-- ============================== SETTINGS -->
  <div class="tab" id="t-settings">
    <div class="card"><h3>Plugin</h3>
      <div id="genset"></div>
      <button class="buttons" onclick="FM.saveSettings('genset')">Save settings</button>
      <div class="help" style="margin-left:0">Changing the serial port or API port needs a service restart:
        <span class="mono">sudo systemctl restart fpp-fm30</span></div>
    </div>
    <div class="card"><h3>Link</h3><div id="linkstats" class="mono"></div></div>
    <div class="card"><h3>Log</h3><button class="buttons" onclick="FM.loadLog()">Refresh</button><pre id="log"></pre></div>
  </div>

  <div id="msg"></div>
</div>

<script>
(function () {
  const API = 'plugin.php?plugin=fpp-fm30-controler&page=fm30api.php&nopage=1&p=';
  const PROTECTED = ['FREQ','POWER','U6TRIM','U6','VTSCALE','RDSLEVEL','DACTEST','DEFAULTS'];
  const PTY = ['None','News','Information','Sports','Talk','Rock','Classic Rock','Adult Hits','Soft Rock','Top 40',
    'Country','Oldies','Soft Music','Nostalgia','Jazz','Classical','R&B','Soft R&B','Language','Religious Music',
    'Religious Talk','Personality','Public','College','Spanish Talk','Spanish Music','Hip Hop','(27)','(28)','Weather',
    'Emergency Test','Emergency'];
  const RDS_SET = [
    ['rds_auto','bool','Drive PS/RT from the show','Off = set PS/RT by hand on the RDS fields tab.'],
    ['station_ps','text','Station PS tag','Shown between PS loops and when idle (8 chars), e.g. 105.5 FM.'],
    ['station_between','bool','Station tag between PS loops',''],
    ['ps_uppercase','bool','PS in capitals',''],
    ['ps_center','bool','Center short PS pages',''],
    ['ps_page_s','num','PS page time (s)','1-30. Radios need ~2 s to latch a PS page.'],
    ['rt_page_s','num','RT page time (s)','4-120. Each RT page is sent as new text (A/B flag flips).'],
    ['ps_template','text','PS template',''],
    ['rt_template_show','text','RT template: show pieces','Start with {count} to repeat the x/N counter on every page.'],
    ['rt_template_inter','text','RT template: interstitials',''],
    ['idle_ps','text','PS when no show',''],
    ['idle_rt','text','RT when no show','{next} = next scheduled show time.'],
  ];
  const GEN_SET = [
    ['port','text','Serial port','Pi GPIO UART: /dev/serial0 (GPIO14 TX pin 8, GPIO15 RX pin 10).'],
    ['master','text','Show master','auto = the MultiSync player, local = this FPP, or an IP address.'],
    ['tx_auto','bool','TX on/off with the show','TX ON when the show starts; TX OFF after it has stopped for the grace time.'],
    ['tx_off_grace_s','num','TX off grace (s)','Covers the gap between playlist loops.'],
    ['lcd_slot1','bool','LCD slot 1 shows show state',''],
    ['http_port','num','Service API port','Local only (127.0.0.1).'],
  ];
  let ST = null, SET = null, PIECES = null, SEL = null, pvTimer = null;

  window.val = id => document.getElementById(id).value.trim();
  window.txt = (id, t) => { document.getElementById(id).textContent = t; };
  window.cnt = (el, max) => { const c = el.nextElementSibling; c.textContent = el.value.length + '/' + max; c.className = 'cnt' + (el.value.length > max ? ' over' : ''); };
  const $ = id => document.getElementById(id);
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  function toast(t, ok) {
    const m = $('msg'); m.textContent = t; m.style.background = ok ? '#2a9d43' : '#d62828'; m.style.display = 'block';
    clearTimeout(m._t); m._t = setTimeout(() => { m.style.display = 'none'; }, ok ? 2500 : 6000);
  }
  async function call(p, method, body) {
    const r = await fetch(API + p, {method: method || 'GET', headers: {'Content-Type': 'application/json'},
      body: body === undefined ? undefined : JSON.stringify(body)});
    let d; try { d = await r.json(); } catch (e) { d = {ok: false, error: 'bad response (' + r.status + ')'}; }
    if (!r.ok && d.ok === undefined) d.ok = false;
    return d;
  }

  const FM = window.FM = {};
  FM.cmd = async function (text) {
    const w = text.trim().split(' ')[0].toUpperCase();
    if (PROTECTED.includes(w) && !confirm('Send "' + text + '"?\nThis changes what goes on air.')) return;
    const d = await call('cmd', 'POST', {cmd: text});
    if (d.ok) toast((d.reply || 'OK') + (d.sent && d.sent !== text ? '  (sent: ' + d.sent + ')' : ''), true);
    else toast(d.error || d.reply || 'failed', false);
    if (d.body) { $('conout').textContent = d.body; activate('adv'); }
    refresh();
    return d;
  };
  FM.console = async function () {
    const t = val('con'); if (!t) return;
    const d = await FM.cmd(t);
    if (d) $('conout').textContent = '> ' + (d.sent || t) + '\n' + (d.reply || d.error || '') + (d.body ? '\n' + d.body : '');
  };
  FM.txToggle = function () {
    const on = ST && ST.fm30 && ST.fm30.tx;
    if (on && !confirm('Turn the transmitter OFF?')) return;
    FM.cmd(on ? 'TX OFF' : 'TX ON');
  };
  FM.save = function () { if (confirm('Save the FM30\'s current settings to its flash?')) FM.cmd('SAVE'); };

  function kv(label, v) { return '<div class="kv">' + esc(label) + '<b>' + esc(v) + '</b></div>'; }
  function pages(list, now) { return (list || []).map(p => '<span class="page' + (p === now ? ' now' : '') + '">' + esc(p) + '</span><span class="cnt">' + p.length + '</span>').join(' '); }

  async function refresh() {
    const d = await call('status');
    if (d.ok === false && !d.link) { $('linkline').textContent = d.error || 'service not reachable'; return; }
    ST = d;
    const f = d.fm30 || {}, s = d.show || {}, r = d.rds || {};
    const b = $('txbtn');
    if (!d.link.connected) { b.className = 'fault'; b.textContent = 'NO LINK'; }
    else if (f.state === 'fault') { b.className = 'fault'; b.textContent = 'FAULT'; }
    else if (f.tx && (f.state === 'tuning' || f.state === 'ramp')) { b.className = 'fault'; b.textContent = 'TUNING...'; }
    else if (f.tx) { b.className = 'on'; b.textContent = '● ON AIR ' + f.freq; }
    else { b.className = ''; b.textContent = 'TX OFF'; }
    document.title = f.tx ? '● ON AIR ' + f.freq : 'FM30';
    $('linkline').textContent = (d.link.connected ? 'Link OK ' : 'Link DOWN ') + d.link.port +
      (d.fm30_age != null ? ' · status ' + d.fm30_age + ' s ago' : '') + ' · fm30d ' + d.version;
    const cnt = s.n ? s.n + '/' + s.N : (s.type === 'interstitial' ? 'interstitial' : (s.section === 'leadIn' ? 'lead-in' : '-'));
    $('nowgrid').innerHTML = kv('Show', s.playing ? 'playing' : 'idle') + kv('Playlist', s.playlist || '-') +
      kv('Piece', s.key || '-') + kv('Position', cnt) + kv('Next show', s.next || '-') +
      kv('TX automation', SET && SET.tx_auto === false ? 'off' : d.tx_auto_state + (d.idle_off_in != null ? ' (off in ' + d.idle_off_in + ' s)' : '')) +
      (s.error ? kv('FPP', s.error) : '');
    $('pspages').innerHTML = r.auto ? pages(r.ps_pages, r.ps_now) + ' <span class="kv">' + (r.ps_s || '') + ' s each</span>' : '<span class="kv">automation off</span>';
    $('rtpages').innerHTML = r.auto ? pages(r.rt_pages, r.rt_now) + ' <span class="kv">' + (r.rt_s || '') + ' s each</span>' : '<span class="kv">automation off</span>';
    $('txgrid').innerHTML = kv('State', f.state || '-') + kv('Frequency', f.freq != null ? f.freq + ' MHz' : '-') +
      kv('Power', f.power != null ? f.power + ' / target ' + f.power_target : '-') +
      kv('Vtune', f.vtune != null ? f.vtune + ' V (exp ' + f.vtune_expected + ')' : '-') +
      kv('Pilot lock', f.pilot_lock ? 'yes' : 'no') + kv('RDS', f.rds ? 'on' : 'muted') +
      kv('PS on air', f.ps != null ? '[' + f.ps + ']' : '-') + kv('RT on air', f.rt || '-') +
      kv('Volume / balance', f.vol != null ? f.vol + ' / ' + f.bal : '-') +
      kv('Faults', (f.fault ? f.fault + ' ' : '') + '(' + (f.faults || 0) + ' total)') +
      kv('PI / PTY', f.pi != null ? f.pi + ' / ' + (PTY[f.pty] || f.pty) : '-') + kv('Uptime', f.uptime != null ? f.uptime + ' s' : '-');
    $('rawstatus').textContent = JSON.stringify(f, null, 1);
    $('linkstats').textContent = JSON.stringify(d.link, null, 1);
    $('manualnote').textContent = r.auto ? 'RDS automation is on, so manual PS/RT are refused. Turn it off under Show & RDS.' : 'RDS automation is off: these set PS/RT directly.';
    if (!refresh.seeded && f.freq != null) {        // fill the controls once
      refresh.seeded = true;
      $('c-freq').value = f.freq; $('c-vol').value = f.vol; txt('c-volv', f.vol); $('c-bal').value = f.bal; txt('c-balv', f.bal);
      $('c-pow').value = Math.round(100 * f.power_target / 3968); txt('c-powv', $('c-pow').value + '%');
      $('r-pi').value = f.pi; $('r-pty').value = f.pty; $('a-u6t').value = f.u6_trim; $('a-u6').value = 'AUTO';
      $('a-vts').value = f.vt_scale; $('a-rl').value = f.rds_level; $('a-rp').value = f.rds_phase;
    }
  }

  // ---------------------------------------------------------------- settings
  function settingRows(host, defs) {
    $(host).innerHTML = defs.map(([k, t, label, help]) => {
      const v = SET[k];
      const input = t === 'bool' ? '<input type="checkbox" data-k="' + k + '"' + (v ? ' checked' : '') + '>'
        : '<input type="' + (t === 'num' ? 'number' : 'text') + '" step="any" data-k="' + k + '" value="' + esc(v) + '" size="' + (t === 'num' ? 8 : 50) + '">';
      return '<div class="row"><label>' + esc(label) + '</label>' + input + '</div>' + (help ? '<div class="help">' + esc(help) + '</div>' : '');
    }).join('');
  }
  async function loadSettings() {
    const d = await call('settings');
    if (!d.settings) return;
    SET = d.settings;
    settingRows('rdsset', RDS_SET);
    settingRows('genset', GEN_SET);
    $('lcdslots').innerHTML = ['2', '3', '4'].map(n => {
      const m = (SET.lcd_msgs || {})[n] || ['', ''];
      return '<div class="row"><label>Slot ' + n + ' (page ' + (+n + 4) + ')</label>' +
        '<input type="text" id="lt' + n + '" size="9" maxlength="8" value="' + esc(m[0]) + '" placeholder="top" oninput="cnt(this,8)"><span class="cnt"></span>' +
        '<input type="text" id="lb' + n + '" size="40" maxlength="48" value="' + esc(m[1]) + '" placeholder="bottom" oninput="cnt(this,48)"><span class="cnt"></span>' +
        '<button class="buttons" onclick="FM.lcd(' + n + ')">Set</button><button class="buttons" onclick="FM.lcd(' + n + ',1)">Clear</button></div>';
    }).join('');
  }
  FM.lcd = function (n, clear) {
    if (clear) { $('lt' + n).value = ''; $('lb' + n).value = ''; }
    const t = val('lt' + n).replace(/\|/g, ''), b = val('lb' + n).replace(/\|/g, '');
    FM.cmd('LCD MSG ' + n + (t || b ? ' ' + (t ? t + '|' : '') + b : '')).then(loadSettings);
  };
  FM.saveSettings = async function (host) {
    const out = {};
    $(host).querySelectorAll('[data-k]').forEach(el => {
      out[el.dataset.k] = el.type === 'checkbox' ? el.checked : (el.type === 'number' ? Number(el.value) : el.value);
    });
    const d = await call('settings', 'PUT', out);
    if (d.ok) { toast(d.restart_needed ? 'Saved - restart the service for port changes' : 'Saved', true); SET = d.settings; }
    else toast(d.error || 'failed', false);
  };

  // ---------------------------------------------------------------- pieces
  FM.loadPieces = async function () {
    const d = await call('pieces');
    if (!d.pieces) { toast(d.error || 'cannot load pieces', false); return; }
    PIECES = d;
    $('piecehost').textContent = 'from ' + d.host;
    $('piecerows').innerHTML = d.pieces.map((p, i) => {
      const typ = p.type === 'show' ? '<span class="badge show">show</span>' : '<span class="badge inter">interstitial</span>';
      const need = (p.effective.needs_name ? ' <span class="badge need">needs name</span>' : '') +
        (p.names.ps_lines || p.names.rt_lines ? ' <span class="badge show">custom RDS</span>' : '');
      return '<tr class="piece' + (SEL === p.key ? ' sel' : '') + '" onclick="FM.edit(' + i + ')"><td class="mono">' + esc(p.key) + need +
        '</td><td>' + typ + (p.type_auto ? ' <span class="kv">auto</span>' : '') + '</td><td>' + esc(p.effective.title) +
        '</td><td>' + esc(p.effective.artist) + '</td><td>' + esc(p.playlists.join(', ')) + '</td></tr>';
    }).join('') || '<tr><td colspan="5">No playlists found on the show master.</td></tr>';
  };
  const FIELDS = ['type', 'title', 'artist', 'short', 'extra', 'ps_template', 'rt_template', 'ps_s', 'rt_s'];
  const LINES = ['ps_lines', 'rt_lines'];
  let AUTO = {ps: [], rt: []};
  function linesOf(id) { return $(id).value.split('\n').map(l => l.trim()).filter(l => l); }
  function setCustom(on) {
    $('e-custom').checked = on;
    LINES.forEach(k => { $('e-' + k).readOnly = !on; $('e-' + k).style.opacity = on ? 1 : 0.55; });
  }
  function showAuto() {
    if ($('e-custom').checked) return;
    $('e-ps_lines').value = AUTO.ps.join('\n');
    $('e-rt_lines').value = AUTO.rt.join('\n');
  }
  FM.customToggle = function () {
    const on = $('e-custom').checked;
    setCustom(on);
    if (on && !linesOf('e-ps_lines').length && !linesOf('e-rt_lines').length) FM.refill(); else showAuto();
    FM.preview();
  };
  FM.refill = function () {
    $('e-ps_lines').value = AUTO.ps.join('\n');
    $('e-rt_lines').value = AUTO.rt.join('\n');
    FM.preview();
  };
  FM.edit = function (i) {
    const p = PIECES.pieces[i];
    SEL = p.key;
    $('editor').style.display = 'block';
    txt('e-key', p.key);
    FIELDS.forEach(k => { $('e-' + k).value = p.names[k] != null ? p.names[k] : ''; });
    const custom = !!(p.names.ps_lines || p.names.rt_lines);
    AUTO = {ps: [], rt: []};
    LINES.forEach(k => { $('e-' + k).value = (p.names[k] || []).join('\n'); });
    setCustom(custom);
    txt('e-ttag', p.tags.title ? 'tag: ' + p.tags.title : 'no tag');
    txt('e-atag', p.tags.artist ? 'tag: ' + p.tags.artist : '');
    txt('e-typeauto', p.type_auto ? 'auto = ' + p.type : '');
    $('e-pl').innerHTML = p.playlists.map(n => '<option>' + esc(n) + '</option>').join('');
    document.querySelectorAll('#piecerows tr').forEach((tr, j) => tr.classList.toggle('sel', j === i));
    FM.preview();
    $('editor').scrollIntoView({behavior: 'smooth', block: 'nearest'});
  };
  function editorNames() {
    const o = {};
    FIELDS.forEach(k => { const v = val('e-' + k); if (v !== '') o[k] = (k === 'ps_s' || k === 'rt_s') ? Number(v) : v; });
    if ($('e-custom').checked) LINES.forEach(k => { const l = linesOf('e-' + k); if (l.length) o[k] = l; });
    return o;
  }
  FM.preview = async function () {
    if (!SEL) return;
    const d = await call('preview', 'POST', {key: SEL, playlist: val('e-pl'), names: editorNames()});
    if (!d.ps) return;
    AUTO = {ps: d.auto_ps || [], rt: d.auto_rt || []};
    showAuto();
    $('pv-ps').innerHTML = pages(d.ps) + ' <span class="kv">' + d.ps_s + ' s each' + (d.n ? ' · piece ' + d.n + '/' + d.N : ' · not counted') + '</span>';
    $('pv-rt').innerHTML = d.rt.map(p => '<div><span class="page">' + esc(p) + '</span><span class="cnt' + (p.length > 64 ? ' over' : '') + '">' + p.length + '/64</span></div>').join('') +
      '<span class="kv">' + d.rt.length + ' page' + (d.rt.length === 1 ? '' : 's') + ', ' + d.rt_s + ' s each</span>';
  };
  FM.savePiece = async function () {
    const d = await call('piece', 'PUT', {key: SEL, names: editorNames()});
    if (d.ok) { toast('Saved names for ' + SEL, true); FM.loadPieces(); } else toast(d.error || 'failed', false);
  };
  FM.clearPiece = async function () {
    if (!confirm('Clear all custom names for ' + SEL + '?')) return;
    FIELDS.forEach(k => { $('e-' + k).value = ''; });
    LINES.forEach(k => { $('e-' + k).value = ''; });
    setCustom(false);
    await FM.savePiece(); FM.preview();
  };
  FM.loadLog = async function () { const d = await call('log'); $('log').textContent = (d.log || []).join('\n'); };

  // ---------------------------------------------------------------- tabs / init
  function activate(t) {
    document.querySelectorAll('#fm30 .tabs button').forEach(b => b.classList.toggle('active', b.dataset.t === t));
    document.querySelectorAll('#fm30 .tab').forEach(el => el.classList.toggle('active', el.id === 't-' + t));
    if (t === 'show' && !PIECES) FM.loadPieces();
    if (t === 'settings') FM.loadLog();
    try { localStorage.setItem('fm30tab', t); } catch (e) {}
  }
  document.querySelectorAll('#fm30 .tabs button').forEach(b => b.addEventListener('click', () => activate(b.dataset.t)));
  $('r-pty').innerHTML = PTY.map((n, i) => '<option value="' + i + '">' + i + ' ' + esc(n) + '</option>').join('');
  $('r-flags').innerHTML = ['TP', 'TA', 'MS', 'STEREO'].map(f => '<span style="margin-right:10px">' + f +
    ' <button class="buttons" onclick="FM.cmd(\'' + f + ' 1\')">1</button><button class="buttons" onclick="FM.cmd(\'' + f + ' 0\')">0</button></span>').join('');
  document.querySelectorAll('#editor input, #editor select, #editor textarea').forEach(el => el.addEventListener('input', () => {
    clearTimeout(pvTimer); pvTimer = setTimeout(FM.preview, 300);
  }));
  try { const t = localStorage.getItem('fm30tab'); if (t) activate(t); } catch (e) {}
  loadSettings().then(refresh);
  setInterval(refresh, 2000);
})();
</script>
