docker build --tag evenn:latest -f docker/Dockerfile-dev .
pause
rem docker run -d -it -p 8080:80 -e DB_NAME=main -e DB_PASSWD=1234 -v ./webroot:/var/www/html/webroot evenn:latest
docker run -d -it -p 8080:80 -v ./webroot:/var/www/html/webroot evenn:latest
pause