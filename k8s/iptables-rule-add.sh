#!/bin/bash
# Advanced Port Forwarding Script with Custom Port Mapping
# Usage: ./iptables-port.sh <service_name> <public_port:internal_port> [public_port:internal_port] ...
# Example: ./iptables-port.sh flags-app 4129:3000 4130:8000

if [ "$#" -lt 2 ]; then
    echo "Usage: $0 <service_name> <public_port:internal_port> [public_port:internal_port] ..."
    echo ""
    echo "Examples:"
    echo "  $0 flags-app 4129:3000 4130:8000"
    echo "  $0 myapp 8080:80"
    echo "  $0 matrix 3478:3478 5349:5349"
    exit 1
fi

SERVICE_NAME=$1
LOCAL_SERVER="10.0.0.3"
AUDIT_DIR="/etc/caddy/audit-logs"
DOCS_FILE="$AUDIT_DIR/port-mappings.log"
IPTABLES_DIR="/etc/iptables"
shift  # Remove first argument, leaving only port mappings

echo "Setting up port forwarding for $SERVICE_NAME..."

# Enable IP forwarding (idempotent)
if ! grep -q "net.ipv4.ip_forward=1" /etc/sysctl.conf; then
    echo 'net.ipv4.ip_forward=1' >> /etc/sysctl.conf
    sysctl -p
fi

# Create directories if they don't exist
mkdir -p $AUDIT_DIR
mkdir -p $IPTABLES_DIR
if [ ! -f $DOCS_FILE ]; then
    echo "# Port Forwarding Mappings" > $DOCS_FILE
    echo "# Format: [timestamp] service_name: public_port -> internal_port" >> $DOCS_FILE
    echo "" >> $DOCS_FILE
fi

# Log this operation
echo "# =====================================" >> $DOCS_FILE
echo "# Service: $SERVICE_NAME" >> $DOCS_FILE
echo "# Date: $(date '+%Y-%m-%d %H:%M:%S')" >> $DOCS_FILE
echo "# =====================================" >> $DOCS_FILE

# Forward each port mapping
for MAPPING in "$@"; do
    # Parse public:internal format
    if [[ $MAPPING =~ ^([0-9]+):([0-9]+)$ ]]; then
        PUBLIC_PORT="${BASH_REMATCH[1]}"
        INTERNAL_PORT="${BASH_REMATCH[2]}"
    else
        echo "⚠ Invalid format: $MAPPING (expected format: public:internal)"
        continue
    fi

    echo "Forwarding $PUBLIC_PORT -> $LOCAL_SERVER:$INTERNAL_PORT..."

    # Check if rule already exists to avoid duplicates
    if ! iptables -t nat -C PREROUTING -p tcp --dport $PUBLIC_PORT -j DNAT --to-destination $LOCAL_SERVER:$INTERNAL_PORT 2>/dev/null; then
        iptables -t nat -A PREROUTING -p tcp --dport $PUBLIC_PORT -j DNAT --to-destination $LOCAL_SERVER:$INTERNAL_PORT
        iptables -A FORWARD -p tcp --dport $INTERNAL_PORT -d $LOCAL_SERVER -j ACCEPT
        echo "✓ Port $PUBLIC_PORT -> $INTERNAL_PORT forwarded"

        # Log successful forward
        echo "$PUBLIC_PORT -> $INTERNAL_PORT (added)" >> $DOCS_FILE
    else
        echo "⚠ Port $PUBLIC_PORT already forwarded"

        # Log that it already existed
        echo "$PUBLIC_PORT -> $INTERNAL_PORT (already exists)" >> $DOCS_FILE
    fi
done

echo "" >> $DOCS_FILE

# Save iptables rules to standard location for persistence
echo "Saving iptables rules..."
iptables-save > $IPTABLES_DIR/rules.v4

echo ""
echo "✅ Port forwarding complete for $SERVICE_NAME!"
echo "Mappings configured:"
for MAPPING in "$@"; do
    if [[ $MAPPING =~ ^([0-9]+):([0-9]+)$ ]]; then
        echo "  - Public port ${BASH_REMATCH[1]} -> Internal port ${BASH_REMATCH[2]}"
    fi
done
echo "Destination server: $LOCAL_SERVER"
echo ""
echo "📝 Audit log: $DOCS_FILE"
echo "💾 Rules saved: $IPTABLES_DIR/rules.v4"
echo ""
echo "Next steps:"
echo "1. Expose port(s) in Kubernetes (NodePort or hostPort)"
echo "2. Update Caddyfile if needed"
