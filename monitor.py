#!/usr/bin/env python

import threading
import signal
import sys
import time
import subprocess
import os
import os.path
import argparse
import requests
import json
import datetime

def send_data(entries):
    if verbose:
	print 'sending data...'
    payload = {}
    for key, value in entries.iteritems():
        payload[key] = {'mac':value.mac,'ssid':value.ssid,'datetime':value.timeLastSeen}

    url = 'http://localhost/hoover/api.php?action=dump'
    headers = {'Content-Type': 'application/json'}
    r = requests.post(url, data=json.dumps(payload), headers=headers)
    return {}

def signal_handler(signal, frame):
    if verbose:
        print 'You pressend CTRL+C, data is flushed into database/file...'
    switchThread.running = False
    switchThread.join()
    send_data(entries)
    sys.exit(0)

class switchChannelThread (threading.Thread):
    def __init__(self, threadID, name, delayInSeconds):
        threading.Thread.__init__(self)
        self.threadID = threadID
        self.name = name
	self.channels = [1,7,2,8,3,9,4,10,5,11,6,12]
        self.delayInSeconds = delayInSeconds
        self.running = True
    def run(self):
	if verbose:        
	    print 'Starting switch channel thread using a dely of %d seconds' % self.delayInSeconds
        while self.running:
            for channel in self.channels:
                if verbose: 
                    print 'Switching to channel %d' % (channel)
                if subprocess.call([iwconfigPath, interface, "channel", str(channel)]) != 0:
                    self.running = False
                    sys.exit(4)
                    
                time.sleep(self.delayInSeconds)
                if not self.running:
                    return       

class Entry (object):
    def __init__(self, mac, ssid, time):
        self.mac = mac
        self.ssid = ssid
        self.timeLastSeen = time

defaultInterface = "ra0"

# command line parsing:
parser = argparse.ArgumentParser(description='Show and collect wlan request probes')
parser.add_argument('--interface', default=defaultInterface, 
    help='the interface used for monitoring')
parser.add_argument('--tsharkPath', default='/usr/bin/tshark', 
    help='path to tshark binary')
parser.add_argument('--ifconfigPath', default='/sbin/ifconfig', 
    help='path to ifconfig')
parser.add_argument('--iwconfigPath', default='/sbin/iwconfig', 
    help='path to iwconfig')
parser.add_argument('--verbose', action='store_true', help='verbose information')
args = parser.parse_args()

tsharkPath = args.tsharkPath
ifconfigPath = args.ifconfigPath
iwconfigPath = args.iwconfigPath
interface = args.interface
verbose = args.verbose

# check all params
if not os.path.isfile(tsharkPath):
    print "tshark not found at path {0}".format(tsharkPath)
    sys.exit(1)
if not os.path.isfile(ifconfigPath):
    print "ifconfig not found at path {0}".format(ifconfigPath)
    sys.exit(1)
if not os.path.isfile(iwconfigPath):
    print "iwconfig not found at path {0}".format(iwconfigPath)
    sys.exit(1)

# start interface
if subprocess.call([ifconfigPath, interface, 'up']) != 0:
    print "cannot start interface: {0}".format(interface)
    sys.exit(2)

# Set interface in monitor mode
retVal = subprocess.call([iwconfigPath, interface, "mode", "monitor"])

if retVal != 0:
    print "cannot set interface to monitor mode: {0}".format(interface)
    sys.exit(3)

# start thread that switches channels
switchThread = switchChannelThread(1, 'SwitchChannel', 5)
switchThread.start()
signal.signal(signal.SIGINT, signal_handler)
if verbose:
    print 'press CTRL+C to exit'
# signal.pause()

# start tshark and read the results
fieldParams = "-T fields -E separator=, -e wlan.sa -e wlan_mgt.ssid -e frame.time";
# -e frame.number
tsharkCommandLine = "{0} -i {1} -n -l {2}" # -t ad
tsharkCommandLine += " subtype probereq"
tsharkCommandLine = tsharkCommandLine.format(tsharkPath, interface, fieldParams)

if verbose: 
    print 'tshark command: %s\n' % tsharkCommandLine, 

DEVNULL = open(os.devnull, 'w')
popen = subprocess.Popen(tsharkCommandLine, shell=True, stdout=subprocess.PIPE, stderr=DEVNULL)

# collect all Entry objects in entries
entries = {}

i = 0
for line in iter(popen.stdout.readline, ''):
    line = line.rstrip()
    if line.find(',') > 0:
        mac, rest = line.split(',', 1)
	rest = rest.split(',')	
	frame_time = ",".join(reversed([rest.pop(),rest.pop()]))
	ssid = ",".join(rest)
	time_str = datetime.datetime.strptime(frame_time[:-10], "%b %d, %Y %H:%M:%S").strftime("%Y-%m-%d %H:%M:%S")
	if not (mac + time_str) in entries:
	    if verbose:
                 print "entry found " + mac
            entries[mac + time_str] = Entry(mac, ssid, time_str) 
	i = i + 1
    if i > 10:
        entries = send_data(entries)
	i = 0
