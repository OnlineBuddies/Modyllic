CWD := $(shell pwd)
PHP_INCLUDE_PATH := $(shell echo '<?php echo get_include_path();'|php)
PHP := 'php -d include_path="$(CWD):$(PHP_INCLUDE_PATH)"'
PROVE := prove -r --exec $(PHP)
PACKAGEXML := base.xml
BUILDXML := package.xml
BUILDVAL := $(BUILDXML).validate

default:

.PHONY: test test-verbose package-validate install uninstall clean build-package-xml discover-channel

clean:
	rm $BUILDXML $BUILDXML.validate

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

package-validate: build-package-xml
	pear package-validate $(BUILDXML) | egrep -v 'Analyzing|Validation' > $(BUILDVAL)
	grep ^Warning: $(BUILDVAL) || true
	if grep ^Error: $(BUILDVAL); then exit 1; else exit 0; fi

test: package-validate
	$(PROVE) tests

test-verbose: package-validate
	$(PROVE) -v tests
