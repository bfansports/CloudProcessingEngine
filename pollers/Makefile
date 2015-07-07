COMPOSER = ./composer.phar
COMPOSER_CONF = ./composer.json

.PRECIOUS: .env.%
.PHONY: all 

# Rules for building environment

all: vendor/autoload.php

# Rules for generating files

$(COMPOSER):
	curl -sS https://getcomposer.org/installer | php

vendor/autoload.php: $(COMPOSER) $(COMPOSER_CONF)
	$(COMPOSER) self-update
	$(COMPOSER) install -o --prefer-dist
