CREATE TABLE users (
	ID SERIAL PRIMARY KEY,
	phone_num bigint UNIQUE,
	carrier varchar(16),
	password varchar(16) UNIQUE,
	text_consent bool, 
	is_active bool,
	subscribe_ts timestamp
)

CREATE TABLE diaries (
	ID SERIAL PRIMARY KEY,
	password varchar(16),
	diary_ts timestamp,
	local_date varchar(16),
	score decimal(4,1),
	comment varchar(255)
)
