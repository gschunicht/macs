#define DEBUG_JKW_MAIN 0

// used for wifi connected or not an a 200 from the web service
#define NOT_CONNECTED  0
#define CONNECTED  1

void set_connected(int status);
void set_wifi_connected(int status, bool force);
bool read_EEPROM();
void goto_update_mode();
bool update_ids(bool force);
uint32_t set_relay_state(int8_t input);
bool validate_tag(uint8_t *buf,uint32_t *tag);

void create_report(uint8_t event,uint32_t badge,uint32_t extrainfo);
bool tag_found(uint8_t *buf,uint32_t *tag);
bool access_test(uint32_t tag);
bool check_db_for_one_tag(uint32_t currentTag);
