.PHONY: pint stan rector qa

pint:
	vendor/bin/pint -v

stan:
	vendor/bin/phpstan analyse --memory-limit=1G --configuration=phpstan.neon

rector:
	vendor/bin/rector process --dry-run

qa: pint stan
