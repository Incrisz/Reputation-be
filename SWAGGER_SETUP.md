# Swagger UI - Complete API Documentation

## ✅ Setup Complete

Your API documentation is now fully set up with **Swagger UI** using OpenAPI 3.0 specifications!

## Access Swagger UI

**URL:** `http://localhost:8000/api/docs`

## Available Endpoints (18 Total)

### Authentication (4 endpoints)

| Method | Endpoint | Description |
|--------|----------|-------------|
| **POST** | `/auth/register` | Register a new user account |
| **POST** | `/auth/login` | Login with email and password |
| **POST** | `/auth/logout` | Logout and invalidate token |
| **GET** | `/auth/me` | Get authenticated user profile |

### Businesses (5 endpoints)

| Method | Endpoint | Description |
|--------|----------|-------------|
| **GET** | `/businesses` | List all user businesses (paginated) |
| **POST** | `/businesses` | Create a new business |
| **GET** | `/businesses/{id}` | Get business details |
| **PUT** | `/businesses/{id}` | Update business information |
| **DELETE** | `/businesses/{id}` | Delete a business |

### Audits (5 endpoints)

| Method | Endpoint | Description |
|--------|----------|-------------|
| **GET** | `/audits` | List all audits (paginated) |
| **GET** | `/audits/{id}` | Get audit details with findings |
| **POST** | `/audits/trigger` | Start a new audit for a business |
| **GET** | `/audits/compare` | Compare two audits |
| **DELETE** | `/audits/{id}` | Delete an audit record |

### Subscriptions (5 endpoints)

| Method | Endpoint | Description |
|--------|----------|-------------|
| **GET** | `/subscription/plans` | List all subscription plans (public) |
| **GET** | `/subscription/current` | Get user's current subscription |
| **POST** | `/subscription/upgrade` | Upgrade/downgrade subscription plan |
| **POST** | `/subscription/cancel` | Cancel active subscription |
| **GET** | `/subscription/usage` | Get usage stats against plan limits |

## Features

✨ **Complete OpenAPI Documentation:**
- Full endpoint documentation with descriptions
- Request/response examples
- Parameter documentation (path, query, body)
- Error responses (401, 422, 404, 429, etc.)
- Security scheme (Bearer Token - Laravel Sanctum)
- Try-it-out functionality to test endpoints directly

## How to Use Swagger UI for Testing

1. **Open** `http://localhost:8000/api/docs`

2. **Register/Login first:**
   - Click "POST /auth/register"
   - Click "Try it out"
   - Fill in the JSON body with user details
   - Click "Execute"
   - Copy the `token` from the response

3. **Set Authorization:**
   - Click the "Authorize" button (top right)
   - Paste your token in the format: `<your_token>`
   - Click "Authorize"

4. **Test Protected Endpoints:**
   - Click any endpoint that requires authentication
   - Click "Try it out"
   - Fill in required parameters
   - Click "Execute"
   - View the response

## API Base URL

```
http://localhost:8000/api/v1
```

## Authentication

All endpoints except `/auth/register`, `/auth/login`, and `/subscription/plans` require authentication.

**Header:** `Authorization: Bearer YOUR_TOKEN_HERE`

**Token Format:** Laravel Sanctum token (obtained from login/register)

## Sample Request/Response

### Register a User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "company": "Tech Corp",
    "industry": "Technology"
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "token": "1|ABC123DEF456GHI789JKL..."
  }
}
```

## Documentation Files

The API documentation is automatically generated from OpenAPI annotations in the controller files:

- **Generated Docs:** `storage/api-docs/api-docs.json`
- **Swagger UI:** `http://localhost:8000/api/docs`
- **Redoc Docs:** `http://localhost:8000/api/redoc` (if available)

## Controller Annotations

All endpoints have OpenAPI annotations (`@OA\Get`, `@OA\Post`, etc.) that include:

- Summary and description
- Request/response schemas
- Parameter documentation
- Security requirements
- Example values

**Controller Files with Annotations:**
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Api/BusinessController.php`
- `app/Http/Controllers/Api/AuditController.php`
- `app/Http/Controllers/Api/SubscriptionController.php`
- `app/Http/Controllers/Api/SwaggerDocumentation.php` (Global definitions)

## Regenerating Documentation

If you add new endpoints or modify existing ones, regenerate the Swagger docs:

```bash
php artisan l5-swagger:generate
```

## Configuration

Swagger configuration is in: `config/l5-swagger.php`

Key settings:
- **Route:** `api/docs`
- **Format:** JSON
- **Annotation Paths:** `app/` (scanned for `@OA\` annotations)
- **Output:** `storage/api-docs/`

## Testing Tips for Developers

1. **Check Request Schema** - Swagger shows required vs optional fields
2. **View Example Responses** - See data structure in responses
3. **Test Error Cases** - Trigger 401, 422, 404 responses
4. **Verify Pagination** - Use page/per_page parameters
5. **Test Authentication Flow** - Register → Login → Use token

## Common Status Codes

- **200** - Success (GET, PUT, POST with existing data)
- **201** - Created (POST for new resource)
- **202** - Accepted (Async operation like audit trigger)
- **204** - No Content (DELETE)
- **401** - Unauthorized (Missing/invalid token)
- **404** - Not Found (Resource doesn't exist)
- **422** - Validation Error (Invalid request data)
- **429** - Rate Limited / Quota Exceeded

## Next Steps

1. **Share with team** - Bookmark `http://localhost:8000/api/docs`
2. **Use for QA** - Developers can test without postman
3. **Reference** - Developers can understand API contracts
4. **Document changes** - Always regenerate after adding endpoints
5. **CI/CD Integration** - Add `php artisan l5-swagger:generate` to deployment

---

**All 19 endpoints are now fully documented and ready for testing!** 🎉
