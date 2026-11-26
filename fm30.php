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
#fm30 .badge.ovm { background:#e74c3c; color:#fff; font-weight:bold; }
#fm30 .badge.clean { background:#cfe8d5; }
#fm30 .badge.recheck { background:#f1e3c6; }
/* generic radio LCD */
#fm30 .radio { background:#20242a; border-radius:14px; padding:10px; max-width:560px; box-shadow:0 2px 6px rgba(0,0,0,.35); }
#fm30 .radio .lcd { background:linear-gradient(#b5cf9f,#98b585); border-radius:6px; padding:8px 12px 10px; color:#1c2a14;
  font-family:Consolas,"Courier New",monospace; box-shadow:inset 0 0 0 2px #6f8a5f, inset 0 3px 8px rgba(0,0,0,.25); }
#fm30 .radio .top { display:flex; justify-content:space-between; align-items:center; font-size:.78rem; font-weight:bold; letter-spacing:.06em; }
#fm30 .radio .dot { display:inline-block; width:8px; height:8px; border-radius:50%; background:rgba(28,42,20,.25); margin-right:5px; }
#fm30 .radio .dot.on { background:#a01818; }
#fm30 .radio .zone { position:relative; border-radius:5px; padding:3px 4px 3px 24px; margin-top:6px; outline:2px dashed transparent; transition:outline-color .15s, background .15s; }
#fm30 .radio .zone.hl { outline-color:#1d4e89; background:rgba(29,78,137,.12); }
#fm30 .radio .zone > .mk { position:absolute; left:2px; top:50%; transform:translateY(-50%); }
#fm30 .radio .ps { display:flex; gap:3px; }
#fm30 .radio .ps .c { width:1.35em; height:1.9em; line-height:1.9em; text-align:center; font-size:1.45rem; font-weight:bold;
  background:rgba(28,42,20,.07); border-radius:2px; }
#fm30 .radio .rt { font-size:.95rem; min-height:2.6em; line-height:1.3em; white-space:pre-wrap; word-break:break-word; transition:opacity .25s; }
#fm30 .radio .rt.blank { opacity:0; }
#fm30 .radio .lbl { font-size:.62rem; letter-spacing:.1em; opacity:.6; }
#fm30 .mk { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:50%;
  background:#1d4e89; color:#fff; font-size:.7rem; font-weight:bold; font-family:sans-serif; flex:none; }
