
#include "application.h"
#include "stdint.h"
#include "config.h"

// enable to log wifi 
#define DEBUG_JKW_WIFI 1

class FindSSID
{
    char *SSID_to_search;
    bool found;
    LED *m_green;
    LED *m_red;
    // This is the callback passed to WiFi.scan()
    // It makes the call on the `self` instance - to go from a static
    // member function to an instance member function.
    static void handle_ap(WiFiAccessPoint* wap, FindSSID* self){
        self->next(*wap);
    }

    // determine if this AP is stronger than the strongest seen so far
    void next(WiFiAccessPoint& ap)
    {
        m_green->toggle();
        m_red->toggle();
        
        #ifdef DEBUG_JKW_WIFI
        Serial.print("search for ssid, checking ");
        Serial.println(ap.ssid);
        //delay(1000);
        #endif
        
        if(strcmp(ap.ssid,SSID_to_search)==0){
            #ifdef DEBUG_JKW_WIFI
            Serial.print("found ");
            Serial.println(SSID_to_search);
            delay(100);
            #endif
            found=true;
        }
    }

public:
    /**
     * Scan WiFi Access Points and retrieve the strongest one.
     */
    bool check_SSID_in_range(char *SSID, LED *green, LED *red)
    {
        #ifdef DEBUG_JKW_WIFI
          Serial.print("check SSID for ");
          Serial.println(SSID);
        #endif
        // initialize data
        found = false;
        SSID_to_search = SSID;
        m_green = green;
        m_red = red;
        
        // avoid scanning for invaid data
        if(strlen(SSID)==0){
            return false;
        }
        
        // perform the scan#
         WiFi.scan(handle_ap,this); 
         if (found) {
            return true;
         }

        WiFiAccessPoint aps[20];
        int countOfAps = WiFi.scan(aps, 20);
        for (int i=0; i<countOfAps && !found; i++) {
            WiFiAccessPoint& ap = aps[i];
           // Log.info("ssid=%s security=%d channel=%d rssi=%d", ap.ssid, (int)ap.security, (int)ap.channel, ap.rssi);
           Serial.print("Scan with array: have this ap ");
           Serial.println( ap.ssid);
           if(strcmp(ap.ssid,SSID_to_search)==0){
              found = true;
           }

        }

        #ifdef DEBUG_JKW_WIFI
        //Serial.println("EOS!!!!");
        #endif
        return found;
    }
};


// set the config for the update mode
// if the jumper is in position this function will be called in a loop until we return true
// our steps are: 
// 1. Clear all old wifi credentials to avoid connecting back to the MACS operational network
// 2. Load data set 1 from EEPROM
// 3. Run the SSID finder for this config, they will return true if SSID in scan results
// 4. set the credentials
// 4.1. if this fails, try config 2 (backup)
// 5. if we don't set any credentials: give the user 10 sec to add some wifis via serial
// 6. assuming we've set credentials, we'll return true, otherwise false and come back in a second
bool set_update_login(LED *green, LED *red){
	return set_login(green, red, UPDATE);
}

bool set_macs_login(LED *green, LED *red){
	return set_login(green, red, !UPDATE);
}
	
