FROM freeradius/freeradius-server:3.0.23

# Install MySQL client libraries for SQL support
RUN apt-get update && apt-get install -y \
    libmysqlclient-dev \
    && rm -rf /var/lib/apt/lists/*

COPY configs/clients.conf /etc/freeradius/clients.conf
COPY configs/default /etc/freeradius/sites-available/default
COPY configs/inner-tunnel /etc/freeradius/sites-available/inner-tunnel
COPY configs/ldap /etc/freeradius/mods-available/ldap
COPY configs/sql /etc/freeradius/mods-available/sql
COPY configs/mysql-queries.conf /etc/freeradius/mods-available/mysql-queries.conf
COPY configs/eap /etc/freeradius/mods-enabled/eap
COPY configs/proxy.conf /etc/freeradius/proxy.conf
COPY init.sh /usr/local/bin
RUN chmod +x /usr/local/bin/init.sh
RUN ln -s /etc/freeradius/mods-available/ldap /etc/freeradius/mods-enabled/ldap
RUN ln -s /etc/freeradius/mods-available/sql /etc/freeradius/mods-enabled/sql

ENTRYPOINT ["/usr/local/bin/init.sh"]

CMD ["freeradius"]
