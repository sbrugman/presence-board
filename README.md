# Presence Board
Tracks Wi-Fi probe requests. Displays presence board. Endless possibilities: show a welcome message when someone enters office, stalk your neighbour, check if the mailman passed by with your package, start romatic music when your girlfriend is near... 

## How it works
The Wi-Fi probes are detected using the monitor mode of the wireless adapter. If yours doesn't support monitor mode, they're available in China for less than 2 USD. The data is collected using Python. The dashboard and storage is handled in PHP.

## Usage
Place the files from the www-directory on your (local)server. Change the path on line 22 of ``monitor.py``. Now run ``monitor.py`` to collect the Wi-Fi probes and start having fun. If you have any questions on the usage or made a nice application, I'd like to hear from you.

Based on https://github.com/cdaller/hoover.
