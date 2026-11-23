#!/bin/bash
# Run by FPP's Plugin Manager on uninstall. Settings/names in
# /home/fpp/media/config/fpp-fm30-*.json are kept.
SUDO=${SUDO-sudo}
$SUDO systemctl disable --now fpp-fm30.service 2>/dev/null
$SUDO rm -f /etc/systemd/system/fpp-fm30.service
$SUDO systemctl daemon-reload
echo "fpp-fm30 service removed."