bool set_login(LED *green, LED *red, uint8_t mode){	
	//	... ok ... complicated
	//
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
	//
	
	String pw;
    String SSID;
    int type;
	uint8_t wifi_offset;
	uint8_t max_loop;
    bool have_found_SSID = false; // is there an SSID in range

    uint8_t config;
	
	//WiFi.off();
    WiFi.on();
    //WiFi.clearCredentials(); // just try last working
    WiFi.connect(WIFI_CONNECT_SKIP_LISTEN);
	
	// start with both LED's off
	green->off();
	red->off();

    // can we shortcut using stored data from before?
    if (WiFi.hasCredentials()){
        Serial.print("using prior credentials ");

        max_loop=15;
        for(config=0; config < max_loop; config++){
            if (WiFi.ready())
            {
                green->off();
                red->off();
                Serial.print("wifi is ready ");
                return true;
            }
            green->toggle();
            red->toggle();
            delay(200);
        }
    }
    
	// prepare loop
	if(mode==UPDATE){ // in update mode we'll try both configs, WIFI_UPDATE_1 and WIFI_UPDATE_2
		max_loop=2;
		wifi_offset=WIFI_UPDATE_1;
	} else {	// in macs mode we'll just try WIFI_MACS
		max_loop=1;
		wifi_offset=WIFI_MACS;
	}
	
    Serial.print("try the SSID from eeprom, stored creds didn't work ");

    // Try to see if EEPROM has an SSID
    for(config=0; config < max_loop; config++){
        if(get_eeprom_wifi_config(wifi_offset+config,&SSID,&pw,&type)){
            // flash 3x green/red to show that I've found a valid WLAN config in EEPROM
            for(int i=0;i<2*3; i++){
				if(mode!=UPDATE){	// MACS mode, toggle both
					green->toggle();
					red->toggle();
				} else {	// UPDATE mode, toggle just one
					red->toggle();
				}
                delay(100);
            }
            delay(1000);

            Serial.print("checking eeprom saved SSID ");
            Serial.println(SSID );

			
            // can we connect, well is this the right SSID 
            if (check_if_SSID_available(SSID, pw, type, mode, green, red) )
            {
                have_found_SSID = true;
                break; // done
            }

        };
    };
    // just do an alternating flash
	green->on();
	red->off();

    if (!have_found_SSID)     {
        // try to use hard coded defaults
        for(config=0; config < max_loop; config++){
            if(get_hard_coded_wifi_config(wifi_offset+config,&SSID,&pw,&type)){
                // flash 3x green/red to show that I've found a valid WLAN config in EEPROM
                for(int i=0;i<2*3; i++){
                    if(mode!=UPDATE){	// MACS mode, toggle both
                        green->toggle();
                        red->toggle();
                    } else {	// UPDATE mode, toggle just one
                        red->toggle();
                    }
                    delay(100);
                }
                delay(1000);
                
                // can we connect, well is this the right SSID 
                if (check_if_SSID_available(SSID, pw, type, mode, green, red) )
                {
                    have_found_SSID = true;

                    if(save_wifi_config(WIFI_MACS, (const char*)SSID, (const char*)pw, type)){
                        Serial.println("saved hardcoded to eeprom");
                        return true;
                    }
                    break; // done
                }

            };
         };

    }

    /////////// ideally have an in-range SSID so, try to connect


	// start with both off, to show a pattern, different from scanning
	green->off();
	red->off();

    if (!have_found_SSID)
    {
        Serial.println("no SSID found ");
        // hmmm - what now 
        green->off();
	    red->off();

        return false;
    }

    for(int i=0; i<200 && !WiFi.hasCredentials(); i++){
        // take new info
        parse_wifi();
        // set info
        delay(50);
		green->toggle();
		red->toggle();
    }
    
	green->off();
	red->off();
    if(WiFi.hasCredentials()){
        // set IP 
        if(mode!=UPDATE){
            // IPAddress myAddress(192,168,188,100+get_my_id());
            // IPAddress gateway(192,168,188,254);
            // IPAddress dns(192,168,188,254);
            IPAddress myAddress(192,168,3,155);
            IPAddress gateway(192,168,3,1);
            IPAddress dns(192,168,3,1);         


            IPAddress netmask(255,255,255,0);

            WiFi.setStaticIP(myAddress, netmask, gateway, dns);
        
            // now let's use the configured IP
            WiFi.useStaticIP();
        } else {
            WiFi.useDynamicIP();
        }
        
        // finally connect
        WiFi.connect(WIFI_CONNECT_SKIP_LISTEN);
        int i=0;
        while(i<200 && !WiFi.ready()){
            delay(75); // wait 59
            i++;
        }
        
        if (WiFi.ready()){
            Serial.println("wifi ready, return true");

        }
        else
        {
            Serial.println("wifi NOT ready, return false");
        }


        for(int i=0;i<2*2; i++){
			if(mode!=UPDATE){	// MACS mode, toggle both
				green->toggle();
				red->toggle();
			} else {	// UPDATE mode, toggle just one
				red->toggle();
			}
            delay(100);
		}
		green->off();
		red->off();
		return true;
    }
    
	green->off();
	red->off();
	return false;
}



