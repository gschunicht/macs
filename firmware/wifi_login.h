#include "config.h"

bool set_update_login(LED *green, LED *red);
bool set_macs_login(LED *green, LED *red);
bool set_login(LED *green, LED *red, uint8_t mode);
bool save_wifi_config(uint8_t id,String SSID,String pw,uint8_t type);

bool get_eeprom_wifi_config(uint8_t id, String *_SSID, String *_pw, int *_type);
bool get_hard_coded_wifi_config(uint8_t id, String *_SSID, String *_pw, int *_type);
uint8_t compute_checksum(String SSID,String pw,uint8_t type);

bool check_wifi_config(String SSID,String pw,uint8_t type,uint8_t chk);
bool check_if_SSID_available(String SSID, String pw, int type, int mode, LED *green, LED *red );

bool parse_wifi();
void listen(system_event_t event);
bool is_wifi_connected();
