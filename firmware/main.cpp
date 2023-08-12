/* LED Patter
* === STARTUP ===
* red,green,red,green  -> i give you 10 sec to connect to me, before I start
* red on, green on -> I'm trying to connect to my server
*
* === UPDATE MODE ===
* green flashes 3 times (10Hz) -> I've found valid WiFi config 1 in EEPROM and now I'm scannning for it! 
* green flashes 5 times (10Hz) -> I've found WiFi config 1 in scan results and try to conenct now! 
* red flashes 3 times (10Hz) -> I've found valid WiFi config 2 in EEPROM and now I'm scannning for it! 
* red flashes 5 times (10Hz) -> I've found WiFi config 2 in scan results and try to conenct now! 
* red and green simulatnious, 2 fast blinks (10Hz) -> connected to wifi!
* red,green,red,greed fast (10sec, 20Hz) -> Waiting for the Wifi to save credentials AND give you time to add new credentials via serial
* red and green blink simultainous -> I'm ready for an update
* 
* === MACS MODE (status can be combined) ===
* red and green simulatnious, flashes 3 times (10Hz) -> I've found valid WiFi config in EEPROM and now I'm scannning for it! 
* red and green simulatnious, flashes 5 times (10Hz) -> I've found WiFi config in scan results and try to conenct now! 
* red and green simulatnious, 2 fast flashes (10Hz)  -> connected to wifi!
* red blinking -> no connection to the MACS Server
* red solid -> card rejected
* green blinking -> connected to the MACS Server
* green solid -> card accepted
*/

// This #include statement was automatically added by the Particle IDE.
#include "application.h"
#include "stdint.h"
#include "config.h"
#include "application.h"
#include "Particle.h"
#include "main.h"
#include "SPI.h"

// network the web server
//IPAddress HOSTNAME(192,168,188,23);
IPAddress HOSTNAME(192,168,3,102);

uint32_t v=20230514;

uint8_t keys_available=0; // ho many keys are in the array in memory 
uint32_t keys[MAX_KEYS];


uint8_t currentTagBuf[TAGSTRINGSIZE];
uint8_t currentTagIndex=0;
uint8_t connected=0;
uint32_t currentTag=-1;

uint8_t current_relay_state=RELAY_DISCONNECTED;
uint8_t id=-1; //255, my own id
uint8_t tagInRange=0;
system_tick_t last_key_update_ms=0;
system_tick_t last_badge_fallback_update_ms=0;
system_tick_t last_server_request=0;
system_tick_t relay_open_timestamp=0;
system_tick_t last_tag_read_ms=0;

LED db_led(DB_LED_AND_UPDATE_PIN,DB_LED_DELAY,1,1); // weak + inverse
LED red_led(RED_LED_PIN,RED_LED_DELAY,0,0);
LED green_led(GREEN_LED_PIN,GREEN_LED_DELAY,0,0);

BACKUP b; // report backup

SYSTEM_MODE(MANUAL);// do not connect on your own

// http server
HttpClient http; // Headers currently need to be set at init, useful for API keys etc.
http_header_t headers[] = {     { "Accept" , "*/*"},     { NULL, NULL } }; // NOTE: Always terminate headers will NULL
http_request_t request;
http_response_t response;


///

boolean cardPresent = false;

SerialLogHandler logHandler;


