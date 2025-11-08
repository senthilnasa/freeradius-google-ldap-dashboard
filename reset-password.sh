#!/bin/bash

# FreeRADIUS Admin Password Reset Script
# This script resets the admin password to default values

echo "==================================="
echo "FreeRADIUS Admin Password Reset"
echo "==================================="
echo

# Get environment variables
source .env 2>/dev/null || {
    echo "Warning: .env file not found. Using default values."
    DB_HOST="mysql"
    DB_NAME="radius"
    DB_USER="radius"
    DB_PASSWORD="radiuspass"
    ADMIN_USERNAME="admin"
    ADMIN_PASSWORD="admin123"
}

echo "Database Host: $DB_HOST"
echo "Database Name: $DB_NAME"
echo "Admin Username: $ADMIN_USERNAME"
echo "New Password: $ADMIN_PASSWORD"
echo

# Confirm reset
read -p "Are you sure you want to reset the admin password? (y/N): " confirm
if [[ ! $confirm =~ ^[Yy]$ ]]; then
    echo "Password reset cancelled."
    exit 0
fi

echo
echo "Resetting password..."

# Generate password hash (using PHP to match the application)
HASHED_PASSWORD=$(php -r "echo password_hash('$ADMIN_PASSWORD', PASSWORD_BCRYPT, ['cost' => 12]);")

# Reset password in database
docker exec -i $(docker ps -q -f name=radius-mysql) mysql -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" << EOF
UPDATE admin_users 
SET password_hash = '$HASHED_PASSWORD', 
    first_login = TRUE, 
    password_changed_at = NOW() 
WHERE username = '$ADMIN_USERNAME';
EOF

if [ $? -eq 0 ]; then
    echo "✅ Password reset successful!"
    echo
    echo "New login credentials:"
    echo "Username: $ADMIN_USERNAME"
    echo "Password: $ADMIN_PASSWORD"
    echo
    echo "⚠️  You will be required to change this password on first login."
else
    echo "❌ Password reset failed!"
    echo "Make sure the MySQL container is running and accessible."
    exit 1
fi