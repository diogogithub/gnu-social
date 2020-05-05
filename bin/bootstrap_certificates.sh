#!/bin/sh

echo "Domain: "
read doamin
echo "Email: "
read email

cat > ../docker/bootstrap/bootstrap.env <<EOF
email=${email}
domain=${domain}
EOF

echo "Social database name: "
read db
echo "Database user: "
read user
echo "Database password: "
read password
echo "Sitename: "
read sitename
echo "Admin nickname: "
read admin_nick
echo "Admin password: "
read admin_password
echo "Site profile (public|private|community|singleuser): "
read profile

cat > ../docker/social/social.env <<EOF
SOCIAL_DB=${db}
SOCIAL_USER=${user}
SOCIAL_PASSWORD=${password}
SOCIAL_DOMAIN=${domain}
SOCIAL_SITENAME=${sitename}
SOCIAL_ADMIN_NICK=${nick}
SOCIAL_ADMIN_PASSWORD=${admin_password}
SOCIAL_ADMIN_EMAIL=${email}
SOCIAL_SITE_PROFILE=${profile}
EOF

docker-compose -f docker/bootstrap/bootstrap.yaml up
