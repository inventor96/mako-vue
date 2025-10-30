#!/bin/sh
set -e

# ensure working directory is the directory containing this script
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

# intro
echo ""
echo "This script will help you configure the network settings for your Docker-based"
echo "project. It will update the .env file and modify /etc/hosts to set up a local"
echo "development domain using an available loopback address. This will enable you to"
echo "access your project via a friendly domain name instead of localhost or an IP"
echo "address. Because each project uses its own loopback IP, you can have multiple"
echo "projects running simultaneously. This script also allows you to set the host"
echo "listing port to 80 to avoid needing to specify a port in the URL. If you"
echo "choose a privileged port (<1024), ensure your host's Docker configuration allows"
echo "binding to it."
echo ""
echo "You may be prompted for your password via sudo to modify /etc/hosts."
echo ""

# check for NoNewPrivs
if [ -f /proc/self/status ]; then
	if grep -q 'NoNewPrivs:\s*1' /proc/self/status; then
		echo "This script requires the ability to modify /etc/hosts, which is not possible with NoNewPrivs set."
		echo "Please run this script in an environment without NoNewPrivs."
		exit 1
	fi
fi

# check for .env file
if [ ! -f ../.env ]; then
	echo ".env file not found in project root. Do you want to create one based on .env.example? (Y/n)"
	read -r create_env
	# default to Y if the user just presses Enter (empty input)
	if [ -z "$create_env" ] || [ "$create_env" = "y" ] || [ "$create_env" = "Y" ]; then
		cp ../.env.example ../.env
		echo ".env file created from .env.example"
		echo ""
	else
		echo "Aborting."
		exit 1
	fi
fi

# load environment variables from .env file
export $(grep -v '^#' ../.env | xargs)

# report current values
echo "Current environment settings:"
echo "LISTEN_IP: ${LISTEN_IP}"
echo "LISTEN_DOMAIN: ${LISTEN_DOMAIN}"
echo "BACKEND_PORT: ${BACKEND_PORT}"
echo "FRONTEND_PORT: ${FRONTEND_PORT}"
echo ""

# ask for the desired domain name, defaulting to {project}.test if LISTEN_DOMAIN is 'localhost' or empty
if [ -n "$LISTEN_DOMAIN" ] && [ "$LISTEN_DOMAIN" != "localhost" ]; then
	domain_name=$LISTEN_DOMAIN
else
	domain_name=$(basename "$(dirname "$(pwd)")").test
fi
read -p "Enter the local development domain name for the project [${domain_name}]: " input_domain
domain_name=${input_domain:-$domain_name}

# check if the domain is already in /etc/hosts
if grep -q "$domain_name" /etc/hosts; then
	echo "The domain $domain_name is already in /etc/hosts"
	exit 1
fi

# ask for the desired IP address, defaulting to LISTEN_IP if it's not 127.0.0.1, otherwise find the next available 127.0.0.0/8 address
if [ -n "$LISTEN_IP" ] && [ "$LISTEN_IP" != "127.0.0.1" ]; then
	ip_address=$LISTEN_IP
else
	# find the next available 127.0.x.y address
	found_ip=false
	for j in $(seq 0 255); do
		for i in $(seq 2 254); do
			candidate_ip="127.0.$j.$i"
			if ! grep -q "$candidate_ip" /etc/hosts; then
				ip_address="$candidate_ip"
				found_ip=true
				break
			fi
		done
		[ "$found_ip" = true ] && break
	done
fi
read -p "Enter the local development IP address for the project [${ip_address}]: " input_ip
ip_address=${input_ip:-$ip_address}

# if the ip_address is not the same as LISTEN_IP, check if it's already in /etc/hosts
if [ "$ip_address" != "$LISTEN_IP" ]; then
	if grep -q "$ip_address" /etc/hosts; then
		echo "The IP address $ip_address is already in /etc/hosts"
		exit 1
	fi
fi

# ask for the desired backend port, defaulting to 80 if BACKEND_PORT is 8080
if [ -n "$BACKEND_PORT" ] && [ "$BACKEND_PORT" != "8080" ]; then
	backend_port=$BACKEND_PORT
else
	backend_port=80
fi
read -p "Enter the backend port for the project [${backend_port}]: " input_backend_port
backend_port=${input_backend_port:-$backend_port}

# notify user if backend_port is privileged (<1024)
if [ "$backend_port" -lt 1024 ]; then
	echo ""
	echo "NOTE: The backend port $backend_port is a privileged port (<1024). Please make sure your docker configuration is allowed to bind to it."
fi
echo ""

# ask for the desired frontend port, defaulting to 5173 if FRONTEND_PORT is 5173
if [ -n "$FRONTEND_PORT" ] && [ "$FRONTEND_PORT" != "5173" ]; then
	frontend_port=$FRONTEND_PORT
else
	frontend_port=5173
fi
read -p "Enter the frontend port for the project [${frontend_port}]: " input_frontend_port
frontend_port=${input_frontend_port:-$frontend_port}

# notify user if frontend_port is privileged (<1024)
if [ "$frontend_port" -lt 1024 ]; then
	echo ""
	echo "NOTE: The frontend port $frontend_port is a privileged port (<1024). Please make sure your docker configuration is allowed to bind to it."
fi
echo ""

# confirm the settings
echo "The following settings will be applied:"
echo "LISTEN_IP: $ip_address"
echo "LISTEN_DOMAIN: $domain_name"
echo "BACKEND_PORT: $backend_port"
echo "FRONTEND_PORT: $frontend_port"
echo ""
read -p "Do you want to proceed? (Y/n) " confirm
confirm=${confirm:-Y}
if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
	echo "Aborting."
	exit 1
fi
echo ""

# update .env file with the new settings
echo "Updating .env file..."
sed -i.bak -e "s|^LISTEN_IP=.*$|LISTEN_IP=$ip_address|" \
	-e "s|^LISTEN_DOMAIN=.*$|LISTEN_DOMAIN=$domain_name|" \
	-e "s|^BACKEND_PORT=.*$|BACKEND_PORT=$backend_port|" \
	-e "s|^FRONTEND_PORT=.*$|FRONTEND_PORT=$frontend_port|" ../.env
rm ../.env.bak

# add the new entry to /etc/hosts
echo "Updating /etc/hosts..."
echo "$ip_address	$domain_name db.$domain_name mail.$domain_name" | sudo tee -a /etc/hosts > /dev/null

# done
echo ""
echo "Configuration updated successfully!"
echo "Please restart your Docker containers to apply the new settings."
if [ "$backend_port" -eq 80 ]; then
	# display backend port only if not 80
	echo ""
	echo "You can now access your project at http://$domain_name"
else
	echo ""
	echo "You can now access your project at http://$domain_name:$backend_port"
fi