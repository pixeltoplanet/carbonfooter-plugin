#!/usr/bin/env bash
#
# WordPress Test Suite Setup Script
#
# This script downloads and configures the WordPress test suite for running
# integration tests. It requires a MySQL database to be available.
#
# Usage: ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
#
# Environment variables:
#   WP_TESTS_DIR  - Directory for WordPress test library (default: /tmp/wordpress-tests-lib)
#   WP_CORE_DIR   - Directory for WordPress core (default: /tmp/wordpress)
#

if [ $# -lt 3 ]; then
    echo "Usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
    echo "Example: $0 wordpress_tests root '' localhost latest"
    exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4:-localhost}
WP_VERSION=${5:-latest}

WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-/tmp/wordpress}

TMPDIR=${TMPDIR:-/tmp}
TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    else
        echo "Error: curl or wget required"
        exit 1
    fi
}

if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\-? ]]; then
    WP_TESTS_TAG="branches/tags/$WP_VERSION"
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
    WP_TESTS_TAG="trunk"
else
    # Fetch latest version
    download http://api.wordpress.org/core/version-check/1.7/ $TMPDIR/wp-latest.json
    WP_VERSION=$(grep -o '"version":"[^"]*"' $TMPDIR/wp-latest.json | head -1 | sed 's/"version":"//;s/"//')
    WP_TESTS_TAG="tags/$WP_VERSION"
fi

# Install WordPress
if [ ! -d "$WP_CORE_DIR" ]; then
    mkdir -p "$WP_CORE_DIR"
fi

if [ ! -f "$WP_CORE_DIR/wp-settings.php" ]; then
    echo "Downloading WordPress..."
    
    if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
        download https://wordpress.org/nightly-builds/wordpress-latest.zip $TMPDIR/wordpress.zip
    else
        download https://wordpress.org/wordpress-$WP_VERSION.zip $TMPDIR/wordpress.zip
    fi
    
    unzip -q $TMPDIR/wordpress.zip -d $TMPDIR
    mv $TMPDIR/wordpress/* "$WP_CORE_DIR"
fi

# Install WordPress test library
if [ ! -d "$WP_TESTS_DIR" ]; then
    mkdir -p "$WP_TESTS_DIR"
fi

if [ ! -f "$WP_TESTS_DIR/includes/functions.php" ]; then
    echo "Downloading WordPress test library..."
    
    svn export --quiet --ignore-externals \
        https://develop.svn.wordpress.org/$WP_TESTS_TAG/tests/phpunit/includes/ \
        "$WP_TESTS_DIR/includes"
    
    svn export --quiet --ignore-externals \
        https://develop.svn.wordpress.org/$WP_TESTS_TAG/tests/phpunit/data/ \
        "$WP_TESTS_DIR/data"
fi

# Create wp-tests-config.php
if [ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]; then
    echo "Creating wp-tests-config.php..."
    
    cat > "$WP_TESTS_DIR/wp-tests-config.php" << EOF
<?php
/* Path to the WordPress codebase */
define( 'ABSPATH', '$WP_CORE_DIR/' );

define( 'DB_NAME', '$DB_NAME' );
define( 'DB_USER', '$DB_USER' );
define( 'DB_PASSWORD', '$DB_PASS' );
define( 'DB_HOST', '$DB_HOST' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

\$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );

define( 'WPLANG', '' );
EOF
fi

# Create the test database
echo "Setting up test database..."

mysql_command="mysql -u$DB_USER"
if [ -n "$DB_PASS" ]; then
    mysql_command="$mysql_command -p$DB_PASS"
fi
if [ "$DB_HOST" != "localhost" ]; then
    mysql_command="$mysql_command -h$DB_HOST"
fi

$mysql_command -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"

echo ""
echo "WordPress test suite installed successfully!"
echo ""
echo "Configuration:"
echo "  WordPress Core: $WP_CORE_DIR"
echo "  Test Library:   $WP_TESTS_DIR"
echo "  Database:       $DB_NAME"
echo ""
echo "To run integration tests, set environment variables and run:"
echo ""
echo "  export WP_TESTS_DIR=$WP_TESTS_DIR"
echo "  vendor/bin/phpunit -c phpunit-integration.xml.dist"
echo ""
