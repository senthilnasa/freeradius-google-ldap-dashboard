FROM freeradius/freeradius-server:3.0.23

# Install MySQL client libraries for SQL support and CA certificates for TLS
RUN apt-get update && apt-get install -y \
    libmysqlclient-dev \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

COPY configs/radiusd.conf /etc/freeradius/radiusd.conf
COPY configs/clients.conf /etc/freeradius/clients.conf
COPY configs/default /etc/freeradius/sites-available/default
COPY configs/inner-tunnel /etc/freeradius/sites-available/inner-tunnel
COPY configs/ldap /etc/freeradius/mods-available/ldap
COPY configs/sql /etc/freeradius/mods-available/sql
COPY configs/cache /etc/freeradius/mods-available/cache
COPY configs/exec /etc/freeradius/mods-available/exec
COPY configs/eap /etc/freeradius/mods-enabled/eap
COPY configs/decode-password.sh /etc/freeradius/decode-password.sh
COPY configs/queries.conf /etc/freeradius/mods-config/sql/main/mysql/queries.conf
COPY configs/proxy.conf /etc/freeradius/proxy.conf
COPY configs/dictionary.custom /etc/freeradius/dictionary.custom
COPY init.sh /usr/local/bin
COPY start-radrelay.sh /start-radrelay.sh
RUN chmod +x /usr/local/bin/init.sh /start-radrelay.sh /etc/freeradius/decode-password.sh && \
    sed -i 's/\r$//' /usr/local/bin/init.sh && \
    sed -i 's/\r$//' /start-radrelay.sh && \
    sed -i 's/\r$//' /etc/freeradius/decode-password.sh
RUN ln -s /etc/freeradius/mods-available/ldap /etc/freeradius/mods-enabled/ldap
RUN ln -s /etc/freeradius/mods-available/sql /etc/freeradius/mods-enabled/sql
RUN ln -s /etc/freeradius/mods-available/cache /etc/freeradius/mods-enabled/cache
RUN rm -f /etc/freeradius/mods-enabled/exec && ln -s /etc/freeradius/mods-available/exec /etc/freeradius/mods-enabled/exec

# Include custom dictionary in main FreeRADIUS dictionary
RUN echo '$INCLUDE /etc/freeradius/dictionary.custom' >> /usr/share/freeradius/dictionary

ENTRYPOINT ["/usr/local/bin/init.sh"]

CMD ["freeradius"]

