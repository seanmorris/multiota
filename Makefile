PROJECT?=muliota
REPO   ?=seanmorris
TARGET ?=dev

-include .env
-include .env.${TARGET}

-include vendor/seanmorris/ids/Makefile

init:
	@ cp -n .env.sample .env 2>/dev/null || true
	@ cp -n .env.${TARGET}.sample .env.${TARGET} 2>/dev/null || true
	@ docker run --rm \
		-v $$PWD:/app \
		-v $${COMPOSER_HOME:-$$HOME/.composer}:/tmp \
		composer install

test:
	./idilic "runTests SeanMorris/Multiota"