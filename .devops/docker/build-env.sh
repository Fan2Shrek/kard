touch .env.local

echo "APP_ENV=$(echo "${APP_ENV:-dev}")" >> .env.local

echo "MERCURE_PUBLIC_URL=$(echo "${MERCURE_PUBLIC_URL:-http://localhost:3000/.well-known/mercure}")" >> .env.local
echo "MERCURE_JWT_SECRET=$(echo "${MERCURE_JWT_SECRET:-!ChangeThisMercureHubJWTSecretKey!}")" >> .env.local

echo "DATABASE_URL=$(echo "${DATABASE_URL:-mysql://root:root@mysql:3306/symfony}")" >> .env.local
