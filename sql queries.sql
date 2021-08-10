CREATE TABLE users (
	ID SERIAL PRIMARY KEY,
	phone_num bigint UNIQUE,
	carrier varchar(16),
	password varchar(16) UNIQUE,
	text_consent bool, 
	is_active bool,
	verified_num bool,
	subscribe_ts timestamp
)

CREATE TABLE diaries (
	ID SERIAL PRIMARY KEY,
	password varchar(16),
	diary_ts timestamp,
	local_date varchar(16),
	local_ts timestamp,
	score decimal(4,1),
	comment varchar(255)
)

ALTER TABLE diaries
ADD COLUMN local_ts timestamp;

UPDATE diaries
SET local_ts = TO_DATE(local_date, 'DD Mon YYYY');

INSERT INTO diaries (local_ts) VALUES (to_timestamp('10/14/1983, 22:40:10', 'MM/DD/YYYY, HH24:MI:SS'))

//run teh above. change hidden date field, change the insert query, change display query. see if it works; if so,  run query below

ALTER TABLE diaries
DROP COLUMN local_date