// will see if this SSID is in range, if so, configures the WIFI with that SSID, pw and type
bool check_if_SSID_available(String SSID, String pw, int type, int mode, LED *green, LED *red )
{
    char SSID_char[20];
    memset(SSID_char, 0, sizeof(SSID_char));
    FindSSID ssidFinder;

    // now switch to a configuration with one LED on, because well toggle both in the ssidFinder
    green->off();
    red->on();
    
    SSID.toCharArray(SSID_char,20);
    if(ssidFinder.check_SSID_in_range(SSID_char,green,red)){
        // flash 5x green to show that I've found the WLAN and try to connect now
        green->off();
        red->off();
        delay(1000);
        for(int i=0;i<2*5; i++){
            if(mode!=UPDATE){	// MACS mode, toggle both
                green->toggle();
                red->toggle();
            } else {	// UPDATE mode, toggle just one
                red->toggle();
            }
            delay(100);
        }
        //try_backup=false;
        Serial.print("SSID in range so setting crededentials for SSID ");
        Serial.println(SSID);
        WiFi.setCredentials(SSID, pw, type);
        
        return true;
    } else {
        Serial.print("SSID in NOT range for SSID ");
        Serial.println(SSID);
    }
    delay(1000);
    // end with both off, we should have a clean start if we have to loop
    green->off();
    red->off();

    return false;
}

bool is_wifi_connected(){
    //IPAddress gateway(192,168,188,254);
    IPAddress gateway(192,168,3,1);

    if(WiFi.ping(gateway,3)>0){
        return true;
    } else {
        delay(100);
        if(WiFi.ping(gateway,1)>0){
            return true;
        }   
    }

    if (WiFi.ready())
    {
        Serial.print("Odd no ping but is REady, ping disabled on router maybe "); 
        return true; 
    }

    return false;
};


// read data from EEPROM, check them and set them if the check is passed
bool get_eeprom_wifi_config(uint8_t id, String *_SSID, String *_pw, int *_type){
    Serial.println("load wifi config!!");
    uint16_t data_start=0;

    if(id==WIFI_MACS){
        data_start=START_WIFI_MACS;
    } else if(id==WIFI_UPDATE_1){
        data_start=START_WIFI_UPDATE_1;
    } else if(id==WIFI_UPDATE_2){
        data_start=START_WIFI_UPDATE_2;
    } else {
        #ifdef DEBUG_JKW_WIFI
        Serial.println("set wifi unknown id");
        #endif
        return false;
    }
    
    uint8_t SSID[21];
    uint8_t pw[21];
    uint8_t type=0x00;
    uint8_t chk=0x00;
    uint8_t read_char=0x00;
    uint8_t p=0x00;
    
    // read ssid
    bool all_FF=true;
    read_char=0x01; // avoid instand stop
    for(uint8_t i=0; i<20 && read_char!=0x00; i++){
        read_char=EEPROM.read(data_start+i);
        SSID[i]=read_char;
        p=i;
        
        if(read_char!=0xFF){ 
            all_FF=false; 
        }
    }
    
    SSID[p+1]=0x00;
    
    // read pw
    read_char=0x01; // avoid instand stop
    for(uint8_t i=0; i<20 && read_char!=0x00; i++){
        read_char=EEPROM.read(data_start+i+20);
        pw[i]=read_char;
        p=i;
    }
    pw[p+1]=0x00;
    
    type=EEPROM.read(data_start+40);
    chk=EEPROM.read(data_start+41);
    
    
    // a bug in the system can erase all EEPROM info.
    // it is connected to a brown out situation
    // in this case the hole eeprom page is 0xFF
    // the only thing we can do is to return the default 
    // wifi config, which we'll do below
    if(all_FF){
        #ifdef DEBUG_JKW_WIFI
          Serial.println("invalid wifi data FF");
        #endif
        Serial.println("invalid wifi data in eeprom");

        // don't need to verify checksums for this hard coded info so will just return here
        return get_hard_coded_wifi_config( id,  _SSID,  _pw,  _type);
     }
    
    #ifdef DEBUG_JKW_WIFI
    Serial.println("loaded eeprom wifi, data:");
    Serial.print("SSID:");
    Serial.print((const char*)SSID);
    Serial.println(":");
    delay(1000);
    Serial.print("PW:");
    Serial.print((const char*)pw);
    Serial.println(":");
    delay(1000);
    Serial.print("type:");
    Serial.print(type+'0');
    Serial.println(".");
    delay(1000);
    Serial.print("chk:");
    Serial.print(chk);
    Serial.println(".");
    delay(1000);
    #endif
    
    if(!check_wifi_config((const char*)SSID,(const char*)pw,type,chk)){
        
        //#ifdef DEBUG_JKW_WIFI
        //Serial.println("set wifi, data invalid");
        //#endif
        Serial.println("set wifi, data invalid");
        *_SSID="";
        *_pw="";
        *_type=0;
        
        return false;
    }
    
    *_SSID=(const char*)SSID;
    *_pw=(const char*)pw;
    *_type=type;
    //WiFi.setCredentials((const char*)SSID, (const char*)pw, type);
    
    return true;
}

