#!/bin/bash
# Run by FPP's Plugin Manager on install/upgrade: installs and starts the
# fm30d service. Does NOT touch the Pi's boot config; see setup_uart.sh.
set -e
DIR="$(cd "$(dirname "$0")/.." && pwd)"
SUDO=${SUDO-sudo}
CFG=/home/fpp/media/config
UNIT=/etc/systemd/system/fpp-fm30.service

$SUDO tee "$UNIT" >/dev/null <<UNITEOF
[Unit]
Description=FM30 transmitter service (fpp-fm30-controler FPP plugin)
After=network-online.target fppd.service
Wants=network-online.target

[Service]
User=fpp
SupplementaryGroups=dialout
ExecStart=/usr/bin/python3 -I $DIR/fm30d/fm30d.py --config $CFG
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
UNITEOF

$SUDO systemctl daemon-reload
$SUDO systemctl enable fpp-fm30.service
$SUDO systemctl restart fpp-fm30.service
echo "fpp-fm30 service installed and started."
echo "If the UART is not set up yet, run: sudo $DIR/scripts/setup_uart.sh  (dry run), then with --apply, then reboot."
