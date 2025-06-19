serverUp:
	php -S localhost:8000

serverDown:
	kill $$(lsof -ti tcp:8000) 2>/dev/null || true

zip:
	cd ../ ; zip -r web.zip web/*  -x web/nbproject/\*

index:
	@php index.php > index.html
