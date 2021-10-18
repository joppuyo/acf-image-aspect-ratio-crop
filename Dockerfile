# https://github.com/conetix/docker-wordpress-wp-cli/blob/master/Dockerfile

ARG wp_docker_tag

FROM wordpress:$wp_docker_tag

ARG xdebug_version

# Add sudo in order to run wp-cli as the www-data user
RUN apt-get update && apt-get install -y sudo less mariadb-client

# RUN pecl install xdebug-2.5.5 && docker-php-ext-enable xdebug

# Add WP-CLI
#RUN curl -o /bin/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
#COPY wp-su.sh /bin/wp
#RUN chmod +x /bin/wp-cli.phar /bin/wp

RUN curl -o /bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN chmod +x /bin/wp

RUN docker-php-ext-install \
    pdo_mysql

# Cleanup
RUN apt-get clean
RUN rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
