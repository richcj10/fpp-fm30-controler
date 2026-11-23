#!/bin/bash
# Frees the Pi 4's full UART (PL011) on GPIO14/15 for the FM30:
#   - enable_uart=1 and dtoverlay=disable-bt in config.txt (Bluetooth off,
#     so /dev/serial0 -> ttyAMA0, a stable 115200 clock)
#   - removes the serial console from cmdline.txt
#   - disables hciuart / serial-getty on serial0
# Dry run by default; pass --apply to change files (backups *.fm30bak). Reboot after.
APPLY=0; [ "$1" = "--apply" ] && APPLY=1
BOOT=/boot/firmware; [ -f $BOOT/config.txt ] || BOOT=/boot
CFG=$BOOT/config.txt; CMD=$BOOT/cmdline.txt
run() { echo "  $*"; [ $APPLY = 1 ] && eval "$@"; }

echo "Boot dir: $BOOT  ($( [ $APPLY = 1 ] && echo APPLYING || echo 'dry run, use --apply' ))"
[ $APPLY = 1 ] && cp -n $CFG $CFG.fm30bak && cp -n $CMD $CMD.fm30bak

grep -q '^enable_uart=1' $CFG || run "echo 'enable_uart=1' >> $CFG"
grep -q '^dtoverlay=disable-bt' $CFG || run "echo 'dtoverlay=disable-bt' >> $CFG"
if grep -qE 'console=(serial0|ttyAMA0|ttyS0),[0-9]+ ?' $CMD; then
  run "sed -i -E 's/console=(serial0|ttyAMA0|ttyS0),[0-9]+ ?//g' $CMD"
fi
systemctl is-enabled hciuart >/dev/null 2>&1 && run "systemctl disable --now hciuart"
systemctl is-enabled serial-getty@ttyAMA0.service >/dev/null 2>&1 && run "systemctl disable --now serial-getty@ttyAMA0.service"
systemctl is-enabled serial-getty@serial0.service >/dev/null 2>&1 && run "systemctl disable --now serial-getty@serial0.service"
echo "Current: $(ls -l /dev/serial0 2>/dev/null || echo '/dev/serial0 missing')"
[ $APPLY = 1 ] && echo "Done. Reboot, then check: ls -l /dev/serial0  (should point to ttyAMA0)"
