#!/bin/bash

set -e
set -x
echo "$@"

# Check if all env parameters exist
[ -z "$ACCESS_ALLOWED_CIDR" ] && echo "ACCESS_ALLOWED_CIDR env variable not defined! Exiting..." && exit 1
[ -z "$BASE_DOMAIN" ] && echo "BASE_DOMAIN env variable not defined! Exiting..." && exit 1
[ -z "$DOMAIN_EXTENSION" ] && echo "DOMAIN_EXTENSION env variable not defined! Exiting..." && exit 1
# [ -z "$GOOGLE_LDAP_PASSWORD" ] && echo "GOOGLE_LDAP_PASSWORD env variable not defined! Exiting..." && exit 1
# [ -z "$GOOGLE_LDAP_USERNAME" ] && echo "GOOGLE_LDAP_USERNAME env variable not defined! Exiting..." && exit 1
[ -z "$GOOGLE_LDAPTLS_CERT" ] && echo "GOOGLE_LDAPTLS_CERT env variable not defined! Exiting..." && exit 1
[ -z "$GOOGLE_LDAPTLS_KEY" ] && echo "GOOGLE_LDAPTLS_KEY env variable not defined! Exiting..." && exit 1

# replace all those env params in the file
sed -i "s|ENV_ACCESS_ALLOWED_CIDR|${ACCESS_ALLOWED_CIDR:-10.10.0.0/16}|g" /etc/freeradius/clients.conf
sed -i "s|ENV_RADIUS_SECRET|${SHARED_SECRET:-${RADIUS_SECRET:-testing123}}|g" /etc/freeradius/clients.conf

sed -i "s|BASE_DOMAIN|$BASE_DOMAIN|g" /etc/freeradius/proxy.conf
sed -i "s|DOMAIN_EXTENSION|$DOMAIN_EXTENSION|g" /etc/freeradius/proxy.conf

# sed -i "s|GOOGLE_LDAP_PASSWORD|$GOOGLE_LDAP_PASSWORD|g" /etc/freeradius/mods-available/ldap
# sed -i "s|GOOGLE_LDAP_USERNAME|$GOOGLE_LDAP_USERNAME|g" /etc/freeradius/mods-available/ldap

sed -i "s|GOOGLE_LDAPTLS_CERT|$GOOGLE_LDAPTLS_CERT|g" /etc/freeradius/mods-available/ldap
sed -i "s|GOOGLE_LDAPTLS_KEY|$GOOGLE_LDAPTLS_KEY|g" /etc/freeradius/mods-available/ldap

# Update SQL configuration with database environment variables
sed -i "s|\${ENV_DB_HOST}|${DB_HOST:-mysql}|g" /etc/freeradius/mods-available/sql
sed -i "s|\${ENV_DB_PORT}|${DB_PORT:-3306}|g" /etc/freeradius/mods-available/sql
sed -i "s|\"\${ENV_DB_USER}\"|\"${DB_USER:-radius}\"|g" /etc/freeradius/mods-available/sql
sed -i "s|\"\${ENV_DB_PASSWORD}\"|\"${DB_PASSWORD:-radiuspass}\"|g" /etc/freeradius/mods-available/sql
sed -i "s|\"\${ENV_DB_NAME}\"|\"${DB_NAME:-radius}\"|g" /etc/freeradius/mods-available/sql
sed -i "s|ENV_ENABLE_SQL_TRACE|${ENABLE_SQL_TRACE:-no}|g" /etc/freeradius/mods-available/sql
sed -i "s|ENV_DB_MAX_CONNECTIONS|${DB_MAX_CONNECTIONS:-20}|g" /etc/freeradius/mods-available/sql

# Update domain configuration with VLAN assignments
# Parse DOMAIN_CONFIG JSON and update configuration files
if [ ! -z "$DOMAIN_CONFIG" ]; then
    echo "Updating domain configuration with VLAN assignments..."
    # Log the domain configuration for debugging
    echo "Domain Config: $DOMAIN_CONFIG"
    
    # Extract domain names for validation
    DOMAINS=$(echo "$DOMAIN_CONFIG" | grep -o '"domain":"[^"]*"' | cut -d'"' -f4 | tr '\n' ',' | sed 's/,$//')
    echo "Supported domains: $DOMAINS"
fi

# add support to second level like: .com.br, .com.ar
sed -i "s|BASE_DOMAIN|$BASE_DOMAIN|g" /etc/freeradius/mods-available/ldap
if [[ ${DOMAIN_EXTENSION} =~ [.] ]]; then
    DOMAIN_EXTENSION=$( echo $DOMAIN_EXTENSION | awk -F'.' '{print $1",dc="$2}' )
fi
sed -i "s|DOMAIN_EXTENSION|$DOMAIN_EXTENSION|g" /etc/freeradius/mods-available/ldap

# Handle the certs
cp /certs/ldap-client.key /etc/freeradius/certs/ldap-client.key
cp /certs/ldap-client.crt /etc/freeradius/certs/ldap-client.crt
chown freerad:freerad /etc/freeradius/certs/ldap-client*
chmod 640 /etc/freeradius/certs/ldap-client*

# Handle the rest of the certificates
# First the array of files which need 640 permissions
FILES_640=( "ca.key" "server.key" "server.p12" "server.pem" "ldap-client.crt" "ldap-client.key" )
for i in "${FILES_640[@]}"
do
	if [ -f "/certs/$i" ]; then
	    cp /certs/$i /etc/raddb/certs/$i
	    chmod 640 /etc/raddb/certs/$i
	fi
done

# Now all files that need a 644 permission set
FILES_644=( "ca.pem" "server.crt" "server.csr" "dh" )
for i in "${FILES_644[@]}"
do
	if [ -f "/certs/$i" ]; then
	    cp /certs/$i /etc/raddb/certs/$i
	    chmod 644 /etc/raddb/certs/$i
	fi
done


# Start FreeRADIUS in foreground mode
exec freeradius -X