//////////////////////////////// SETUP ////////////////////////////////
void setup() {
    //FLASH_Lock();
    // set adress pins
    for(uint8_t i=10; i<=MAX_JUMPER_PIN+10; i++){   // A0..7 is 10..17, used to read my ID
       pinMode(i,INPUT_PULLUP);
    }
    pinMode(RELAY_PIN,OUTPUT);          // driver for the relay
    pinMode(TAG_IN_RANGE_INPUT,INPUT);

    Log.info("Logging Startup");
    
    // register system handles
    System.on(wifi_listen, listen);
    //System.on(network_status, listen);
    
    // antenna selection
    pinMode(ANTENNA_PIN,INPUT_PULLUP);
    
    if(digitalRead(ANTENNA_PIN)==LOW){ // high == no jumper to pull it down 
        WiFi.selectAntenna(ANT_EXTERNAL);
    } else {
        WiFi.selectAntenna(ANT_AUTO);
    }
    
    // the db led is a weak and inverterted LED on the same pin as the update_input, this will set the pin to input_pullup anyway //
    pinMode(DB_LED_AND_UPDATE_PIN,INPUT_PULLUP);
    Serial1.begin(9600); // card reader
    Serial.begin(9600);  // debug out

    // setup https client
    request.ip = HOSTNAME;
    request.port = HOSTPORT;
 
    
    // read mode to starting with
    if(digitalRead(DB_LED_AND_UPDATE_PIN)){
        
        // ############ MACS MODUS ############ // 
        Log.info("- MACS -");
        
        red_led.on();
        green_led.on();
        
        set_connected(NOT_CONNECTED); // init as not connected
        if(update_ids(true)){ // true = force update, update_ids will initiate the connect
            set_connected(CONNECTED);
        } else {
            set_wifi_connected(NOT_CONNECTED,true); // force LED update for not connected
            read_EEPROM();
        }
        // ############ MACS MODUS ############ // 
        
    } else {
        Log.info("- MACS - Update");

       goto_update_mode();
    }
}
//////////////////////////////// SETUP ////////////////////////////////

 // ############ UPDATE MODUS ############ // 
void goto_update_mode(){
    connected=0;
    red_led.on();
    green_led.on();
    db_led.on();
    
    Log.info("- Cloud -");

    
    // satrt loop that will set wifi data and connect to cloud,
    // and if anything fails start again, until there is an update
    while(1){
        // set_update_login will return true, if we've read a valid config from 
        // the EEPROM memory AND that WIFI was in range AND the module has saved the login
        if(set_update_login(&green_led,&red_led)){
            Log.info("set update login done");

            Particle.connect();
            uint8_t i=0;

            // backup, if connect didn't work, repeat it
            while(!WiFi.ready()){
                Log.info("wait wifi .");
               
                Particle.connect();
            }

            // stay in update mode forever
            while(WiFi.ready()){
                if(i!=millis()/1000){
                    
                    if(Particle.connected()){
                        // as soon as we are connected, swtich to blink mode to make it visible
                        if(!connected){
                            red_led.blink();
                            green_led.blink();
                            db_led.blink();
                            connected=1;
                        } else {
                            Particle.process();
                        }
                        
                        // check incomming data, unlikely here, because at this point we are already connected to an update wifi
                        parse_wifi();
                        
                        // keep blinking
                        red_led.check();
                        green_led.check();
                        db_led.check();
                        
                        Log.info("Photon connected to wifi");

                        
                    } else {
                        
                        Log.warn("Photon NOT connected to wifi");
                        
                        // constant on == not yet connected
                        red_led.on();
                        green_led.on();
                        db_led.on();
                    }
                    i=millis()/1000;
                } // i!=millis()/1000
                delay(200); // don't go to high as blink will look odd
            } // end while(WiFi.ready())
            // reaching this point tells us that we've set the wifi login, tried to connect but lost the connection, as the wifi is not (longer) ready
        } // if(set_update_login())
    } // end while(1)
    // ############ UPDATE MODUS ############ // 
}
// ############ UPDATE MODUS ############ // 

