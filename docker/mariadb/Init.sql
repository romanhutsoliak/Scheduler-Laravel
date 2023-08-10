

-- data
	
-- schedule_api will be created in docker-compose.yml
-- CREATE DATABASE schedule_api;
-- GRANT ALL PRIVILEGES ON schedule_api.* TO 'docker'@'%';

-- testing
CREATE DATABASE testing_schedule_api;

GRANT ALL PRIVILEGES ON testing_schedule_api.* TO 'docker'@'%';

FLUSH PRIVILEGES;

