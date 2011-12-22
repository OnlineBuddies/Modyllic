default:

.PHONY: test

test:
	prove --exec 'php -d include_path=lib' --ext '.phpt' test