//////////////////////////////// MAIN LOOP ////////////////////////////////
// woop woop main loop
void loop() {
    // check if we found a tag (just inserted)

    if(tag_found(currentTagBuf,&currentTag)){
        if(currentTag == UPDATECARD){
            goto_update_mode();
        }


        // if we found a tag, test it
        // if it works close relay,
        // if not - ask the server for an update and try again
        uint8_t tries=2; // two tries to check the card
        while(tries){

            int taginserted = digitalRead(TAG_IN_RANGE_INPUT);
            Log.info("card inserted state  %d ", taginserted);

            if (taginserted == 0)
            {
                // if nothing here no reason to check access
                tries = 0;
                continue;
            }

            // compares known keys, returns true if key is known
            if(access_test(currentTag)){
                set_relay_state(RELAY_CONNECTED);
                tries=0;
                // takes long
                create_report(LOG_RELAY_CONNECTED,currentTag,0);
                // 1. assuming that we are NOT connected, we reached this point and the create_report has success to reconnet than it will call set_connected()
                // this will turn red off (which is fine (was blinking = not connected)) and green to blink (ok), so we have to override it
                // 2. assuming that we are NOT connected, we reached this point and the create_report has NO success to reconnet than it will not call set_connected()
                // the red will keep blinking (ok) but we still want to show that this card was good, turn green on
                // 3. assuming that we are connected, we reached this point then create_report will not try to reconnect and the report is send just fine
                // the red will be off anywa (ok), we want to show that this card was good, turn green on
                // 4. assuming that we are connected, we reached this point then create_report will not try to reconnect, but the report failed, create_report will set us to not conneted
                // the red will be blinkin (ok), we want to show that this card was good, turn green on
                set_wifi_connected(connected,1); // force to resume LED pattern
                green_led.on();
                tries = 0; // done
                break; 
            } 

            tries--; 

            update_ids(false); // unforced update
            // above will just look to see if there is a recent update, eg new user/ access granted
            // check if this one badge is in DB, a fallback if errors.

            // if we have a card that is not known to be valid we should maybe check our database
            Log.info("Card Key not valid, requesting update from server");

            if (check_db_for_one_tag(currentTag))
            {
                set_relay_state(RELAY_CONNECTED);
                tries=0;
                // takes long
                create_report(LOG_RELAY_CONNECTED,currentTag,0);
                set_wifi_connected(connected,1); // force to resume LED pattern
                green_led.on();
                break;
            }
                   
            Log.warn("card key still not valid / found in DB or stored data");

            if (tries == 0 )  {              // takes long
                create_report(LOG_LOGIN_REJECTED,currentTag,0);
                red_led.on();
            }
            
        }
    }
    


    
    // card moved away
    // if Serial has avaioable chars, it means that TAG IN RANGE had to be low at some point, its just a new card there now!
    if(digitalRead(TAG_IN_RANGE_INPUT)==0 && current_relay_state==RELAY_CONNECTED) { 

        int taginserted = digitalRead(TAG_IN_RANGE_INPUT);

        Log.info("card key state  %d", taginserted);
        
        // maybe flush if no tag ??
        //while(Serial1.available()){
        //       uint8_t temp=Serial1.read();
        //}
        // open the relay as soon as the tag is gone
        if(current_relay_state==RELAY_CONNECTED){
            uint32_t open_time_sec=set_relay_state(RELAY_DISCONNECTED);

            // last because it takes long
            create_report(LOG_RELAY_DISCONNECTED,currentTag,open_time_sec);
        }
        
        set_wifi_connected(connected,1); // force to resume LED pattern
    
        currentTag=-1;      // reset current user
        currentTagIndex=0;  // reset index counter for incoming bytes
        
    }
    
    // time based update the storage from the server (every 10 min?) 
    if((last_key_update_ms+DB_UPDATE_TIME_MS)<millis()){
        update_ids(false);  // unforced upate
    }
    
    
    // see if we should switch off the leds by now
    db_led.check();
    red_led.check();
    green_led.check();
    parse_wifi();
}
//////////////////////////////// MAIN LOOP ////////////////////////////////


//////////////////////////////// ACCESS TEST ////////////////////////////////
// callen from main loop as soon as a tag has been found to test if it matches one of the saved keys
bool access_test(uint32_t tag){

    Log.info("Check tag %lu value read from reader there are %d keys in memory ", tag, keys_available);

    for(uint16_t i=0;i<MAX_KEYS && i<keys_available; i++){
        
        if(keys[i]==tag){

            Log.info("Card Key found in memory");
    
            return true;
        }
    }
    
    return false;
}
//////////////////////////////// ACCESS TEST ////////////////////////////////

