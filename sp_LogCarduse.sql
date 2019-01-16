BEGIN

DECLARE vUser_id INT;
DECLARE vMach_id INT;
DECLARE txtEvent VARCHAR(20);
DECLARE unlocktime INT;
DECLARE unixNow INT;

SET unixNow = (UNIX_TIMESTAMP(NOW()));
SET vUser_id = (SELECT id FROM user WHERE badge_id = CardNum LIMIT 1);
SET vMach_id = (SELECT id FROM mach WHERE mach_nr = Mach_NR LIMIT 1);
SET txtEvent = (CASE CardAction WHEN -1 THEN 'Denied' WHEN 0 THEN 'Locked' WHEN 1 THEN 'Unlocked' END);

CASE CardAction
	WHEN 0 THEN SET unlocktime = (SELECT `timestamp` FROM log WHERE machine_id = vMach_id AND event = 'Unlocked' AND user_id = vUser_id ORDER BY `timestamp` DESC LIMIT 1);

	INSERT INTO log (`timestamp`,`user_id`,`machine_id`,`event`, `usage`) VALUES (unixNow,vUser_id,vMach_id,txtEvent, (unixNow - unlocktime));
ELSE 
	INSERT INTO log (`timestamp`,`user_id`,`machine_id`,`event`, `usage`)VALUES (unixNow,vUser_id,vMach_id,txtEvent,0);
END CASE;
END


CALL sp_LogCardUse('12345678',1,9)

select * from log where `user_id` = 291 and `machine_id` = 33 AND `usage` = 0 ORDER BY `timestamp` DESC LIMIT 1


select * from log where machine_id = 33 and event = 'Unlocked' order by timestamp desc limit 1