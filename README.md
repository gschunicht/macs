please refere to http://koljawindeler.github.io/macs


Geoff notes

LED info
	//	if we have a blank device this will happen:
	//	1. Both LEDs will toggle for 10sec (saving WiFi credentials (not applicable here) or waiting for input)
	//
	//	if we have a config that is OUT of reach:
	//	1. (MACS=Both LEDs)/(UPDATE1=green LED)/(UPDATE2=red LED) will flash 3x (MACS=simultaneously) to show that the config has been read
	//	2. Green off, red on will show the start of the WiFi scanning
	//	3. per WiFi that has been found the green and red will toggle, just to show activity
	// 	4. Both LEDs are switched off
	//	5. Both LEDs will toggle for 10sec (saving WiFi credentials (not applicable here) or waiting for input) 20Hz
	//
	//	if we have a config that is IN reach:
	//	1. (MACS=Both LEDs)/(UPDATE1=green LED)/(UPDATE2=red LED) will flash 3x (MACS=simultaneously) to show that the config has been read
	//	2. Green off, red on will show the start of the WiFi scanning
	//	3. per WiFi that has been found the green and red will toggle, just to show activity
	// 	4. Both LEDs are switched off
	//	5. (MACS=Both LEDs)/(UPDATE1=green LED)/(UPDATE2=red LED) will toggle 5x (WiFi found) 10Hz
	//	6. Both LEDs will toggle for 10sec or until WiFi data are saved (saving WiFi credentials or waiting for input) 20Hz
	//	7. (MACS=Both LEDs)/(UPDATE1=green LED)/(UPDATE2=red LED) will toggle 2x (WiFi connected) 10Hz

  

  // GS: Connected to Wifi, no card, red blink
  // Connected, bad card - ** Solid Red **
  // Connected, good card, Green  + Red Blink

  // Not Connected - Blink both ??


  
  // 1. assuming that we are NOT connected, we reached this point and the create_report has success to reconnet than it will call set_connected()
  // this will turn red off (which is fine (was blinking = not connected)) and green to blink (ok), so we have to override it
  // 2. assuming that we are NOT connected, we reached this point and the create_report has NO success to reconnet than it will not call set_connected()
  // the red will keep blinking (ok) but we still want to show that this card was good, turn green on
  // 3. assuming that we are connected, we reached this point then create_report will not try to reconnect and the report is send just fine
  // the red will be off anywa (ok), we want to show that this card was good, turn green on
  // 4. assuming that we are connected, we reached this point then create_report will not try to reconnect, but the report failed, create_report will set us to not conneted
  // the red will be blinkin (ok), we want to show that this card was good, turn green on

The Serial port can be used to update Wifi - write to it:
    // buffer is 64 byte (id(1)+<tab>+SSID(20)+<tab>+pw(20)+<tab>+type(1)+<tab>+chk(1)+<tab>)=48
    // e.g. 00 09 6d 61 63 73 09 36 32 31 35 30 32 37 30 39 34 09 03 09 17 09
    // e.g. 01 09 61 6a 6c 6f 6b 65 72 74 09 71 77 65 71 77 65 71 77 65 09 03 09 60 09
    // e.g. 02 09 73 68 6f 70 09 61 62 63 64 65 66 67 68 09 02 09 0E 09
        //   type=3; // wpa2 ;
             type=2; // wpa
             chk, simple checksum




particle serial wifi --file <config file>

The JSON file for passing Wi-Fi credentials should look like this:
{
  "network": "my_ssid",
  "security": "WPA2_AES",
  "password": "my_password"
}

Passwords:  bva333/bva333  - user login:   SuperCompaq01.
mysql.exe -u root -p.     Password is macs

particle usb start-listening
particle serial wifi --file gwhomewifi.json

particle usb cloud-status  GeoffSPhoton

re-flash, enable then flash
particle usb dfu 
particle flash --usb makerspace.bin

Is this easier
particle flash --serial makerspace.bin

particle serial wifi


card reader data

Tag 2507647 found. Checking database (100) for matching key==============
==============
card key still not valid. :P
calling:/history.php?logme&badge=2507647&mach_nr=38&event=Rejected
db request took 805 ms
Check tag serial1!
Check tag serial1!
serial1 card reader read 38  ox26  // card msb
Check tag serial1!
serial1 card reader read 67  0x43  // card
Check tag serial1!
serial1 card reader read 127 0x7F  // card lsb
Check tag serial1!
serial1 card reader read 26 0x1a   // checksum (xord)
Have card key tag!2507647
Tag 2507647 found. Checking database (100) for matching key==============
==============
Card Key not valid, requesting update from server
db request took 726 ms
Requested:
/m2m.php?v=20160214&mach_nr=38&forced=0
Recevied:


What I have learned:
Each machine (reader) polls the app every 10 minutes (the m2m.php). The machine IDs and last 2 digits of the IP appear to be one in the same. The readers are provided a CSV list of allowed badges upon access  changes, hence the readers check a cached copy of allowed badges and are not querying the app each badge insert.
When the APP gets a poll from a reader, it does the following:
first updates a the mach table with a time stamp indicating the last poll time of a reader.
The script loads the set of badge IDs that are valid for that machine based on the user and access tables. (see query below) , the results of this are converted to ints values (from the string in db) aka remove leading zeros, then creates a big CSV for with each allowed badge ID, which later will be returned.
The script then looks to see if there are any access changes for that machine ID - or if the force flag was set.
If there were changes then the m2m page returns the CSV of badge IDs to the reader, otherwise it returns "nu"
The evidence here…
Step 1
From the Access logs, machines poll every 10 minutes. Note the size 2 return for most cases.
Line 2501309: 192.168.188.111 - - [03/Mar/2023:09:35:10 -0600] "GET /m2m.php?v=20160214&mach_nr=11&forced=0 HTTP/1.0" 200 2 "-" "-"
Line 2501310: 192.168.188.119 - - [03/Mar/2023:09:35:58 -0600] "GET /m2m.php?v=20160214&mach_nr=19&forced=0 HTTP/1.0" 200 2 "-" "-"
Line 2501313: 192.168.188.135 - - [03/Mar/2023:09:37:07 -0600] "GET /m2m.php?v=20160214&mach_nr=35&forced=0 HTTP/1.0" 200 2 "-" "-"
…
Line 2501359: 192.168.188.111 - - [03/Mar/2023:09:45:11 -0600] "GET /m2m.php?v=20160214&mach_nr=11&forced=0 HTTP/1.0" 200 2 "-" "-"
Step 2,
 run query to get badge_ids for a machine, there is more code that makes the CSV, just see script…
SELECT badge_id FROM `user` WHERE active=1 and id in (select user_id from access where mach_id=(select id from mach where mach_nr=19));
Now for step 3
This query is run looking to see if that machine needs an update, then the an insert to the log table is ,made with "Station updated" .
 SELECT COUNT(*) FROM `update_available` WHERE mach_id in (select id from mach where mach_nr=19);
I manually took results from this and made the big long CVS, which was interestingly 807 chars (see step 4 below).
Snip of the log table
# id, timestamp, user_id, machine_id, event, login_id, usage
'63982', '1677698484', '0', '19', 'Station updated', NULL, '0'
'63983', '1677698614', '0', '34', 'Station updated', NULL, '0'
Step 4: evidence that data > 2 chars is returned and did correspond with the noted time stamps above
192.168.188.111 - - [01/Mar/2023:13:20:47 -0600] "GET /m2m.php?v=20160214&mach_nr=11&forced=0 HTTP/1.0" 200 2 "-" "-"
192.168.188.119 - - [01/Mar/2023:13:21:24 -0600] "GET /m2m.php?v=20160214&mach_nr=19&forced=0 HTTP/1.0" 200 807 "-" "-"
192.168.188.125 - - [01/Mar/2023:13:22:23 -0600] "GET /m2m.php?v=20160214&mach_nr=25&forced=0 HTTP/1.0" 200 2 "-" "-"
192.168.188.134 - - [01/Mar/2023:13:23:34 -0600] "GET /m2m.php?v=20160214&mach_nr=34&forced=0 HTTP/1.0" 200 1180 "-" "-"
I have a few suggestions:
first, it appears much of the app is working, though I do think getting rid of dups will help… I also think we could "force" updates using the force flag in the script.
We know things are slowish, so clean up the log files (archive or whatever) AND clean the log DB table, eg remove entries older than a year. Lastly maybe remove/inactivted users that have not access since moving to the new site.
What we really need is the code / FW for the reader, Does anyone know where this code is?. It seems there are few possible issues.
1.) the comparison is not being done right… eg those 000's are on the reader but not CSV (doubt it)
2.) The sheer number of entries is causing issues. See table below. 
@james.woods
 Question I have is do some machines work, others not…
3.) the reader times out waiting for results - eg maybe that log update takes too long (note, I'd expect apache errors here).
User to machine access counts.
Cnt       ID          Machine name
===      ===       ============
3         12        Miller Filtair MWX-D Fume Extractor
3         13        Miller Filtair MWX-D Fume Extractor
3         29        Jet Dust Collector 1 of 2
3         30        Jet Dust Collector 2 of 2
10        36        NEODEN Parts Placer
13        14        Laser
16        8         Hydra-Power Press Brake
16        16        HAAS VF-2
17        17        Lathe - 13 x 40 Dayton
17        37        ELEC_TABLE_1
19        3         Pemserter
19        11        Miller Welder - Dynasty 350
20        39        Front Gate
21        42        Stratasys FDM 400 mc - T-1184
21        43        Stratasys FDM 400 mc - T-1183
22        9         Miller Welder - Millermatic 212
22        45        HP Z6100 Plotter
23        7         Boschert Punch
24        10        Miller Welder - Thunderbolt XL
28        35        FARO 3D Scanning Arm
30        18        Lagun Mill
32        24        Jet Horizontal Vertical Bandsaw
41        41        SSC Panel Saw
53        33        CNC Shark HD4
62        6         Grind_Table_2
63        15        Grind_Table_3
64        5         Grind_Table_1
65        1         Baldor Grinder
83        2         Universal Abrasive Blast Machine
88        20        Sawstop Router
96        4         Arfa Drill Press
98        32        Rikon Mini Lathe
99        31        Jet Woodworking Lathe
99        40        Mid Gate
103        19        Sawstop
103        38        ELEC_TABLE_2
105        28        Delta Deluxe Long Bed Jointer
116        21        Jet 22 Scroll Saw
118        25        Powermatic 22-44 Drum Sander
126        22        Rikon Band Saw
126        27        Jet Spindle Sander
129        26        Dewalt 13 Thickness Planer
131        23        Jet Industrial Belt and Disc Machine
137        44        AA Card Test
149        34        Dewalt Sliding Compound Miter Saw (edited) 