//////////////////////////////// DRIVE THE RELAY ////////////////////////////////
// hardware controll, writing to the pin and log times
uint32_t set_relay_state(int8_t input){
    if(input==RELAY_CONNECTED){

        Log.info("Connecting relay!");
        
        digitalWrite(RELAY_PIN,HIGH);
        current_relay_state=RELAY_CONNECTED;
        relay_open_timestamp=millis()/1000;

        green_led.on();

        return relay_open_timestamp;
    } else {

        Log.info("Disconnecting relay!");
        
        digitalWrite(RELAY_PIN,LOW);
        current_relay_state=RELAY_DISCONNECTED;
        green_led.off();

        return ((millis()/1000) - relay_open_timestamp);
    }
}
//////////////////////////////// DRIVE THE RELAY ////////////////////////////////

//////////////////////////////// SCAN FOR TAG ON SERIAL ////////////////////////////////
// returns true if tag found, does the UART handling
bool tag_found(uint8_t *buf,uint32_t *tag){
    uint8_t temp;
    

    while(Serial1.available()){
        Log.info("Check tag, read serial1 port !");
        
        // if we haven't received input for a long time, make sure that we writing to pos 1
        if(abs((int)(millis()-last_tag_read_ms))>(int)100){
            currentTagIndex=0;
        }
        last_tag_read_ms=millis();
        
        // read and store
        temp=Serial1.read();
        buf[currentTagIndex]=temp;
        currentTagIndex=(currentTagIndex+1)%TAGSTRINGSIZE;

        if (temp > 0)
        {
            Log.info("Check tag serial1 port! serial1 card reader read %d", temp);
        }
        
        if(currentTagIndex==0){
            //Serial.flush(); // flush it, as we just want to read this tag
            // according to the ref of particle, flush is not implemented
            // and there understanding is different after all
            // will manual flushing the input
            while(Serial1.available()){
                temp=Serial1.read();

                if (temp > 0) {
                    Log.info("serial1 read flush %d ", temp);
                }
 
            }
            
            return validate_tag(buf,tag);
        };
    }
    return false;
}
//////////////////////////////// SCAN FOR TAG ON SERIAL ////////////////////////////////

//////////////////////////////// VERIFY CHECKSUM FOR TAG ////////////////////////////////
// just check if the data are corrumpeted or equal the checksum 
// and convert them to the correct oriented unsigned long
bool validate_tag(uint8_t *buf,uint32_t *tag){
    uint8_t expected=0;
    for(uint8_t i=0;i<TAGSTRINGSIZE-1;i++){
        expected^=buf[i];
    }

    if(expected==buf[TAGSTRINGSIZE-1]){
        // checksum correct, flip data around to get the uint32_t
        for(uint8_t i=0;i<TAGSTRINGSIZE-1;i++){
            *tag=(*tag<<8)+buf[i];
        };
        // avoid noise - if tag is 0 move on, it can't be valid
        if (*tag > 0)
        {
            Log.info("Have card key tag! %lu", *tag);

            return true;
        }

    }

    return false;
}
//////////////////////////////// VERIFY CHECKSUM FOR TAG ////////////////////////////////