#fm30 .arrow { color:#1d4e89; font-weight:bold; font-size:.8rem; margin-left:6px; white-space:nowrap; }
#fm30 .field.hl textarea.lines, #fm30 textarea.lines:focus { border-color:#1d4e89; box-shadow:0 0 0 2px rgba(29,78,137,.3); outline:none; }
/* switches */
#fm30 .sw { position:relative; display:inline-block; width:42px; height:22px; flex:none; }
#fm30 .sw input { opacity:0; width:0; height:0; }
#fm30 .sw span { position:absolute; inset:0; background:#9aa3ab; border-radius:22px; cursor:pointer; transition:.2s; }
#fm30 .sw span:before { content:""; position:absolute; width:16px; height:16px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; }
#fm30 .sw input:checked + span { background:#1d4e89; }
#fm30 .sw input:checked + span:before { transform:translateX(20px); }
#fm30 .opt { display:flex; gap:12px; align-items:flex-start; padding:10px 0; border-bottom:1px solid rgba(128,128,128,.2); }
#fm30 .opt:last-child { border-bottom:none; }
#fm30 .opt .t { font-weight:600; }
#fm30 .opt .d { font-size:.82rem; color:var(--mute); margin-top:2px; }
#fm30 .opt .ctl { margin-left:auto; display:flex; gap:6px; align-items:center; flex-wrap:nowrap; }
#fm30 .warn { color:#b85c00; font-weight:600; }
#fm30 .sect { margin:16px 0 6px; font-size:.95rem; font-weight:700; border-bottom:2px solid rgba(29,78,137,.35); padding-bottom:3px; }
#fm30 .ed2 { display:grid; grid-template-columns:minmax(300px,400px) 1fr; gap:16px; }
@media (max-width:760px) { #fm30 .ed2 { grid-template-columns:1fr; } }
#fm30 .field .t { font-weight:600; font-size:.9rem; }
#fm30 .field .d { font-size:.78rem; color:var(--mute); margin-bottom:4px; }
#fm30 textarea.lines { width:100%; box-sizing:border-box; font-family:Consolas,monospace; font-size:.95rem; line-height:1.45; padding:6px 8px; border-radius:6px; border:1px solid #aab; resize:vertical; }
#fm30 textarea.lines[readonly] { background:rgba(128,128,128,.12); color:var(--mute); }
#fm30 .lc { font-size:.75rem; font-family:Consolas,monospace; margin-top:3px; }
#fm30 .lc div { display:flex; gap:8px; }
#fm30 .lc .n { color:var(--mute); width:1.6em; text-align:right; }
#fm30 .lc .ok { color:var(--ok); }
#fm30 .lc .long { color:#b85c00; }
#fm30 .tok { font-size:.75rem; padding:1px 8px; border-radius:10px; border:1px solid #8aa; background:transparent; color:inherit; cursor:pointer; margin:2px; }
#fm30 .tok:hover { background:rgba(29,78,137,.15); }
#fm30 details.adv { margin-top:10px; }
#fm30 details.adv summary { cursor:pointer; color:#1d4e89; font-weight:600; }
#fm30 .callout { border-left:4px solid #1d4e89; background:rgba(29,78,137,.07); padding:8px 12px; border-radius:4px; font-size:.85rem; margin:6px 0 10px; }
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
    <button data-t="rds">Station &amp; RDS</button>
    <button data-t="display">Display</button>
    <button data-t="adv">Advanced</button>
    <button data-t="settings">Settings</button>
  </div>

  <!-- ============================== STATUS -->
  <div class="tab active" id="t-status">
    <div class="card"><h3>Now</h3>
      <div class="row" style="align-items:flex-start;gap:16px">
        <div style="flex:1 1 300px;min-width:260px"><div class="grid" id="nowgrid"></div></div>
        <div style="flex:1 1 320px"><div class="kv" style="margin-bottom:4px">On the radio right now</div><div id="liveradio"></div></div>
      </div>
      <div style="margin-top:8px"><span class="kv">Station display screens:</span> <span id="pspages"></span></div>
      <div><span class="kv">Now-playing screens:</span> <span id="rtpages"></span></div>
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
      <div class="help" style="margin-left:0"><span class="badge ovm">overmod 12 @ vol 128</span> = the last play overmodulated (12 times, at volume 128): lower its volume. Hover a tag for how often per minute and where in the song.
        <span class="badge clean">clean</span> = no overmodulation on its last play. <span class="badge recheck">recheck</span> = its volume changed since; the tag updates when it next plays.</div>
      <table><thead><tr><th>Piece</th><th>Type</th><th>Title</th><th>Artist</th><th>In playlists</th></tr></thead>
        <tbody id="piecerows"></tbody></table>
    </div>
    <div class="card" id="editor" style="display:none"><h3>Edit: <span id="e-key" class="mono"></span></h3>
      <div class="sect">Names</div>
      <div class="row"><label>Title</label><input type="text" id="e-title" size="40"><span class="mk" title="Shows in the now-playing text (2)">2</span><span class="kv" id="e-ttag"></span></div>
      <div class="row"><label>Artist</label><input type="text" id="e-artist" size="40"><span class="mk" title="Shows in the now-playing text (2)">2</span><span class="kv" id="e-atag"></span></div>
      <div class="row"><label>Greeting / extra</label><input type="text" id="e-extra" size="40" placeholder="e.g. Merry Christmas!"><span class="mk" title="Shows in the now-playing text (2)">2</span></div>
      <div class="help">Added after the artist when it fits. Empty title/artist use the song's tags.</div>
      <div class="row"><label>Short name</label><input type="text" id="e-short" size="24"><span class="mk" title="Shows in the station display (1)">1</span><span class="kv" id="e-shortnote"></span></div>
      <div class="row"><label>Counts in the show?</label><select id="e-type"><option value="">automatic</option><option value="show">Yes: a song (x/N)</option><option value="interstitial">No: Station ID / intro</option></select><span id="e-typeauto" class="kv"></span></div>

      <div class="sect">Volume</div>
      <div class="row"><label>Volume</label><input type="range" id="e-volr" min="0" max="255" style="width:220px" oninput="$('e-vol').value=this.value;$('e-vol').dispatchEvent(new Event('input'))">
        <input type="number" id="e-vol" min="0" max="255" step="1" style="width:70px" placeholder="default">
        <button class="buttons" onclick="FM.volTry()">Try now</button><span id="e-volnote" class="kv"></span></div>
      <div class="help">Transmitter volume (0-255) while this piece plays. Empty = the default volume. <b>Try now</b> sends it straight away.
        Overmodulation: <b id="e-ovm">-</b>. The tag in the list updates after every play.</div>

      <div class="sect">On-air text</div>
      <div class="ed2">
        <div><div class="kv" style="margin-bottom:4px">Live preview: how a radio shows it <span id="pv-meta"></span></div><div id="pvradio"></div>
          <div class="help" style="margin:6px 0 0">Updates as you type. Click into a box (or hover a part of the display) to see which part it controls.</div></div>
        <div>
          <div class="opt" style="padding-top:0"><div><div class="t">Write my own text for this piece</div>
            <div class="d">Off = made from the names above (shown greyed). On = the lines below go on air exactly as written, in order.</div></div>
            <div class="ctl"><label class="sw"><input type="checkbox" id="e-custom" onchange="FM.customToggle()"><span></span></label></div></div>
          <div class="field" data-z="ps"><div class="t"><span class="mk">1</span> Station display <span class="kv">(PS) &middot; 8 characters per screen</span><span class="arrow">&#9664; shows at <span class="mk">1</span> on the display</span></div>
            <div class="d" id="e-psnote">One screen per line. Longer lines are split into several screens.</div>
            <textarea id="e-ps_lines" class="lines" rows="4" spellcheck="false"></textarea><div class="lc" id="lc-ps"></div></div>
          <div class="field" data-z="rt" style="margin-top:10px"><div class="t"><span class="mk">2</span> Now-playing text <span class="kv">(RadioText) &middot; up to 64 characters per screen</span><span class="arrow">&#9664; shows at <span class="mk">2</span> on the display</span></div>
            <div class="d" id="e-rtnote">One screen per line; they take turns.</div>
            <textarea id="e-rt_lines" class="lines" rows="4" spellcheck="false"></textarea><div class="lc" id="lc-rt"></div></div>
          <div style="margin-top:6px"><span class="kv">Insert:</span>
            <button class="tok" onclick="FM.tok('{count}')" title="The show counter, e.g. 2/4. Stays correct when shows change.">x/N counter</button>
            <button class="tok" onclick="FM.tok('{title}')">Title</button><button class="tok" onclick="FM.tok('{artist}')">Artist</button>
            <button class="tok" onclick="FM.tok('{station}')">Station name</button><button class="tok" onclick="FM.tok('{next}')">Next show time</button>
            <button class="buttons" onclick="FM.refill()" style="margin-left:8px">Start over from names</button></div>
        </div>
      </div>
      <div class="row"><label>Preview as in playlist</label><select id="e-pl"></select></div>
      <div><span class="kv">Station display screens:</span> <span id="pv-ps"></span></div>
      <div><span class="kv">Now-playing screens:</span> <span id="pv-rt"></span></div>

      <details class="adv"><summary>Advanced: patterns and timing for this piece</summary>
        <div class="row"><label>Station display pattern</label><input type="text" id="e-ps_template" size="30" placeholder="(from settings)"></div>
        <div class="row"><label>Now-playing pattern</label><input type="text" id="e-rt_template" size="50" placeholder="(from settings)"></div>
        <div class="help">Patterns use {count} {n} {N} {title} {artist} {short} {extra} {station} {next}. Leave empty to use the ones in "How it works".</div>
        <div class="row"><label>Seconds per screen</label><input type="number" id="e-ps_s" step="0.5" min="1" max="30" style="width:80px" placeholder="default"> <span class="kv">station display</span>
          <input type="number" id="e-rt_s" step="1" min="4" max="120" style="width:80px" placeholder="default"> <span class="kv">now-playing</span></div>
      </details>
      <div class="row" style="margin-top:12px"><button class="buttons" onclick="FM.savePiece()">Save</button>
        <button class="buttons" onclick="FM.clearPiece()">Reset to tags</button></div>
    </div>
    <div class="card"><h3>On-air text: how it works</h3>
      <div id="rdsset"></div>
      <button class="buttons" onclick="FM.saveSettings('rdsset')">Save</button>
    </div>
  </div>

  <!-- ============================== STATION & RDS -->
  <div class="tab" id="t-rds">
    <div class="card"><h3>What listeners see</h3>
      <div class="callout">RDS is the text a radio shows next to the frequency. It has two parts:
        the <b>station display</b> (PS, 8 characters, meant to be your station name) and the
        <b>now-playing text</b> (RadioText, up to 64 characters, where song names go).</div>
      <div id="rdsradio"></div>
    </div>
    <div class="card"><h3>Station identity</h3>
      <div class="opt"><div><div class="t"><span class="mk">1</span> Station name</div>
          <div class="d">What radios show as your station (8 characters). With car-friendly text on, it stays on screen the whole show.</div></div>
        <div class="ctl"><input type="text" id="r-name" maxlength="8" size="10" class="mono" oninput="cnt(this,8);FM.namePreview()" onkeydown="if(event.key==='Enter')FM.setName()"><span class="cnt"></span>
          <button class="buttons" onclick="FM.setName()">Save</button></div></div>
      <div class="opt"><div><div class="t">Station code <span class="kv">(PI)</span></div>
          <div class="d">A 4-character code (0-9, A-F) radios use to recognise your station and keep it in presets. Pick one and leave it:
            changing it makes radios treat you as a different station. Real US stations derive it from their call letters; any code works for a low-power show.</div></div>
        <div class="ctl"><input type="text" id="r-pi" size="6" maxlength="4" class="mono" style="text-transform:uppercase"><button class="buttons" onclick="FM.setPI()">Save</button></div></div>
      <div class="opt"><div><div class="t">Program type <span class="kv">(PTY)</span></div>
          <div class="d">A genre label. Some radios show it (e.g. "Soft Music") and some let people search by it.</div></div>
        <div class="ctl"><select id="r-pty"></select><button class="buttons" onclick="FM.cmd('PTY '+val('r-pty'))">Save</button></div></div>
    </div>
    <div class="card"><h3>Signals to the radio</h3>
      <div class="opt"><div><div class="t">Send RDS text</div><div class="d">Off = radios show only the frequency.</div></div>
        <div class="ctl"><label class="sw"><input type="checkbox" id="f-RDS" onchange="FM.flag('RDS',this)"><span></span></label></div></div>
      <div class="opt"><div><div class="t">Music <span class="kv">(MS)</span></div><div class="d">On = music, off = talk. A few radios adjust their sound for it. Leave on.</div></div>
        <div class="ctl"><label class="sw"><input type="checkbox" id="f-MS" onchange="FM.flag('MS',this)"><span></span></label></div></div>
      <div class="opt"><div><div class="t">Stereo <span class="kv">(STEREO)</span></div><div class="d">Tells the radio the broadcast is in stereo. Leave on.</div></div>
        <div class="ctl"><label class="sw"><input type="checkbox" id="f-STEREO" onchange="FM.flag('STEREO',this)"><span></span></label></div></div>
      <div class="opt"><div><div class="t">Traffic station <span class="kv">(TP)</span></div><div class="d">Only for stations that broadcast traffic reports. Leave off.</div></div>
        <div class="ctl"><label class="sw"><input type="checkbox" id="f-TP" onchange="FM.flag('TP',this)"><span></span></label></div></div>
      <div class="opt"><div><div class="t">Traffic report now <span class="kv">(TA)</span></div><div class="d"><span class="warn">Leave off.</span>
          When on, many cars interrupt whatever they are playing (even other stations or phones) and turn the volume up.</div></div>
        <div class="ctl"><label class="sw"><input type="checkbox" id="f-TA" onchange="FM.flag('TA',this)"><span></span></label></div></div>
    </div>
    <div class="card"><h3>Type text by hand</h3>
      <div class="help" style="margin-left:0" id="manualnote"></div>
      <div class="opt"><div><div class="t">Station display</div><div class="d">8 characters.</div></div>
        <div class="ctl"><input type="text" id="m-ps" maxlength="8" size="10" class="mono" oninput="cnt(this,8)"><span class="cnt"></span>
          <button class="buttons" onclick="FM.cmd('PS '+val('m-ps'))">Send</button></div></div>
      <div class="opt"><div><div class="t">Now-playing text</div><div class="d">Up to 64 characters.</div></div>
        <div class="ctl"><input type="text" id="m-rt" maxlength="64" size="50" oninput="cnt(this,64)"><span class="cnt"></span>
          <button class="buttons" onclick="FM.cmd('RT '+val('m-rt'))">Send</button></div></div>
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
    ['car_mode','bool','Car-friendly text (recommended)','The station display stays on your station name and the song goes in the now-playing text: one line per song, changed only when the song changes. Cars (Tesla included) struggle to show text that changes every few seconds. Off = the song name is paged through the station display too, which some home radios like.'],
    ['rds_auto','bool','Follow the show automatically','Off = nothing is sent automatically; type text by hand on the Station & RDS tab.'],
    ['station_ps','text8','Station name','What radios show as your station (8 characters), e.g. 105.5 FM.'],
    ['idle_rt','text','Now-playing text between shows','{next} = the next show time.'],
    ['__adv__'],
    ['rt_template_show','text','Now-playing pattern: songs','Start with {count} for the x/N counter. Tokens: {count} {title} {artist} {extra} {station} {next}.'],
    ['rt_template_inter','text','Now-playing pattern: Station ID / intros',''],
    ['rt_page_s','num','Seconds per now-playing screen','When a piece has several now-playing lines. Car-friendly uses at least 20 s: each change blanks the text on many radios while they re-read it.'],
    ['ps_template','text','Station display pattern (car-friendly off)','{short} = the short name.'],
    ['ps_page_s','num','Seconds per station-display screen','Car-friendly uses at least 4 s.'],
    ['station_between','bool','Station name between song screens','Car-friendly off only.'],
    ['ps_uppercase','bool','Station display in capitals',''],
    ['ps_center','bool','Center short station-display screens',''],
    ['idle_ps','text','Station display between shows','{station} = the station name.'],
  ];
  const GEN_SET = [
    ['port','text','Serial port','Pi GPIO UART: /dev/serial0 (GPIO14 TX pin 8, GPIO15 RX pin 10).'],
    ['master','text','Show master','auto = the MultiSync player, local = this FPP, or an IP address.'],
    ['tx_auto','bool','TX on/off with the show','TX ON when the show starts; TX OFF after it has stopped for the grace time.'],
    ['tx_off_grace_s','num','TX off grace (s)','Covers the gap between playlist loops.'],
    ['vol_default','num','Default volume','0-255: VOL between songs and for songs without their own volume. -1 = not set (the transmitter volume is left alone; the first song given its own volume sets this to the current volume). Setting the volume on the Status tab while such a song plays updates it too.'],
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

  // ---------------------------------------------------------------- radio display
  // Shows PS/RT the way a car radio would; cycles through screens at their real timing.
  function Radio(el) {
    this.el = el; this.ps = []; this.rt = []; this.pi = 0; this.ri = 0; this.t1 = null; this.t2 = null; this.sig = '';
    el.innerHTML = '<div class="radio"><div class="lcd"><div class="top"><span><span class="dot"></span><span class="freq"></span></span><span class="rbadge">RDS</span></div>' +
      '<div class="zone" data-z="ps"><span class="mk">1</span><div class="lbl">STATION</div><div class="ps"></div></div>' +
      '<div class="zone" data-z="rt"><span class="mk">2</span><div class="lbl">NOW PLAYING</div><div class="rt"></div></div></div></div>';
    this.q = c => el.querySelector(c);
  }
  Radio.prototype.set = function (o) {
    this.last = o;
    this.q('.freq').textContent = o.freq != null ? o.freq + ' FM' : 'FM';
    this.q('.dot').className = 'dot' + (o.onair ? ' on' : '');
    this.q('.rbadge').textContent = this.pv != null ? (this.pvSaved ? 'SENDING...' : 'PREVIEW') : (o.rds === false ? 'RDS OFF' : 'RDS');
    const ps = this.pv != null ? [psCenter(this.pv)] : ((o.ps || []).length ? o.ps : ['']), rt = (o.rt || []).length ? o.rt : [''];
    const sig = JSON.stringify([ps, rt, o.ps_s, o.rt_s]);
    if (sig === this.sig) return;
    this.sig = sig; this.ps = ps; this.rt = rt; this.pi = this.ri = 0;
    clearInterval(this.t1); clearInterval(this.t2);
    const showPS = () => {
      const t = (this.ps[this.pi++ % this.ps.length] || '').padEnd(8, ' ').slice(0, 8);
      this.q('.ps').innerHTML = t.split('').map(ch => '<span class="c">' + (ch === ' ' ? '&nbsp;' : esc(ch)) + '</span>').join('');
    };
    const showRT = () => {
      const r = this.q('.rt'), txt_ = this.rt[this.ri++ % this.rt.length] || '';
      if (this.rt.length > 1) { r.classList.add('blank'); setTimeout(() => { r.textContent = txt_; r.classList.remove('blank'); }, 300); }
      else r.textContent = txt_;
    };
    showPS(); showRT();
    if (this.ps.length > 1) this.t1 = setInterval(showPS, (o.ps_s || 4) * 1000);
    if (this.rt.length > 1) this.t2 = setInterval(showRT, (o.rt_s || 20) * 1000);
  };
  Radio.prototype.preview = function (text, saved) {   // null = back to what is on air
    this.pv = text; this.pvSaved = !!saved;
    if (this.last) this.set(this.last);
  };
  // the plugin centres a short station name in the 8 characters (ps_center)
  function psCenter(t) {
    t = String(t || '').slice(0, 8);
    if (SET && SET.ps_center === false || t.length >= 8) return t;
    const pad = 8 - t.length, l = Math.floor(pad / 2);
    return (' '.repeat(l) + t + ' '.repeat(pad - l)).replace(/\s+$/, '');
  }
  Radio.prototype.hl = function (zone, on) {
    this.el.querySelectorAll('.zone').forEach(z => z.classList.toggle('hl', on && z.dataset.z === zone));
  };
  let RLIVE = null, RRDS = null, RPV = null;

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
    const om = d.ovm && d.ovm.total != null ? (d.ovm.last_min ? '⚠ ' : '') + 'overmod ' + d.ovm.last_min + '/min' +
      (d.ovm.piece != null ? ', ' + d.ovm.piece + ' this song' : '') + ', ' + d.ovm.total + ' total' : '';
    $('linkline').textContent = (om ? om + ' · ' : '') + (d.link.connected ? 'Link OK ' : 'Link DOWN ') + d.link.port +
      (d.fm30_age != null ? ' · status ' + d.fm30_age + ' s ago' : '') + ' · fm30d ' + d.version;
    const cnt = s.n ? s.n + '/' + s.N : (s.type === 'interstitial' ? 'interstitial' : (s.section === 'leadIn' ? 'lead-in' : '-'));
    $('nowgrid').innerHTML = kv('Show', s.playing ? 'playing' : 'idle') + kv('Playlist', s.playlist || '-') +
      kv('Piece', s.key || '-') + kv('Position', cnt) + kv('Next show', s.next || '-') +
      kv('Volume', f.vol != null ? String(f.vol) : '-') + kv('Overmodulation', om ? om.replace('overmod ', '') : '-') +
      kv('TX automation', SET && SET.tx_auto === false ? 'off' : d.tx_auto_state + (d.idle_off_in != null ? ' (off in ' + d.idle_off_in + ' s)' : '')) +
      (s.error ? kv('FPP', s.error) : '');
    $('pspages').innerHTML = r.auto ? pages(r.ps_pages, r.ps_now) + ' <span class="kv">' + (r.ps_s || '') + ' s each</span>' : '<span class="kv">automation off</span>';
    $('rtpages').innerHTML = r.auto ? pages(r.rt_pages, r.rt_now) + ' <span class="kv">' + (r.rt_s || '') + ' s each</span>' : '<span class="kv">automation off</span>';
    $('txgrid').innerHTML = kv('State', f.state || '-') + kv('Frequency', f.freq != null ? f.freq + ' MHz' : '-') +
      kv('Power', f.power != null ? f.power + ' / target ' + f.power_target : '-') +
      kv('Vtune', f.vtune != null ? f.vtune + ' V (exp ' + f.vtune_expected + ')' : '-') +
      kv('Pilot lock', f.pilot_lock ? 'yes' : 'no') + kv('RDS', f.rds ? 'on' : 'muted') +
      kv('Station display (PS)', f.ps != null ? '[' + f.ps + ']' : '-') + kv('Now-playing text (RT)', f.rt || '-') +
      kv('Volume / balance', f.vol != null ? f.vol + ' / ' + f.bal : '-') +
      kv('Overmodulation', d.ovm && d.ovm.total != null ? (d.ovm.last_min ? '⚠ ' : '') +
        d.ovm.last_min + ' in the last minute · ' + (d.ovm.piece != null ? d.ovm.piece + ' this song · ' : '') + d.ovm.total + ' total' : '-') +
      kv('Faults', (f.fault ? f.fault + ' ' : '') + '(' + (f.faults || 0) + ' total)') +
      kv('Station code / program type', f.pi != null ? f.pi + ' / ' + (PTY[f.pty] || f.pty) : '-') + kv('Uptime', f.uptime != null ? f.uptime + ' s' : '-');
    const live = {freq: f.freq, onair: !!f.tx, rds: !!f.rds, ps: f.ps != null ? [f.ps] : [], rt: f.rt != null ? [f.rt] : []};
    if (RLIVE) RLIVE.set(live);
    if (RRDS) {
      if (RRDS.pv != null && RRDS.pvSaved && (String(f.ps || '').trim() === psCenter(RRDS.pv).trim() || Date.now() - RRDS.pvAt > 15000)) RRDS.pv = null;
      RRDS.set(live);
    }
    ['RDS', 'MS', 'STEREO', 'TP', 'TA'].forEach(k => {
      const el = $('f-' + k), v = f[k === 'RDS' ? 'rds' : k.toLowerCase()];
      if (el && v != null && !el._busy) el.checked = !!v;
    });
    $('rawstatus').textContent = JSON.stringify(f, null, 1);
    $('linkstats').textContent = JSON.stringify(d.link, null, 1);
    $('manualnote').textContent = r.auto ? 'The plugin is following the show, so typed text would be overwritten and is refused. Turn off "Follow the show automatically" on the Show & RDS tab first.' : 'Not following the show: text you send stays on air.';
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
    let html = '', adv = false;
    defs.forEach(([k, t, label, help]) => {
      if (k === '__adv__') { html += '<details class="adv"><summary>Advanced: patterns and timing</summary>'; adv = true; return; }
      const v = SET[k];
      const input = t === 'bool' ? '<label class="sw"><input type="checkbox" data-k="' + k + '"' + (v ? ' checked' : '') + '><span></span></label>'
        : '<input type="' + (t === 'num' ? 'number' : 'text') + '" step="any" data-k="' + k + '" value="' + esc(v) + '"' +
          (t === 'text8' ? ' maxlength="8" size="10" class="mono"' : ' size="' + (t === 'num' ? 8 : 46) + '"') + '>';
      html += '<div class="opt"><div><div class="t">' + esc(label) + '</div>' + (help ? '<div class="d">' + esc(help) + '</div>' : '') +
        '</div><div class="ctl">' + input + '</div></div>';
    });
    $(host).innerHTML = html + (adv ? '</details>' : '');
  }
  async function loadSettings() {
    const d = await call('settings');
    if (!d.settings) return;
    SET = d.settings;
    settingRows('rdsset', RDS_SET);
    if (!$('r-name').value) { $('r-name').value = SET.station_ps || ''; cnt($('r-name'), 8); }
    editorNotes();
    settingRows('genset', GEN_SET);
    $('lcdslots').innerHTML = ['2', '3', '4'].map(n => {
      const m = (SET.lcd_msgs || {})[n] || ['', ''];
      return '<div class="row"><label>Slot ' + n + ' (page ' + (+n + 4) + ')</label>' +
        '<input type="text" id="lt' + n + '" size="9" maxlength="8" value="' + esc(m[0]) + '" placeholder="top" oninput="cnt(this,8)"><span class="cnt"></span>' +
        '<input type="text" id="lb' + n + '" size="40" maxlength="48" value="' + esc(m[1]) + '" placeholder="bottom" oninput="cnt(this,48)"><span class="cnt"></span>' +
        '<button class="buttons" onclick="FM.lcd(' + n + ')">Set</button><button class="buttons" onclick="FM.lcd(' + n + ',1)">Clear</button></div>';
    }).join('');
  }
  FM.setName = async function () {
    const v = $('r-name').value.trim();
    if (!v) { toast('Enter a station name', false); return; }
    const d = await call('settings', 'PUT', {station_ps: v});
    if (!d.ok) { toast(d.error || 'failed', false); return; }
    SET = d.settings; loadSettings();
    if (SET.rds_auto === false) {
      RRDS.preview(null);
      toast('Saved. "Follow the show automatically" is off, so send it by hand below to put it on air.', true);
      return;
    }
    RRDS.pvAt = Date.now(); RRDS.preview(v, true);      // shows SENDING... until the FM30 reports it
    toast('Station name saved: on air in a few seconds', true);
    if (SEL) FM.preview();
    refresh();
  };
  FM.namePreview = function () {
    const v = $('r-name').value.trim();
    RRDS.preview(v && (!SET || v !== SET.station_ps) ? v : null);
  };
  FM.setPI = function () {
    const v = val('r-pi').toUpperCase();
    if (!/^[0-9A-F]{4}$/.test(v)) { toast('The station code is 4 characters, 0-9 and A-F (e.g. 1234 or C0DE)', false); return; }
    if (ST && ST.fm30 && String(ST.fm30.pi).toUpperCase() === v) { toast('Already ' + v, true); return; }
    if (!confirm('Change the station code to ' + v + '?\nRadios will treat this as a different station (presets may stop matching).')) return;
    FM.cmd('PI ' + v);
  };
  FM.flag = async function (name, el) {
    const on = el.checked;
    if (name === 'TA' && on && !confirm('Turn on "traffic report now"?\nMany cars will interrupt what they are playing and turn the volume up. Normally leave this off.')) { el.checked = false; return; }
    el._busy = true;
    const d = await FM.cmd(name === 'RDS' ? 'RDS ' + (on ? 'ON' : 'OFF') : name + ' ' + (on ? 1 : 0));
    el._busy = false;
    if (!d || !d.ok) el.checked = !on;
  };
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
    if (d.ok) { toast(d.restart_needed ? 'Saved - restart the service for port changes' : 'Saved', true); SET = d.settings; editorNotes(); if (SEL) FM.preview(); }
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
        (p.names.ps_lines || p.names.rt_lines ? ' <span class="badge show">custom RDS</span>' : '') +
        (p.names.vol != null ? ' <span class="badge">vol ' + p.names.vol + '</span>' : '') + ovmBadge(p, d.vol_default);
      return '<tr class="piece' + (SEL === p.key ? ' sel' : '') + '" onclick="FM.edit(' + i + ')"><td class="mono">' + esc(p.key) + need +
        '</td><td>' + typ + (p.type_auto ? ' <span class="kv">auto</span>' : '') + '</td><td>' + esc(p.effective.title) +
        '</td><td>' + esc(p.effective.artist) + '</td><td>' + esc(p.playlists.join(', ')) + '</td></tr>';
    }).join('') || '<tr><td colspan="5">No playlists found on the show master.</td></tr>';
  };
  function mmss(t) { t = Math.max(0, Math.round(t)); return Math.floor(t / 60) + ':' + String(t % 60).padStart(2, '0'); }
  // hover text: how often (per minute) and where in the song it overmodulated
  function ovmTip(n, secs, times, extra) {
    let t = n + ' overmodulation' + (n === 1 ? '' : 's');
    if (secs > 0) t += ' in ' + mmss(secs) + (n ? ' = ' + (n * 60 / secs).toFixed(1) + ' per minute' : '');
    if (times && times.length) {
      t += '\nAt (song time): ' + times.map(([s, k]) => mmss(s) + (k > 1 ? ' x' + k : '')).join(', ');
      if (n > times.reduce((a, [, k]) => a + k, 0)) t += ', ...';
    }
    return t + (extra ? '\n' + extra : '');
  }
  function ovmBadge(p, vdef) {
    let out = '';
    const o = p.ovm, eff = p.names.vol != null ? p.names.vol : (vdef >= 0 ? vdef : null);
    if (o) {
      const tip = ovmTip(o.ovm, o.secs, o.times, 'Last play ' + o.at + (o.vol != null ? ' at vol ' + o.vol : '') +
        (o.partial ? ' (joined part-way, times are from when it was joined)' : '') + ' · ' + o.plays + ' play(s) logged');
      out += o.ovm ? ' <span class="badge ovm" title="' + esc(tip) + '">overmod ' + o.ovm + (o.vol != null ? ' @ vol ' + o.vol : '') + '</span>'
                   : ' <span class="badge clean" title="' + esc(tip) + '">clean' + (o.vol != null ? ' @ vol ' + o.vol : '') + '</span>';
      if (o.vol != null && eff != null && eff !== o.vol) out += ' <span class="badge recheck" title="Its volume changed after that play; the tag updates when it next plays.">recheck: vol now ' + eff + '</span>';
    }
    if (p.ovm_now != null) {
      const dn = p.ovm_now_detail || {};
      out += ' <span class="badge' + (p.ovm_now ? ' ovm' : '') + '" title="' + esc(ovmTip(p.ovm_now, dn.secs, dn.times, 'Playing now')) + '">playing: ' + p.ovm_now + ' so far</span>';
    }
    return out;
  }
  const FIELDS = ['type', 'title', 'artist', 'short', 'extra', 'ps_template', 'rt_template', 'ps_s', 'rt_s', 'vol'];
  const NUMS = ['ps_s', 'rt_s', 'vol'];
  function volNote() {
    const dv = SET && SET.vol_default >= 0 ? SET.vol_default : null;
    const v = val('e-vol');
    $('e-volr').value = v !== '' ? v : (dv != null ? dv : 128);
    txt('e-volnote', v !== '' ? '' : (dv != null ? 'default = ' + dv : 'default not set: the transmitter volume is left as is'));
  }
  FM.volTry = function () {
    const v = val('e-vol') !== '' ? val('e-vol') : (SET && SET.vol_default >= 0 ? SET.vol_default : '');
    if (v === '') { toast('No volume set for this piece', false); return; }
    FM.cmd('VOL ' + v);
  };
  const LINES = ['ps_lines', 'rt_lines'];
  let LASTTA = 'e-rt_lines';
  function lineCounts(id, max) {
    const out = $(id).value.split('\n').map((l, i) => {
      l = l.trim(); if (!l) return '';
      const tok = /\{\w+\}/.test(l), n = l.length;
      const st = tok ? '<span class="kv">uses fields (e.g. {count}): see the preview for the real length</span>' : n <= max ? '<span class="ok">\u2713 fits</span>'
        : '<span class="long">' + Math.ceil(n / max) + '+ screens</span>';
      return '<div><span class="n">' + (i + 1) + '</span>' + (tok ? '' : '<span>' + n + '/' + max + '</span>') + st + '</div>';
    }).join('');
    $(id === 'e-ps_lines' ? 'lc-ps' : 'lc-rt').innerHTML = out;
  }
  function updateLC() { lineCounts('e-ps_lines', 8); lineCounts('e-rt_lines', 64); }
  function editorNotes() {
    const car = !SET || SET.car_mode !== false;
    if ($('e-shortnote')) txt('e-shortnote', car ? 'only used when car-friendly text is off' : 'what the station display pages through');
    if ($('e-psnote')) $('e-psnote').innerHTML = car
      ? '<span class="warn">Car-friendly text is on:</span> keep this to your station name. Changing screens here are hard for cars to show.'
      : 'One screen per line. Longer lines are split into several screens.';
    if ($('e-rtnote')) txt('e-rtnote', car ? 'One line per song is best. Extra lines take turns, at least 20 s each.' : 'One screen per line; they take turns.');
  }
  FM.tok = function (t) {
    if (!$('e-custom').checked) { $('e-custom').checked = true; FM.customToggle(); }
    const ta = $(LASTTA), a = ta.selectionStart != null ? ta.selectionStart : ta.value.length, b = ta.selectionEnd != null ? ta.selectionEnd : a;
    ta.value = ta.value.slice(0, a) + t + ta.value.slice(b);
    ta.focus(); ta.selectionStart = ta.selectionEnd = a + t.length;
    updateLC(); clearTimeout(pvTimer); pvTimer = setTimeout(FM.preview, 300);
  };
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
    updateLC();
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
    updateLC();
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
    updateLC(); editorNotes();
    volNote();
    txt('e-ovm', p.ovm ? p.ovm.ovm + ' on its last play (' + p.ovm.at + (p.ovm.vol != null ? ', vol ' + p.ovm.vol : '') + ')' : 'not played yet');
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
    FIELDS.forEach(k => { const v = val('e-' + k); if (v !== '') o[k] = NUMS.includes(k) ? Number(v) : v; });
    if ($('e-custom').checked) LINES.forEach(k => { const l = linesOf('e-' + k); if (l.length) o[k] = l; });
    return o;
  }
  FM.preview = async function () {
    if (!SEL) return;
    const d = await call('preview', 'POST', {key: SEL, playlist: val('e-pl'), names: editorNames()});
    if (!d.ps) return;
    AUTO = {ps: d.auto_ps || [], rt: d.auto_rt || []};
    showAuto();
    if (RPV) RPV.set({freq: ST && ST.fm30 ? ST.fm30.freq : null, onair: true, ps: d.ps, rt: d.rt, ps_s: d.ps_s, rt_s: d.rt_s});
    txt('pv-meta', (d.n ? '· song ' + d.n + ' of ' + d.N : '· not counted') + (d.car_mode ? ' · car-friendly' : ''));
    $('pv-ps').innerHTML = pages(d.ps) + ' <span class="kv">' + (d.ps.length > 1 ? d.ps_s + ' s each' : 'stays on') + '</span>';
    $('pv-rt').innerHTML = d.rt.map(p => '<div><span class="page">' + esc(p) + '</span><span class="cnt' + (p.length > 64 ? ' over' : '') + '">' + p.length + '/64</span></div>').join('') +
      '<span class="kv">' + d.rt.length + ' screen' + (d.rt.length === 1 ? ', stays on for the song' : 's, ' + d.rt_s + ' s each') + '</span>';
  };
  FM.savePiece = async function () {
    const d = await call('piece', 'PUT', {key: SEL, names: editorNames()});
    if (d.ok) { toast('Saved ' + SEL + ' (live now if it is playing)', true); FM.loadPieces(); } else toast(d.error || 'failed', false);
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
  $('r-pty').innerHTML = PTY.map((n, i) => '<option value="' + i + '">' + esc(n) + '</option>').join('');
  RLIVE = new Radio($('liveradio')); RRDS = new Radio($('rdsradio')); RPV = new Radio($('pvradio'));
  ['e-ps_lines', 'e-rt_lines'].forEach(id => {
    $(id).addEventListener('focus', () => { LASTTA = id; });
    $(id).addEventListener('input', updateLC);
  });
  // editing a box lights up the part of the display it controls, and hovering the display lights up the box
  const ZONE_OF = {'e-ps_lines': 'ps', 'e-short': 'ps', 'e-ps_template': 'ps',
                   'e-rt_lines': 'rt', 'e-title': 'rt', 'e-artist': 'rt', 'e-extra': 'rt', 'e-rt_template': 'rt'};
  Object.entries(ZONE_OF).forEach(([id, z]) => {
    ['focus', 'mouseenter'].forEach(ev => $(id).addEventListener(ev, () => RPV.hl(z, true)));
    ['blur', 'mouseleave'].forEach(ev => $(id).addEventListener(ev, () => { if (document.activeElement && ZONE_OF[document.activeElement.id]) RPV.hl(ZONE_OF[document.activeElement.id], true); else RPV.hl(z, false); }));
  });
  $('pvradio').querySelectorAll('.zone').forEach(zn => {
    zn.addEventListener('mouseenter', () => { zn.classList.add('hl'); document.querySelectorAll('#editor .field[data-z="' + zn.dataset.z + '"]').forEach(f => f.classList.add('hl')); });
    zn.addEventListener('mouseleave', () => { zn.classList.remove('hl'); document.querySelectorAll('#editor .field.hl').forEach(f => f.classList.remove('hl')); });
  });
  $('e-vol').addEventListener('input', volNote);
  document.querySelectorAll('#editor input, #editor select, #editor textarea').forEach(el => el.addEventListener('input', () => {
    clearTimeout(pvTimer); pvTimer = setTimeout(FM.preview, 300);
  }));
  try { const t = localStorage.getItem('fm30tab'); if (t) activate(t); } catch (e) {}
  loadSettings().then(refresh);
  setInterval(refresh, 2000);
  setInterval(() => { if ($('t-show').classList.contains('active') && PIECES) FM.loadPieces(); }, 15000);
})();
</script>
