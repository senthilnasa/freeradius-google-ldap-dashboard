#!/bin/bash
# Build JSON object from RADIUS request attributes
# This script is called by FreeRADIUS exec module to capture all request attributes

# Function to escape JSON strings
json_escape() {
    echo "$1" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g' | sed 's/\//\\\//g'
}

# Build JSON object with all available attributes
cat <<EOF
{"User-Name":"$(json_escape "$1")","NAS-IP-Address":"$(json_escape "$2")","NAS-Identifier":"$(json_escape "$3")","NAS-Port":"$(json_escape "$4")","NAS-Port-Type":"$(json_escape "$5")","Calling-Station-Id":"$(json_escape "$6")","Called-Station-Id":"$(json_escape "$7")","Acct-Session-Id":"$(json_escape "$8")","Service-Type":"$(json_escape "$9")","Framed-IP-Address":"$(json_escape "${10}")","Framed-MTU":"$(json_escape "${11}")","Connect-Info":"$(json_escape "${12}")","EAP-Type":"$(json_escape "${13}")","Aruba-Essid-Name":"$(json_escape "${14}")","Aruba-Location-Id":"$(json_escape "${15}")","Aruba-AP-Group":"$(json_escape "${16}")","Aruba-AP-Name":"$(json_escape "${17}")","Cisco-AVPair":"$(json_escape "${18}")","Cisco-NAS-Port":"$(json_escape "${19}")","Ruckus-SSID":"$(json_escape "${20}")","Ruckus-AP-MAC":"$(json_escape "${21}")","Ruckus-AP-Name":"$(json_escape "${22}")","Ubiquiti-SSID":"$(json_escape "${23}")","Ubiquiti-AP-MAC":"$(json_escape "${24}")","MikroTik-Group":"$(json_escape "${25}")","HP-Port-Priority":"$(json_escape "${26}")"}}
EOF