//////////////////////////////// READ ID's FROM EEPROM ////////////////////////////////
// if we are running offline
bool read_EEPROM(){
    
    Log.info("-- This is EEPROM read --");
    
    uint8_t temp;
    uint16_t num_keys=0;
    uint16_t num_keys_check=0;
    
    temp=EEPROM.read(KEY_NUM_EEPROM_HIGH);
    num_keys=temp<<8;
    temp=EEPROM.read(KEY_NUM_EEPROM_LOW);
    num_keys+=temp;      

    Log.info("# of keys = %d", num_keys);   
    
    temp=EEPROM.read(KEY_CHECK_EEPROM_HIGH);
    num_keys_check=temp<<8;
    temp=EEPROM.read(KEY_CHECK_EEPROM_LOW);
    num_keys_check+=temp;
    
    
    if(num_keys_check==num_keys+1){
        keys_available=num_keys;
        for(uint16_t i=0;i<num_keys;i++){
            temp=EEPROM.read(i*4+0);
            keys[i]=temp<<24;
            temp=EEPROM.read(i*4+1);
            keys[i]+=temp<<16;
            temp=EEPROM.read(i*4+2);
            keys[i]+=temp<<8;
            temp=EEPROM.read(i*4+3);
            keys[i]+=temp;

            Log.info("Read key %d = %lu from eeprom", i, keys[i]);

        }
    }

    return true;
}

// ok could not find the tag in the main array or read from eeprom
// There were some buffer issues, see client.h 
// This is a fallback that will force read one tag from the DB service
// called if all else fails, returns if there is a hit
bool check_db_for_one_tag(int currentTag)
{
    if (currentTag <= 0) {
        return false;
    }

    // Let's not do this too often though
    if((last_badge_fallback_update_ms+MIN_UPDATE_TIME_MS) > millis()   ){
        
        Log.warn("check_db_for_one_tag read skipped, too frequent %lu now %lu ", last_badge_fallback_update_ms,  millis()  );
        
        return false;
    }

    Log.info("check_db_for_one_tag key tag not in memory, trying direct a DB service call");
    
    // well need a connection too
    if(!is_wifi_connected()){
        
        Log.warn("no wifi connection, can't make DB check for tag, tag wasn't found in memory");
        
        if(!set_macs_login(&green_led,&red_led)){
            set_wifi_connected(NOT_CONNECTED,true);
            return false;
        }
    }

    request.path="/m2m.php?v="+String(v)+"&mach_nr="+String(get_my_id())+"&tag="+currentTag;
       
    green_led.on();
    red_led.on();

    // Get request
    http.get(request, response, headers);
    int statusCode = response.status;
    green_led.resume();
    red_led.resume();

    // check length
    if(response.body.length()==0 || statusCode != 200){
        db_led.off(); // turn the led off
        
        Log.info("Empty http response or not HTTP OK for single tag check");
    }

    Log.info("Single tag check response body is [%s] ", response.body.c_str());

    // connection looks good
    set_wifi_connected(CONNECTED,true); // force update LEDs as the reconnect might have overwritten the LED mode
    uint32_t current_key_pos=0;  // not really used - yet
    uint32_t returned_key=0;

    // currently will not append to the in memory array.

    for(uint16_t i=0;i<response.body.length();i++){
        if(response.body.charAt(i)==','){

            // find end of array... 
            if(current_key_pos<MAX_KEYS){
                Log.info("check_db_for_one_tag key return from DB server  %lu actual tag is %d", returned_key, currentTag);

                // check match here..
                if (currentTag == returned_key) {
                    Log.info("check_db_for_one_tag Found key tag in response, yay");

                    return true;
                }
                returned_key = 0; // next - if any 
            }
        } else if(response.body.charAt(i)>='0' && response.body.charAt(i)<='9') { // zahl
            returned_key=returned_key*10+(response.body.charAt(i)-'0');
            Log.info("check_db_for_one_tag parsing %lu actual tag is %d", returned_key, response.body.charAt(i));

        }
    }

    last_badge_fallback_update_ms = millis();
    
    return false; 

}


//////////////////////////////// READ ID's FROM EEPROM ////////////////////////////////

