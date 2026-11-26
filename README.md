# FM30 Transmitter: FPP plugin

Runs a Ramsey FM30 with the `fm30_ctrl` STM32 controller (NUCLEO-G431KB) straight from Falcon Player over the Raspberry Pi's UART. It replaces the ESP8266 network front-end (FM30-Control): the FPP remote that feeds the transmitter's audio now also controls it.

What it does:

- **TX with the show.** When the show starts, TX goes on. When the show has been stopped for a grace time (default 60 s, which covers the gap between playlist loops), TX goes off. On a remote FPP, the show state comes from the MultiSync master.
- **Car-friendly RDS (default).** The station display (PS) stays on the station name and the song goes in the now-playing text (RadioText): one line per song, shortened to fit (greeting, then artist, then title), changed only when the song changes. Cars such as Teslas struggle with text that changes every few seconds. Turn it off under **Show & RDS → On-air text: how it works** to page song names through PS instead.
- **Live radio preview.** A generic LCD shows exactly what a radio displays, on the Status tab (live), on **Station & RDS**, and in the piece editor (updates as you type, with each box linked to the part of the display it controls).
- **Now-playing RDS** (car-friendly off).
  - PS shows a short name as 8-character word pages (about 2.5 s each). Long words are split with a hyphen, and the station tag shows between loops.
  - RT shows `x/N Title - Artist | extra`, split at word boundaries into pages of up to 64 characters. The counter starts every page, and pages rotate about every 10 s.
- **Your own RDS text per piece.** On **Show & RDS**, click a piece and tick **Custom text**. The PS and RT lines are filled in from the names (e.g. `{count} Mary, Did You Know? - Pentatonix`). Edit them, add your own lines (`MERRY` / `XMAS`, `{count} Merry Christmas from our house`) or delete lines; they rotate in order while that piece plays. Tokens stay live, so `{count}` still shows the right x/N after the show changes. Long lines are split automatically. Untick to go back to automatic text.
- **Volume per piece.** Each piece can have its own transmitter volume (VOL 0-255), applied as it starts; **Try now** sends it straight away. Pieces without one, and the time between shows, use the **Default volume** (Settings). Until either is used the plugin never touches the volume; the first piece given a volume makes the transmitter's current volume the default.
- **Flexible show list.** Nothing is hardcoded. The plugin reads the running playlist live, so you can add, remove or reorder pieces, or add whole new playlists. Names belong to the media file and fall back to the song's tags. Each piece is either a *show piece* (counted in x/N) or an *interstitial* (Station ID, intros: not counted). Lead-in and lead-out are never counted.
- **Everything the ESP did:**
  - frequency, power, volume and balance
  - every RDS field (PI, PTY, TP/TA/MS/STEREO, RDS on/off, level, phase, RDSFREE)
  - LCD on/off, pin, rotation and message slots 2-4
  - U6TRIM/U6/VTSCALE, DACTEST/DACDBG, DEFAULTS, SAVE, and a raw console

  The ESP's link rules are kept:
  - one command at a time, with reply matching and a retry
  - every input parsed, range-checked and rebuilt
  - text converted to printable ASCII
  - POWER always sent as decimal (the STM32 reads `080` as octal)
  - SAVE limited to once per 10 s

## Install

1. In FPP, open **Content Setup → Plugin Manager**, paste `https://raw.githubusercontent.com/richcj10/fpp-fm30-controler/main/pluginInfo.json` and install. This installs and starts the `fpp-fm30` systemd service.
2. Check the Pi's UART: `ls -l /dev/serial0` should point to `ttyAMA0`, with no `console=serial0` in `/proc/cmdline`. FPP images with `dtoverlay=miniuart-bt` already have this, so you can skip this step. Otherwise free it (needs a reboot):
   ```
   sudo /home/fpp/media/plugins/fpp-fm30-controler/scripts/setup_uart.sh          # dry run: shows what it would change
   sudo /home/fpp/media/plugins/fpp-fm30-controler/scripts/setup_uart.sh --apply  # then reboot
   ```
   This enables the UART, turns Bluetooth off (`dtoverlay=disable-bt`, so `/dev/serial0` becomes the full PL011 UART) and removes the serial console. It backs up the files it changes as `*.fm30bak`.
3. Open **Status/Control → FM30 Transmitter**.

## Wiring (3.3 V logic both sides, 115200 8N1)

| Pi 4 | Header pin | Direction | Nucleo | STM32 |
|---|---|---|---|---|
| GPIO14 (TXD) | 8 | → | A2 | PA3 (USART2 RX) |
| GPIO15 (RXD) | 10 | ← | A7 | PA2 (USART2 TX) |
| GND | 6 | — | GND | GND (tie to FM30 ground too) |

- The DAC+ HAT uses I2S (GPIO18-21), so there is no clash.
- Disconnect the ESP8266 first: only one device can drive PA3.
- The radio settings (frequency, power, RDS fields and so on) live in the STM32, so nothing is lost. The ESP only kept the LCD slot 2-4 text; retype it on the Display tab.

## Layout

| Path | What |
|---|---|
| `fm30d/fm30d.py` | the service: settings, TX automation, RDS pager, LCD slot 1, local JSON API (127.0.0.1:5440) |
| `fm30d/link.py` | UART link (termios) or `socket://host:port` for tests |
| `fm30d/commands.py` | command parsing/sanitising (port of FM30-Control `Commands.cpp` / `Sanitize.cpp`) |
| `fm30d/pager.py` | templates, PS word pages, RT pages |
| `fm30d/show.py` | follows FPP: running playlist, x/N, tags, piece list |
| `fm30.php`, `fm30api.php`, `menu.inc` | FPP page; `fm30api.php` proxies to the service (never name it `api.php`: FPP loads every plugin api.php into its own API) |
| `scripts/` | `fpp_install.sh`, `fpp_uninstall.sh`, `setup_uart.sh` |

Settings and names are stored in `/home/fpp/media/config/fpp-fm30-settings.json` and `fpp-fm30-names.json`. They are kept on uninstall.

## Tests (no hardware)

```
python -m unittest tests.test_units tests.test_integration
```

The integration test runs the service against `tests/mock_stm32.py` (the STM32 protocol mock from FM30-Control) over TCP, with a fake FPP.

Run the service by hand against the mock:

```
python tests/mock_stm32.py --tcp 7000
python fm30d/fm30d.py --config ./tmpcfg --port socket://127.0.0.1:7000
```