bool get_hard_coded_wifi_config(uint8_t id, String *_SSID, String *_pw, int *_type) {

   // #ifdef DEBUG_JKW_WIFI
        Serial.print("use hard coded wifi ");
   // #endif

    uint8_t SSID[21];
    uint8_t pw[21];
    uint8_t type=0x00;
    uint8_t chk=0x00; // will be ignored now

     // hard coded defaults
    memset(SSID,0x00,21);
    memset(pw,0x00,21);
    
    if(id==WIFI_MACS){
        memcpy(SSID,"macs",4);
        memcpy(pw,"6215027094",10);
        type=3; // wpa2 
        chk=0x17; 

    } else if(id==WIFI_UPDATE_1){
        memcpy(SSID,"ajlokert",8);
        memcpy(pw,"qweqweqwe",9);

        type=3; // wpa2 
        chk=0x60;
    } else if(id==WIFI_UPDATE_2){
        memcpy(SSID,"shop",4);
        memcpy(pw,"abcdefgh",8);
        type=2; // WPA
        chk=0x0E;
    }

    *_SSID=(const char*)SSID;
    *_pw=(const char*)pw;
    *_type=type;

    return true;
}

uint8_t compute_checksum(String SSID,String pw,uint8_t type)
{
   uint8_t checksum=0x00;
    for(uint8_t i=0;i<SSID.length();i++){
        checksum^=SSID.charAt(i);
    }
    for(uint8_t i=0;i<pw.length();i++){
        checksum^=pw.charAt(i);
    }
    checksum^=type;

    return checksum;
}


// checks if the data checksum is correct
bool check_wifi_config(String SSID,String pw,uint8_t type,uint8_t chk){
     
    uint8_t checksum = compute_checksum( SSID, pw, type);

    if(checksum!=chk){
        
        #ifdef DEBUG_JKW_WIFI
            Serial.print("check wifi data, checksum mismatch ");
            Serial.println( checksum);

        #endif
        
        return false;
    }
    
    #ifdef DEBUG_JKW_WIFI
    Serial.println("Checksum ok");
    #endif 
    
    return true;
}