//////////////////////////////// UPDATE ID's ////////////////////////////////
// sends a request to the  server, this server should be later changed to 
// be the local Raspberry pi. It will call the get_my_id() function
// return true if http request was ok
// false if not - you might want to set a LED if it returns false
bool update_ids(bool forced){
    system_tick_t now;

    now = millis();
    // avoid flooding
    if((last_key_update_ms+ MIN_UPDATE_TIME_MS) > now && last_key_update_ms>0){

        Log.warn("db service read blocked, too frequent:updateIDS, last ms %lu now %lu ",last_key_update_ms, now);
        
        return false;
    }
    
    if(!is_wifi_connected()){        

        Log.warn("no ping, wifi not connected");
        
        if(!set_macs_login(&green_led,&red_led)){
            set_wifi_connected(NOT_CONNECTED,true);
            return false;
        }
    }


    db_led.on(); // turn the led on
    
    // request data
    request.path="/m2m.php?v="+String(v)+"&mach_nr="+String(get_my_id())+"&forced=";
    if(forced){
        request.path=request.path+"1";
    } else {
        request.path=request.path+"0";
    }
        
    green_led.on();
    red_led.on();    
    
    // Get request
    http.get(request, response, headers);
    int statusCode = response.status;
    green_led.resume();
    red_led.resume();

    Log.info("DB web server call took %lu ms, status %d request: %s body %s", (millis()-now), statusCode, request.path.c_str(), response.body.c_str());
    
   
    // check status
    if(statusCode!=200){
        db_led.off(); // turn the led off
        set_wifi_connected(NOT_CONNECTED, true);

        Log.warn("No response from server");
        
        return false;
    }

    // check length
    if(response.body.length()==0){
        db_led.off(); // turn the led off
        
        Log.info("Empty response");
    }

    // connection looks good
    set_wifi_connected(CONNECTED, true); // force update LEDs as the reconnect might have overwritten the LED mode
    
    // check if we've received a "no update" message from the server
    // if we are unforced we'll just leave our EEPROM as is.
    // otherweise we'll go on
    //Serial.println("response length:");
    //Serial.println(response.length());
    //Serial.print(response.charAt(0));
    //Serial.println(response.charAt(1));
    
    if(!forced && response.body.length()>=2){
        if(response.body.charAt(0)=='n' && response.body.charAt(1)=='u'){
            // we received a 'no update'

            Log.info("No update received");
         
            b.try_fire();
            //db_led.off(); // turn the led off
            return true;
        }
    }
        
    last_key_update_ms=now;
    
    // clear all existing keys and then, import keys from request
    keys_available=0;
    uint16_t current_key_pos=0;
    for(uint16_t i=0;i<sizeof(keys)/sizeof(keys[0]);i++){
        keys[i]=0;
    }

    //FLASH_Unlock();
    for(uint16_t i=0;i<response.body.length();i++){
 
        if(response.body.charAt(i)==','){
            if(current_key_pos<MAX_KEYS){

                Log.info("write key: %u" , current_key_pos*4+3);

                // store to EEPROM
                EEPROM.update(current_key_pos*4+0, (keys[current_key_pos]>>24)&0xff);
                EEPROM.update(current_key_pos*4+1, (keys[current_key_pos]>>16)&0xff);
                EEPROM.update(current_key_pos*4+2, (keys[current_key_pos]>>8)&0xff);
                EEPROM.update(current_key_pos*4+3, (keys[current_key_pos])&0xff);
            
                current_key_pos++;
            }
        } else if(response.body.charAt(i)>='0' && response.body.charAt(i)<='9') { // zahl
            keys[current_key_pos]=keys[current_key_pos]*10+(response.body.charAt(i)-'0');
        }
    }
    keys_available=current_key_pos;
    
    // log number of keys to the eeprom
  
    EEPROM.update(KEY_NUM_EEPROM_HIGH,(keys_available>>8)&0xff);
    EEPROM.update(KEY_NUM_EEPROM_LOW,(keys_available)&0xff);
    // checksum
    

    EEPROM.update(KEY_CHECK_EEPROM_HIGH,((keys_available+1)>>8)&0xff);
    EEPROM.update(KEY_CHECK_EEPROM_LOW,((keys_available+1))&0xff);
    //FLASH_Lock();    

    Log.info("Total received keys for my id(%d) available %d", get_my_id(), keys_available);


    #ifdef DEBUG_JKW_MAIN_SETUP
    for(uint16_t i=0;i<keys_available;i++){
        Serial.print("Valid Database Key Nr ");
        Serial.print(i+1);
        Serial.print(": ");
        Serial.print(keys[i]);
        Serial.println("");
    };
    #endif
    
    b.try_fire(); // try to submit old reports
    db_led.off(); // turn the led off
    return true;
}
//////////////////////////////// UPDATE ID's ////////////////////////////////

