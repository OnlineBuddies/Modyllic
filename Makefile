CWD := $(shell pwd)
PHP_INCLUDE_PATH := $(shell echo '<?php echo get_include_path();'|php)
PHP := 'php -d include_path="$(CWD):$(PHP_INCLUDE_PATH)"'
PROVE := prove -r --exec $(PHP)

default:

.PHONY: test install

install:
	pear install package.xml

uninstall:
	pear uninstall __uri/Modyllic

test: check
	$(PROVE) test

test-verbose: check
	$(PROVE) -v test

check:
	@[ -f testlib/testmore.php ] || (echo "You must initialize submodules with 'git submodule update --init' first"; exit 1)
