CWD := $(shell pwd)
PHP_INCLUDE_PATH := $(shell echo '<?php echo get_include_path();'|php)
PHP := 'php -d include_path="$(CWD)/lib:$(PHP_INCLUDE_PATH)"'
PROVE := prove -r --exec $(PHP)
PACKAGEXML := base.xml
BUILDXML := package.xml

default:

.PHONY: test test-verbose install uninstall clean build-package-xml discover-channel

clean:
	rm $BUILDXML

build-package-xml:
	php build-package-xml $(PACKAGEXML) $(BUILDXML)

discover-channel:
	pear channel-discover onlinebuddies.github.com/pear || true

install-build-prereqs: discover-channel
	pear install OnlineBuddies/PEAR_PackageFileManager_Gitrepoonly

install: build-package-xml discover-channel uninstall
	pear install $(BUILDXML)

uninstall:
	pear uninstall OnlineBuddies/Modyllic

package: build-package-xml
	pear package $(BUILDXML)

test:
	$(PROVE) test

test-verbose:
	$(PROVE) -v test
