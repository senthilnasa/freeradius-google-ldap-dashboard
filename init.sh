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

# Configure firewall accounting replication
sed -i "s|ENV_FIREWALL_ACCT_SERVER|${FIREWALL_ACCT_SERVER:-10.10.10.1}|g" /etc/freeradius/proxy.conf
sed -i "s|ENV_FIREWALL_ACCT_PORT|${FIREWALL_ACCT_PORT:-1813}|g" /etc/freeradius/proxy.conf
sed -i "s|ENV_FIREWALL_ACCT_SECRET|${FIREWALL_ACCT_SECRET:-testing123}|g" /etc/freeradius/proxy.conf

# sed -i "s|GOOGLE_LDAP_PASSWORD|$GOOGLE_LDAP_PASSWORD|g" /etc/freeradius/mods-available/ldap
# sed -i "s|GOOGLE_LDAP_USERNAME|$GOOGLE_LDAP_USERNAME|g" /etc/freeradius/mods-available/ldap

# Replace LDAP credentials from environment variables
sed -i "s|LDAP_IDENTITY_PLACEHOLDER|${LDAP_IDENTITY}|g" /etc/freeradius/mods-available/ldap
sed -i "s|LDAP_PASSWORD_PLACEHOLDER|${LDAP_PASSWORD}|g" /etc/freeradius/mods-available/ldap

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

# Configure cache TTL from environment variable
echo "Configuring cache with TTL=${CACHE_TIMEOUT:-300} seconds..."
sed -i "s|ENV_CACHE_TTL|${CACHE_TIMEOUT:-300}|g" /etc/freeradius/mods-available/cache

# Configure password logging based on environment
# Control sensitive data logging in SQL queries (passwords)
echo "Configuring password logging based on LOG_SENSITIVE_DATA=${LOG_SENSITIVE_DATA:-false} and ENVIRONMENT=${ENVIRONMENT:-prod}..."

# First, fix encoding in queries.conf (convert from UTF-16 to UTF-8) - only if needed
QUERIES_FILE="/etc/freeradius/mods-config/sql/main/mysql/queries.conf"
if file "$QUERIES_FILE" | grep -q "UTF-16"; then
    echo "Converting queries.conf from UTF-16 to UTF-8..."
    iconv -f UTF-16LE -t UTF-8 "$QUERIES_FILE" > /tmp/queries.conf.tmp
    mv /tmp/queries.conf.tmp "$QUERIES_FILE"
else
    echo "queries.conf is already UTF-8, skipping conversion..."
fi
# Then fix line endings (CRLF to LF)
sed -i 's/\r$//' "$QUERIES_FILE"

if [ "${LOG_SENSITIVE_DATA}" = "true" ] || [ "${ENVIRONMENT}" = "dev" ]; then
    # Development mode: Log actual passwords for debugging
    echo "WARNING: Password logging is ENABLED. Use this setting only for development/debugging!"
    PASSWORD_LOG_VALUE="%{%{User-Password}:-%{Chap-Password}}"
else
    # Production mode: Mask passwords in logs (default secure behavior)
    echo "Password logging is DISABLED (production mode). Passwords will be masked as '***HIDDEN***'"
    PASSWORD_LOG_VALUE="***HIDDEN***"
fi

# Replace placeholder in SQL queries configuration
# Escape the password value for safe sed substitution
PASSWORD_LOG_VALUE_ESCAPED=$(echo "${PASSWORD_LOG_VALUE}" | sed 's|/|\\\/|g')
sed -i "s|ENV_PASSWORD_LOGGING_PLACEHOLDER|${PASSWORD_LOG_VALUE_ESCAPED}|g" /etc/freeradius/mods-config/sql/main/mysql/queries.conf

