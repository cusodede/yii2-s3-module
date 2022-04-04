build:
	@cp -nfr .env.example .env
	@cp -nfr ./tests/.env.example ./tests/.env
	docker-compose pull
	docker-compose build --pull

test: test80 test81
test80:
	docker-compose build --pull php80
	docker-compose run php80 vendor/bin/codecept run -v --debug
	docker-compose down

test81:
	docker-compose build --pull php81
	docker-compose run php81 vendor/bin/codecept run -v --debug
	docker-compose down

clean:
	docker-compose down
	rm -rf tests/runtime/*
	rm -rf composer.lock
	rm -rf vendor/

clean-all: clean
	rm -rf tests/runtime/.composer*
