#Production Environment
FROM ubuntu:groovy-20210325.1

# Install software
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update
RUN apt-get -y install nginx memcached libmemcached-tools php php-memcached php-curl php-json php-mbstring php-dom php-yaml cron
RUN apt-get clean
RUN touch /var/log/cron.log

# Create directories
RUN rm -Rf /var/www/html/*
RUN mkdir /var/www/html/webroot
RUN mkdir /var/www/html/scripts
RUN mkdir /var/www/html/engine

# Set up crontab
COPY ./docker/conf/engine-cron /etc/cron.d/engine-cron
RUN chmod 0644 /etc/cron.d/engine-cron
RUN crontab /etc/cron.d/engine-cron

# Copy in configurations
COPY ./docker/conf/site.conf /etc/nginx/sites-enabled/default
COPY ./docker/scripts/run.sh /var/www/html/run.sh
COPY ./webroot /var/www/html/webroot
COPY ./engine /var/www/html/engine

WORKDIR /var/www/html
#VOLUME /var/www/html/webroot

# Listen here
EXPOSE 80

CMD ["/var/www/html/run.sh"]