# =================================================================================
# VLAN Attribute Configuration Helper Function
# =================================================================================
# Function to generate VLAN attribute assignments based on VLAN_ATTRIBUTES env var
# Supports: Tunnel-Private-Group-ID, Aruba-User-VLAN, Aruba-Named-User-VLAN
generate_vlan_attributes() {
    local vlan_value="$1"
    local vlan_attrs="${VLAN_ATTRIBUTES:-Tunnel-Private-Group-ID}"

    # Start with standard Tunnel attributes (required for RFC compliance)
    echo "				Tunnel-Type := VLAN"
    echo "				Tunnel-Medium-Type := IEEE-802"

    # Process comma-separated list of VLAN attributes
    IFS=',' read -ra ATTRS <<< "$vlan_attrs"
    for attr in "${ATTRS[@]}"; do
        # Trim whitespace
        attr=$(echo "$attr" | xargs)

        case "$attr" in
            "Tunnel-Private-Group-ID"|"Tunnel-Private-Group-Id")
                # Send VLAN as INTEGER (no quotes) for AOS10 compatibility
                # AOS10 treats string values as static VLAN and falls back to DHCP on failure
                echo "				Tunnel-Private-Group-Id := $vlan_value"
                ;;
            "Aruba-User-VLAN")
                echo "				Aruba-User-VLAN := $vlan_value"
                ;;
            "Aruba-Named-User-VLAN")
                echo "				Aruba-Named-User-VLAN := \"VLAN$vlan_value\""
                ;;
            "Cisco-AVPair")
                echo "				Cisco-AVPair := \"vlan=$vlan_value\""
                ;;
            *)
                echo "# Warning: Unknown VLAN attribute: $attr"
                ;;
        esac
    done
}

# Update domain configuration with VLAN assignments
# Parse DOMAIN_CONFIG JSON and dynamically generate FreeRADIUS unlang configuration
if [ ! -z "$DOMAIN_CONFIG" ]; then
    echo "Updating domain configuration with VLAN assignments..."
    echo "Domain Config: $DOMAIN_CONFIG"
    echo "VLAN Attributes: ${VLAN_ATTRIBUTES:-Tunnel-Private-Group-ID (default)}"

    # Generate dynamic domain configuration file
    cat > /etc/freeradius/mods-config/files/dynamic-domains << 'EOFCONFIG'
# Dynamically generated domain configuration
# This file is auto-generated from DOMAIN_CONFIG environment variable
# DO NOT EDIT MANUALLY - Changes will be overwritten on container restart

