CWD := $(shell pwd)
PHP_INCLUDE_PATH := $(shell echo '<?php echo get_include_path();'|php)
PHP := 'php -d include_path="$(CWD):$(PHP_INCLUDE_PATH)"'
PROVE := prove -r --exec $(PHP)

default:

.PHONY: test test-verbose package-validate install uninstall

install:
	pear channel-discover onlinebuddies.github.com/pear ; pear install package.xml

uninstall:
	pear uninstall OnlineBuddies/Modyllic

package-validate:
	@pear package-validate package.xml | egrep -v 'Analyzing|Validation' > pvout ; grep ^Warning: pvout ; if grep ^Error: pvout; then rm pvout; exit 1; else rm pvout; exit 0; fi

test: package-validate
	$(PROVE) test

test-verbose: package-validate
	$(PROVE) -v test
