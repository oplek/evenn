docker build --tag evenn:latest -f docker/Dockerfile .
pause
docker run -d -t -m 256m -p 8080:80 -e DB_NAME=main -e DB_PASSWD=1234 evenn:latest
pause