EOFCONFIG

    # Parse JSON and generate unlang configuration using grep/sed
    # Support both key-based matching and domain-only matching
    # Extract domains and create domain_config.conf
    echo "# Domain to VLAN and Type mappings (with optional key matching)" > /tmp/domain_config.conf

    # Try to parse with key field first (new format)
    echo "$DOMAIN_CONFIG" | grep -o '"domain":"[^"]*","key":"[^"]*","Type":"[^"]*","VLAN":"[^"]*"' | while read entry; do
        domain=$(echo "$entry" | sed 's/.*"domain":"\([^"]*\)".*/\1/')
        key=$(echo "$entry" | sed 's/.*"key":"\([^"]*\)".*/\1/')
        user_type=$(echo "$entry" | sed 's/.*"Type":"\([^"]*\)".*/\1/')
        vlan=$(echo "$entry" | sed 's/.*"VLAN":"\([^"]*\)".*/\1/')
        echo "$domain|$key|$vlan|$user_type" >> /tmp/domain_config.conf
    done

    # Also support legacy format without key field for backward compatibility
    echo "$DOMAIN_CONFIG" | grep -o '"domain":"[^"]*","Type":"[^"]*","VLAN":"[^"]*"' | grep -v '"key":' | while read entry; do
        domain=$(echo "$entry" | sed 's/.*"domain":"\([^"]*\)".*/\1/')
        user_type=$(echo "$entry" | sed 's/.*"Type":"\([^"]*\)".*/\1/')
        vlan=$(echo "$entry" | sed 's/.*"VLAN":"\([^"]*\)".*/\1/')
        # No key for legacy format, use empty string
        echo "$domain||$vlan|$user_type" >> /tmp/domain_config.conf
    done
    
    # Extract and display supported domains
    DOMAINS=$(echo "$DOMAIN_CONFIG" | grep -o '"domain":"[^"]*"' | sed 's/"domain":"\([^"]*\)"/\1/g' | tr '\n' ', ' | sed 's/,$//')
    echo "Supported domains: $DOMAINS"
    
    domain_count=$(grep -c '|' /tmp/domain_config.conf 2>/dev/null || echo "0")
    echo "Generated configuration for $domain_count domains"
    
    if [ $? -eq 0 ]; then
        # Move the generated config to FreeRADIUS config directory
        mv /tmp/domain_config.conf /etc/freeradius/mods-config/files/domain_mappings.conf
        
        # Generate dynamic unlang configuration for VLAN assignment using bash
        # Support both key-based matching (user.mba@domain) and domain-only matching
        > /tmp/dynamic_vlan.conf  # Clear file
        first_entry=true

        # Process entries with key field (new format)
        echo "$DOMAIN_CONFIG" | grep -o '"domain":"[^"]*","key":"[^"]*","Type":"[^"]*","VLAN":"[^"]*"' | while read entry; do
            domain=$(echo "$entry" | sed 's/.*"domain":"\([^"]*\)".*/\1/')
            key=$(echo "$entry" | sed 's/.*"key":"\([^"]*\)".*/\1/')
            user_type=$(echo "$entry" | sed 's/.*"Type":"\([^"]*\)".*/\1/')
            vlan=$(echo "$entry" | sed 's/.*"VLAN":"\([^"]*\)".*/\1/')

            if [ "$first_entry" = true ]; then
                keyword="if"
                first_entry=false
            else
                keyword="elsif"
            fi

            # If key is empty, it's a fallback/default for that domain
            if [ -z "$key" ]; then
                # Generate VLAN attributes dynamically
                vlan_attrs=$(generate_vlan_attributes "$vlan")

                cat >> /tmp/dynamic_vlan.conf << UNLANG
		# $domain (default/others) = $user_type, VLAN $vlan
		$keyword (&request:Tmp-String-0 == "$domain") {
			update control {
				Tmp-String-1 := "$user_type"
			}
			update reply {
$vlan_attrs
			}
			# Copy VLAN and user type to session-state for EAP-TTLS/PEAP inner tunnel logging
			update session-state {
$vlan_attrs
				Tmp-String-1 := "$user_type"
			}
		}
UNLANG
            else
                # Key-based matching: check if User-Name contains the key AND domain matches
                # Generate VLAN attributes dynamically
                vlan_attrs=$(generate_vlan_attributes "$vlan")

                cat >> /tmp/dynamic_vlan.conf << UNLANG
		# $domain with key "$key" = $user_type, VLAN $vlan
		$keyword ((&request:Tmp-String-0 == "$domain") && (&User-Name =~ /$key@/)) {
			update control {
				Tmp-String-1 := "$user_type"
			}
			update reply {
$vlan_attrs
			}
			# Copy VLAN and user type to session-state for EAP-TTLS/PEAP inner tunnel logging
			update session-state {
$vlan_attrs
				Tmp-String-1 := "$user_type"
			}
		}
UNLANG
            fi
        done

        # Process legacy format (domain-only, no key field) for backward compatibility
        echo "$DOMAIN_CONFIG" | grep -o '"domain":"[^"]*","Type":"[^"]*","VLAN":"[^"]*"' | grep -v '"key":' | while read entry; do
            domain=$(echo "$entry" | sed 's/.*"domain":"\([^"]*\)".*/\1/')
            user_type=$(echo "$entry" | sed 's/.*"Type":"\([^"]*\)".*/\1/')
            vlan=$(echo "$entry" | sed 's/.*"VLAN":"\([^"]*\)".*/\1/')

            if [ "$first_entry" = true ]; then
                keyword="if"
                first_entry=false
            else
                keyword="elsif"
            fi

            # Generate VLAN attributes dynamically
            vlan_attrs=$(generate_vlan_attributes "$vlan")

            cat >> /tmp/dynamic_vlan.conf << UNLANG
		# $domain = $user_type, VLAN $vlan (legacy format)
		$keyword (&request:Tmp-String-0 == "$domain") {
			update control {
				Tmp-String-1 := "$user_type"
			}
			update reply {
$vlan_attrs
			}
			# Copy VLAN to session-state for EAP-TTLS/PEAP inner tunnel logging
			update session-state {
$vlan_attrs
			}
		}
