docker build --tag evenn:0.0.1 -f docker/Dockerfile-dev .
pause
docker run -d -it -m 256m -p 8080:80 -e DB_NAME=main -e DB_PASSWD=1234 -v %CD%\webroot:/var/www/html/webroot -v %CD%\engine:/var/www/html/engine evenn:0.0.1
pause