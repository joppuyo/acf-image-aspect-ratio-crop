# https://github.com/conetix/docker-wordpress-wp-cli/blob/master/Dockerfile

FROM wordpress:php5.6-apache

# Add sudo in order to run wp-cli as the www-data user
RUN apt-get update && apt-get install -y sudo less mysql-client

# Add WP-CLI
RUN curl -o /bin/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
COPY wp-su.sh /bin/wp
RUN chmod +x /bin/wp-cli.phar /bin/wp

RUN docker-php-ext-install \
    pdo_mysql

# Cleanup
RUN apt-get clean
RUN rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*