UNLANG
        done

        if [ $? -eq 0 ]; then
            # Insert dynamic VLAN configuration into default site config
            # This replaces the hardcoded domain checks with dynamic ones
            cat > /tmp/authorize_section.conf << 'EOF'
	
	# Multi-domain support with dynamic VLAN assignments
	# Configuration loaded from DOMAIN_CONFIG environment variable
	# All domains use same LDAP connection (same Google Workspace)
	
	if (&request:Tmp-String-0) {
		# LDAP Credential Caching - Check cache first for performance
		# This significantly reduces authentication time from ~10s to <100ms
		ldap_cache {
			# If cache hit, skip SQL and LDAP queries
			ok = return
		}
		
		# Cache miss - Query SQL and LDAP
		# First try SQL for user data (optional, but useful for caching)
		sql
		
		# Then LDAP for authentication
		ldap
		
		# Populate cache after successful LDAP lookup
		if (ok || updated) {
			ldap_cache
		}
		
		if ((ok || updated) && User-Password && !control:Auth-Type) {
			update control {
				Auth-Type := ldap
				# For Google LDAP, bind using email address instead of DN
				LDAP-UserDn := "%{User-Name}"
			}
			
			# Dynamic VLAN assignment based on domain
EOF
            cat /tmp/dynamic_vlan.conf >> /tmp/authorize_section.conf
            cat >> /tmp/authorize_section.conf << 'EOF'
			else {
				# Domain not in configuration, reject
				update control {
					Auth-Type := Reject
					Error-Type := "invalid_domain"
				}
				update reply {
					Reply-Message := "Domain not supported"
				}
			}
		}
	}
	else {
		# Reject users from unsupported domains or invalid format
		update control {
			Auth-Type := Reject
			Error-Type := "invalid_domain"
		}
		update reply {
			Reply-Message := "Invalid username format or domain not supported"
		}
	}
EOF
            
            # Backup original config
            cp /etc/freeradius/sites-available/default /etc/freeradius/sites-available/default.backup
            
            # Replace the dynamic section in the default config
            # Using awk to replace content between markers
            awk -v new_config="$(cat /tmp/authorize_section.conf)" '
            BEGIN { skip=0; replaced=0 }
            /# --- BEGIN DYNAMIC DOMAIN CONFIG ---/ { 
                print
                skip=1
                next 
            }
            /# --- END DYNAMIC DOMAIN CONFIG ---/ { 
                if (skip && !replaced) {
                    print new_config
                    replaced=1
                }
                skip=0
                print
                next
            }
            !skip { print }
            ' /etc/freeradius/sites-available/default > /tmp/default.new
            
            # Replace the original with the new configuration
            if [ -s /tmp/default.new ]; then
                mv /tmp/default.new /etc/freeradius/sites-available/default
                echo "Dynamic domain VLAN configuration applied to FreeRADIUS default site"
            else
                echo "Error: Generated configuration file is empty, keeping original"
            fi

            # Also update inner-tunnel with same VLAN configuration
            # This is needed for EAP-TTLS/PEAP authentication where VLAN assignment happens in inner tunnel
            echo "Applying VLAN configuration to inner-tunnel..."
            cp /etc/freeradius/sites-available/inner-tunnel /etc/freeradius/sites-available/inner-tunnel.backup

            awk -v new_config="$(cat /tmp/dynamic_vlan.conf)" '
            BEGIN { skip=0; replaced=0 }
            /# --- BEGIN DYNAMIC DOMAIN CONFIG FOR INNER TUNNEL ---/ {
                print
                print "\tif (&request:Tmp-String-0) {"
                skip=1
                next
            }
            /# --- END DYNAMIC DOMAIN CONFIG FOR INNER TUNNEL ---/ {
                if (skip && !replaced) {
                    print new_config
                    print "\t\telse {"
                    print "\t\t\t# Domain not in configuration, reject"
                    print "\t\t\tupdate control {"
                    print "\t\t\t\tAuth-Type := Reject"
                    print "\t\t\t\tError-Type := \"invalid_domain\""
                    print "\t\t\t}"
                    print "\t\t\tupdate reply {"
                    print "\t\t\t\tReply-Message := \"Domain not supported\""
                    print "\t\t\t}"
                    print "\t\t}"
                    print "\t}"
                    replaced=1
                }
                skip=0
                print
                next
            }
            !skip { print }
            ' /etc/freeradius/sites-available/inner-tunnel > /tmp/inner-tunnel.new

            if [ -s /tmp/inner-tunnel.new ]; then
                mv /tmp/inner-tunnel.new /etc/freeradius/sites-available/inner-tunnel
                echo "Dynamic domain VLAN configuration applied to inner-tunnel"
            else
                echo "Error: Generated inner-tunnel configuration file is empty, keeping original"
            fi
        else
            echo "Failed to generate dynamic VLAN configuration"
        fi

        echo "Domain configuration updated successfully"
    else
        echo "Failed to parse DOMAIN_CONFIG, using default configuration"
    fi