//////////////////////////////// CREATE REPORT ////////////////////////////////
// create a log entry on the server for the action performed
void create_report(uint8_t event,uint32_t badge,uint32_t extrainfo){
    if(!fire_report(event, badge, extrainfo)){
        b.add(event,badge,extrainfo);
    } else {
        b.try_fire();
    }
}
    
bool fire_report(uint8_t event,uint32_t badge,uint32_t extrainfo){
    bool ret=true;
    system_tick_t now;

    if(!is_wifi_connected()){
    
        Log.warn("no wifi ping, cant report");

        
        if(set_macs_login(&green_led,&red_led)){
            set_wifi_connected(CONNECTED, true); // this could potentially destroy our LED pattern? TODO
        } else {
            set_wifi_connected(NOT_CONNECTED, true);
            return false; // pointless to go on
        }
    }
    
    db_led.on(); // turn the led on
    if(event==LOG_RELAY_CONNECTED){
        request.path = "/history.php?logme&badge="+String(badge)+"&mach_nr="+String(get_my_id())+"&event=Unlocked";
    } else if(event==LOG_LOGIN_REJECTED){
        request.path = "/history.php?logme&badge="+String(badge)+"&mach_nr="+String(get_my_id())+"&event=Rejected";
    } else if(event==LOG_RELAY_DISCONNECTED){
        request.path = "/history.php?logme&badge="+String(badge)+"&mach_nr="+String(get_my_id())+"&event=Locked&timeopen="+String(extrainfo);
    } else if(event==LOG_NOTHING){
        request.path = "/history.php";
    } else {
        return false;
    }
    
    Log.info("calling: %s", request.path.c_str());

    now = millis();
    green_led.on();
    red_led.on();
    http.get(request, response, headers);
    int statusCode = response.status;
    green_led.resume();
    red_led.resume();
    
    
    if(statusCode==200){
        set_wifi_connected(CONNECTED, true);
    } else if(statusCode!=200){
        set_wifi_connected(NOT_CONNECTED, true);
        ret=false;
    }
    
    Log.info("report db request took %lu ms", millis()-now );
    
    db_led.off(); // turn the led off
    return ret;
}
//////////////////////////////// CREATE REPORT ////////////////////////////////

//////////////////////////////// SET CONNECTED ////////////////////////////////
// set the LED pattern 
void set_connected(int status){
    set_wifi_connected(status,false);
};

// Connected to wifi and getting server responses 
void set_wifi_connected(int status, bool force){
    if(status==CONNECTED && (connected==NOT_CONNECTED || force)){
        connected=CONNECTED;
        green_led.blink();
        red_led.off();
    } else if(status==NOT_CONNECTED && (connected==CONNECTED || force)){
        connected=NOT_CONNECTED;
        green_led.off();
        red_led.blink();
    }
}

//////////////////////////////// SET CONNECTED ////////////////////////////////

//////////////////////////////// GET MY ID ////////////////////////////////
// shall later on read the device jumper and return that number
// will only do the interation with the pins once for performance
uint8_t get_my_id(){
    if(id==(uint8_t)-1){
        id=0;        

        Log.info("ID never set, reading");

        for(uint8_t i=10+MAX_JUMPER_PIN; i>=10; i--){   // A0..7 is 10..17
            id=id<<1;
            if(!digitalRead(i)){
                id++;
            };
        }
        
        Log.info(" id for this device as %d ", id);
        
    }
    return id;
}
//////////////////////////////// GET MY ID ////////////////////////////////