// save the wifi data to eeprom
bool save_wifi_config(uint8_t id,String SSID,String pw,uint8_t type){
    
    #ifdef DEBUG_JKW_WIFI
    Serial.println("save wifi, data:");
    Serial.print("SSID:");
    Serial.print(SSID);
    Serial.println(".");
    Serial.print("PW:");
    Serial.print(pw);
    Serial.println(".");
    Serial.print("type:");
    Serial.print(type);
    Serial.println(".");
    Serial.println(".");
    delay(1000);
    #endif
    Serial.println("save wifi config to EEPROM!!");

    uint8_t  checksum = compute_checksum( SSID, pw, type);
    
    uint16_t data_start=0;
    // set data start, EEPROM adress
    
    if(id==WIFI_MACS){
        data_start=START_WIFI_MACS;
    } else if(id==WIFI_UPDATE_1){
        data_start=START_WIFI_UPDATE_1;
    } else if(id==WIFI_UPDATE_2){
        data_start=START_WIFI_UPDATE_2;
    } else {
        #ifdef DEBUG_JKW_WIFI
        Serial.println("save wifi unknown id");
        #endif
        return false;
    }
    
    // length check
    if(SSID.length()>20 || pw.length()>20){
        #ifdef DEBUG_JKW_WIFI
        Serial.println("save wifi, data to long");
        #endif
        return false;
    }
    
    //save the data
    // ssid
    //FLASH_Unlock();
    for(uint8_t i=0;i<SSID.length() && i<20;i++){
        EEPROM.update(data_start+i+0,SSID.charAt(i));
    }
    if(SSID.length()<20){
        EEPROM.update(data_start+SSID.length()+0,0x00);
    }
    // pw
    for(uint8_t i=0;i<pw.length() && i<20;i++){
        EEPROM.update(data_start+i+20,pw.charAt(i));
    }
    if(pw.length()<20){
        EEPROM.update(data_start+pw.length()+20,0x00);
    }
    // type
    EEPROM.update(data_start+40,type);
    // checksum
    EEPROM.update(data_start+41,checksum);
    //FLASH_Lock();
    
    //Serial.println("done!!");
    return true;
}

// read config from serial port for wifi
bool parse_wifi(){
    if(!Serial.available()){
        return false;
    }
    
    uint8_t in=0x00;
    uint8_t tab_count=0;
    uint8_t SSID[20];
    uint8_t pw[20];
    uint8_t type=0x00;
    uint8_t chk=0x00;
    uint8_t id=0x00;
    uint8_t p=0x00;
    
    
    // buffer is 64 byte (id(1)+<tab>+SSID(20)+<tab>+pw(20)+<tab>+type(1)+<tab>+)=48
    // e.g. 00 09 6d 61 63 73 09 36 32 31 35 30 32 37 30 39 34 09 03 09 
    // e.g. 01 09 61 6a 6c 6f 6b 65 72 74 09 71 77 65 71 77 65 71 77 65 09 03 09 
    // e.g. 02 09 73 68 6f 70 09 61 62 63 64 65 66 67 68 09 02 09 
    Serial.print("Enter wifi info, id=0, tab, ssid, tab, pw, tab, type (2=wpa, 3=wpa2), tab:");
    //Serial.println(Serial.available());
    delay(1000); // give buffer time to fill
    
    while(Serial.available()){
        in=Serial.read();
        //Serial.print("read ");
        //Serial.print(in);
        //Serial.println(".");
        
        
        if(in==0x09){ // which is tab, our delimitter
            if(tab_count==1){
                SSID[p]=0x00;
            } else if(tab_count==2){
                pw[p]=0x00;
            }
            
            p=0x00;
            tab_count++;
            //Serial.print("tab is now ");
            //Serial.println(tab_count);
            
            
            // to identify if a macs unit is present i'll send a "i<tab>" and the unit will responde "MACS"
            if(tab_count==1 && id=='i'){
                tab_count=0;
                Serial.println("MACS");
            }
            
            if(tab_count==4){
                //Serial.println("try to save");
                if(save_wifi_config(id, (const char*)SSID, (const char*)pw, type)){
                    Serial.println("saved");
                    return true;
                } else {
                    Serial.println("error, check checksum, lens");
                    return false;
                }
                tab_count=0;
            }
        } else if(tab_count==0){
            id=in;
        } else if(tab_count==1){
            SSID[p]=in;
            if(p<20){
                p++;
            };
        } else if(tab_count==2){
            pw[p]=in;
            if(p<20){
                
                p++;
            };
        } else if(tab_count==3){
            type=in;
        } else if(tab_count==4){
            chk=in; // ignored now, computed from input
        }
        
    }
    return false;
    //Serial.println("while end");
}
    
    
void listen(system_event_t event){
    if (event==wifi_listen_update){
        WiFi.disconnect();
        WiFi.listen(false); 
    }
    /* else if(event==network_status){
        if(param==network_status_disconnecting){
            WiFi.off();
        }
    }*/
}
 