fi

# Configure firewall accounting packet replication
echo "Configuring accounting packet replication: ENABLE_ACCT_REPLICATION=${ENABLE_ACCT_REPLICATION:-false}"
if [ "${ENABLE_ACCT_REPLICATION}" = "true" ]; then
    echo "Enabling accounting packet replication to firewall: ${FIREWALL_ACCT_SERVER}:${FIREWALL_ACCT_PORT}"
    
    # Create accounting replication configuration
    # WARNING: If firewall doesn't respond, accounting will fail for clients!
    # Firewall must be configured at 10.10.10.1:1813 with secret: testing123
    cat > /tmp/acct_replication.conf << 'EOF'
	#
	# Firewall accounting replication ENABLED
	#
	# How it works:
	# 1. FreeRADIUS receives accounting packet from client (AP/NAS)
	# 2. Logs to SQL database
	# 3. Proxies to firewall at 10.10.10.1:1813 (WAITS for response)
	# 4. If firewall responds within 20 seconds → Client gets Accounting-Response
	# 5. If firewall doesn't respond → Client gets NO response (FAILS)
	#
	# NOTE: Full email address restoration happens in pre-proxy section
	#       Firewall receives: user@domain.com (not just username)
	#
	# ENABLED: Proxying to firewall
	update control {
		Proxy-To-Realm := "firewall_accounting"
	}
EOF
    
    # Insert the replication config into the accounting section
    awk -v replication="$(cat /tmp/acct_replication.conf)" '
    BEGIN { skip=0; replaced=0 }
    /# --- BEGIN FIREWALL ACCOUNTING REPLICATION ---/ { 
        print
        skip=1
        next 
    }
    /# --- END FIREWALL ACCOUNTING REPLICATION ---/ { 
        if (skip && !replaced) {
            print replication
            replaced=1
        }
        skip=0
        print
        next
    }
    !skip { print }
    ' /etc/freeradius/sites-available/default > /tmp/default.acct
    
    if [ -s /tmp/default.acct ]; then
        mv /tmp/default.acct /etc/freeradius/sites-available/default
        echo "Accounting replication to firewall ${FIREWALL_ACCT_SERVER}:${FIREWALL_ACCT_PORT} enabled successfully"
    else
        echo "Warning: Failed to enable accounting replication"
    fi
else
    echo "Accounting packet replication is DISABLED"
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

# Start radrelay daemon if accounting replication is enabled
if [ "${ENABLE_ACCT_REPLICATION}" = "true" ]; then
    /start-radrelay.sh || echo "Warning: radrelay failed to start, accounting replication may not work"
fi

# Start FreeRADIUS in foreground mode
exec freeradius -X