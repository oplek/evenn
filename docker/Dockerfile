#Production Environment
FROM ubuntu:groovy-20210325.1

# Install software
RUN apt-get update
RUN apt-get -y install nginx memcached python3 python3-pip
RUN apt-get clean

# Create directories
RUN mkdir /var/www/html/webroot
RUN mkdir /var/www/html/scripts

# Copy in configurations
COPY ./docker/conf/site.conf /etc/nginx/sites-enabled/default
COPY ./docker/scripts/run.sh /var/www/html/run.sh
COPY ./webroot /var/www/html/webroot

WORKDIR /var/www/html

# Listen here
EXPOSE 80

CMD ["/var/www/html/run.sh"]