#!/bin/bash

echo "🚀 Starting BizVisibility AI Database Setup..."
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

cd /home/cloud/Videos/Reputation-be

echo -e "${BLUE}1. Running migrations...${NC}"
php artisan migrate

echo ""
echo -e "${BLUE}2. Seeding subscription plans and test data...${NC}"
php artisan db:seed

echo ""
echo -e "${BLUE}3. Regenerating Swagger documentation...${NC}"
php artisan l5-swagger:generate

echo ""
echo -e "${GREEN}✅ Database setup completed successfully!${NC}"
echo ""
echo -e "${YELLOW}Available endpoints:${NC}"
echo "- API Documentation: http://localhost:8000/api/docs"
echo "- API Prefix: /api/v1"
echo ""
echo -e "${YELLOW}Test credentials:${NC}"
echo "- Email: test@example.com"
echo "- Password: password"
echo ""
echo -e "${YELLOW}To start the server:${NC}"
echo "php artisan serve"
