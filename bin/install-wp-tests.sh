#!/usr/bin/env bash

set -euo pipefail

DB_NAME=${1-wordpress_test}
DB_USER=${2-root}
DB_PASS=${3-}
DB_HOST=${4-127.0.0.1}
WP_VERSION=${5-latest}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress}
WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}

for TARGET_DIR in "$WP_CORE_DIR" "$WP_TESTS_DIR"; do
	if [[ -z "$TARGET_DIR" || "$TARGET_DIR" == "/" ]]; then
		echo "Refusing unsafe test directory: '$TARGET_DIR'" >&2
		exit 1
	fi
done

if [[ "$WP_VERSION" == "latest" ]]; then
	WP_VERSION=$(curl -fsSL https://api.wordpress.org/core/version-check/1.7/ | php -r '$data=json_decode(stream_get_contents(STDIN), true); echo $data["offers"][0]["version"];')
fi

if [[ ! -f "$WP_CORE_DIR/wp-settings.php" ]]; then
	rm -rf "$WP_CORE_DIR"
	mkdir -p "$WP_CORE_DIR"
	curl -fsSL "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz" | tar -xz --strip-components=1 -C "$WP_CORE_DIR"
fi

if [[ ! -f "$WP_TESTS_DIR/includes/functions.php" ]]; then
	rm -rf "$WP_TESTS_DIR"
	TEMP_DIR=$(mktemp -d)
	trap 'rm -rf "$TEMP_DIR"' EXIT
	curl -fsSL "https://github.com/WordPress/wordpress-develop/archive/refs/tags/${WP_VERSION}.tar.gz" | tar -xz -C "$TEMP_DIR"
	mkdir -p "$WP_TESTS_DIR"
	cp -R "$TEMP_DIR/wordpress-develop-${WP_VERSION}/tests/phpunit/"* "$WP_TESTS_DIR/"
fi

if [[ "$DB_HOST" == *:* ]]; then
	DB_HOSTNAME=${DB_HOST%%:*}
	DB_PORT=${DB_HOST##*:}
else
	DB_HOSTNAME=$DB_HOST
	DB_PORT=3306
fi

mysql --host="$DB_HOSTNAME" --port="$DB_PORT" --user="$DB_USER" ${DB_PASS:+--password="$DB_PASS"} \
	-e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

sed \
	-e "s/youremptytestdbnamehere/$DB_NAME/" \
	-e "s/yourusernamehere/$DB_USER/" \
	-e "s/yourpasswordhere/$DB_PASS/" \
	-e "s|localhost|$DB_HOST|" \
	-e "s|dirname( __FILE__ ) . '/src/'|'$WP_CORE_DIR/'|" \
	"$WP_TESTS_DIR/wp-tests-config-sample.php" > "$WP_TESTS_DIR/wp-tests-config.php"
